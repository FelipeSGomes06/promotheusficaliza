<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use DateTimeImmutable;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\UrbanRepository;

/**
 * Importa uma execução COMPLETA do treino separado (ml/marilia-setores) e o GeoJSON dos setores (contrato v1).
 *
 * Os arquivos são dados, nunca código: só os nomes esperados, até 10 MiB cada e 64 MiB no conjunto, JSON estrito com
 * profundidade limitada, números finitos. Tudo é validado ANTES de abrir a transação (versões, IDs, intervalos,
 * métodos, hashes, geometria, coerência entre arquivos e as métricas do teste recalculadas das linhas do
 * retrospecto); a gravação é uma transação só. O SHA-256 dos bytes identifica o conjunto: reimportar devolve a
 * execução existente.
 */
final class UrbanImporter
{
    public const DEFAULT_RUN = 'ml/marilia-setores/runs/primeiro-treino-verificado';
    public const DEFAULT_SECTORS = 'ml/marilia-setores/dados/setores.geojson';
    public const DEFAULT_MANIFEST = 'ml/marilia-setores/dados/manifesto.json';
    public const DEFAULT_GRID = 'ml/marilia-grade-2km/dados/contagens_grade.csv';
    public const MAX_FILE_BYTES = 10 * 1024 * 1024;
    public const MAX_TOTAL_BYTES = 64 * 1024 * 1024;
    /** Mesma regra do treino: emissão a mais de 180 dias da referência é recusada. */
    public const MAX_REFERENCE_AGE_DAYS = 180;
    private const RUN_FILES = ['previsao.json', 'avaliacao.json', 'retrospecto.json', 'modelo.json', 'status.json'];
    private const MAX_DEPTH = 16;
    private const TOLERANCE = 1e-9;
    private const AVAILABILITY = 'assumed_publication_delay_not_observed';
    private const MODEL_KIND = 'poisson_log_link_standardized';
    private const FEATURES = ['sector_NO', 'sector_NE', 'sector_SO', 'sector_SE', 'log1p_rate_13w', 'log1p_rate_52w', 'annual_sin', 'annual_cos', 'years_since_2020'];

    public function __construct(private readonly Database $db, private readonly UrbanRepository $urban, private readonly Clock $clock) {}

    /**
     * Importa a execução e, se $gridFile vier, os quadrados de 2 km calculados das contagens por quadrado. Os quadrados
     * não entram no SHA-256 da execução: uma execução já importada sem eles recebe os quadrados depois.
     *
     * @return array{id: int, created: bool, cells: int, as_of: string, until: string, sectors: int, replay_rows: int, sha256: string}
     */
    public function import(string $runDir, string $sectorsFile, string $manifestFile, ?string $gridFile = null): array
    {
        $bundle = self::read($runDir, $sectorsFile, $manifestFile);
        $cells = $gridFile === null ? [] : self::cells($gridFile, $bundle);
        $summary = ['as_of' => $bundle['run']['as_of'], 'until' => $bundle['run']['until'], 'sectors' => count($bundle['sectors']),
            'replay_rows' => count($bundle['replay']), 'sha256' => $bundle['sha256']];
        $existing = $this->urban->findByBundle($bundle['sha256']);
        if ($existing !== null) {
            return ['id' => $existing, 'created' => false, 'cells' => $this->addCells($existing, $cells)] + $summary;
        }
        try {
            $id = $this->db->transaction(function () use ($bundle, $cells): int {
                $id = $this->urban->insertRun($bundle, Time::toDb($this->clock->now()));
                $this->urban->insertCells($id, $cells);

                return $id;
            });
        } catch (ConstraintViolation $e) {
            // Outra importação dos mesmos bytes terminou primeiro: o resultado é o mesmo.
            $id = $e->constraint === 'UQ_URBAN_RUN_BUNDLE' ? $this->urban->findByBundle($bundle['sha256']) : null;
            if ($id === null) {
                throw $e;
            }

            return ['id' => $id, 'created' => false, 'cells' => $this->addCells($id, $cells)] + $summary;
        }

        return ['id' => $id, 'created' => true, 'cells' => count($cells)] + $summary;
    }

    /**
     * Grava os quadrados numa execução que ainda não os tem; devolve quantos ela tem ao final.
     *
     * @param list<array<string, mixed>> $cells
     */
    private function addCells(int $runId, array $cells): int
    {
        $stored = $this->urban->cellCount($runId);
        if ($stored === 0 && $cells !== []) {
            try {
                $this->db->transaction(fn() => $this->urban->insertCells($runId, $cells));
            } catch (ConstraintViolation $e) {
                if ($e->constraint !== 'PK_URBAN_CELL') {
                    throw $e;
                }
            }

            return $this->urban->cellCount($runId);
        }

        return $stored;
    }

    /**
     * Quadrados de 2 km (grade 8 × 8 do retângulo de estudo) a partir de `date,cell,count` (contagens reais por quadrado
     * e dia, ml/marilia-grade-2km). Estimativa = média simples das 52 semanas, (n + 0,1) ÷ 52,1 — o método que venceu
     * o teste de 2026 nesse experimento. Os quadrados de cada setor precisam somar exatamente as contagens de 13 e 52
     * semanas que a execução importada traz para o setor: arquivo de outro snapshot ou outro corte é recusado.
     *
     * @param array<string, mixed> $bundle resultado de read()
     * @return list<array<string, mixed>> 64 quadrados com a posição
     */
    public static function cells(string $path, array $bundle): array
    {
        $p = 'grade';
        if (!is_file($path) || is_link($path) || (int) filesize($path) > self::MAX_FILE_BYTES) {
            self::fail($p, 'Arquivo da grade ausente ou maior que 10 MiB.');
        }
        $lines = preg_split('/\r?\n/', rtrim((string) file_get_contents($path))) ?: [];
        if (array_shift($lines) !== 'date,cell,count') {
            self::fail($p, 'Cabeçalho esperado: date,cell,count.');
        }
        $through = (string) $bundle['run']['data_reference_through'];
        $from52 = self::addDays($through, -363);
        $from13 = self::addDays($through, -90);
        $c52 = array_fill(0, SectorForecast::GRID ** 2, 0);
        $c13 = $c52;
        $seen = [];
        foreach ($lines as $i => $line) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2}),(\d{1,2}),(\d{1,4})$/', $line, $m) !== 1 || (int) $m[2] >= SectorForecast::GRID ** 2 || (int) $m[3] < 1) {
                self::fail("{$p}[" . ($i + 2) . ']', 'Linha fora do contrato (data, quadrado 0–63, contagem positiva).');
            }
            self::date(['d' => $m[1]], 'd', "{$p}[" . ($i + 2) . ']');
            if (isset($seen["{$m[1]}|{$m[2]}"])) {
                self::fail("{$p}[" . ($i + 2) . ']', 'Data/quadrado repetidos.');
            }
            $seen["{$m[1]}|{$m[2]}"] = true;
            if ($m[1] >= $from52 && $m[1] <= $through) {
                $c52[(int) $m[2]] += (int) $m[3];
                $c13[(int) $m[2]] += $m[1] >= $from13 ? (int) $m[3] : 0;
            }
        }
        foreach ($bundle['forecast'] as $sectorId => $f) {
            $cells = array_filter(array_keys($c52), static fn(int $c): bool => SectorForecast::cellSector($c) === $sectorId);
            $sum52 = array_sum(array_intersect_key($c52, array_flip($cells)));
            $sum13 = array_sum(array_intersect_key($c13, array_flip($cells)));
            if ($sum52 !== $f['count_52w'] || $sum13 !== $f['count_13w']) {
                self::fail($p, "Os quadrados do setor {$sectorId} não somam as contagens da execução ({$sum52}/{$f['count_52w']} em 52 semanas): grade de outro snapshot ou corte.");
            }
        }
        return self::cellRows($c13, $c52, self::bounds($bundle['sectors']));
    }

    /**
     * Retângulo de estudo = união dos setores.
     *
     * @param array<string, array<string, mixed>> $sectors
     * @return array{south: float, north: float, west: float, east: float}
     */
    public static function bounds(array $sectors): array
    {
        return ['south' => (float) min(array_column($sectors, 'south')), 'north' => (float) max(array_column($sectors, 'north')),
            'west' => (float) min(array_column($sectors, 'west')), 'east' => (float) max(array_column($sectors, 'east'))];
    }

    /**
     * Linhas dos 64 quadrados: estimativa pela média simples, (n + 0,1) ÷ 52,1, e posição.
     *
     * @param array<int, int> $c13
     * @param array<int, int> $c52
     * @param array{south: float, north: float, west: float, east: float} $bounds
     * @return list<array<string, mixed>>
     */
    public static function cellRows(array $c13, array $c52, array $bounds): array
    {
        $expected = array_map(static fn(int $n): float => ($n + 0.1) / 52.1, $c52);
        $ranks = SectorForecast::rankCells($expected);
        $rows = [];
        foreach ($expected as $cell => $value) {
            $rows[] = ['cell' => $cell, 'sector_id' => SectorForecast::cellSector($cell), 'count_13w' => $c13[$cell], 'count_52w' => $c52[$cell],
                'expected' => $value, 'rank' => $ranks[$cell]] + SectorForecast::cellBox($bounds, $cell);
        }

        return $rows;
    }

    /**
     * Lê e valida o conjunto sem tocar no banco.
     *
     * @return array{sha256: string, files: array<string, array{sha256: string, bytes: int}>, run: array<string, mixed>,
     *     evaluation_json: string, model_json: string, provenance_json: string,
     *     sectors: array<string, array<string, mixed>>, forecast: array<string, array<string, mixed>>, replay: list<array<string, mixed>>}
     */
    public static function read(string $runDir, string $sectorsFile, string $manifestFile): array
    {
        $paths = array_combine(self::RUN_FILES, array_map(static fn(string $f): string => rtrim($runDir, '/\\') . '/' . $f, self::RUN_FILES));
        $paths += ['setores.geojson' => $sectorsFile, 'manifesto.json' => $manifestFile];
        $raw = [];
        $docs = [];
        $total = 0;
        foreach ($paths as $name => $path) {
            if (!is_file($path) || is_link($path)) {
                self::fail($name, 'Arquivo ausente (é preciso uma execução completa: ' . implode(', ', array_keys($paths)) . ').');
            }
            $size = (int) filesize($path);
            $total += $size;
            if ($size > self::MAX_FILE_BYTES || $total > self::MAX_TOTAL_BYTES) {
                self::fail($name, 'Arquivo grande demais (limites: 10 MiB por arquivo, 64 MiB no conjunto).');
            }
            $raw[$name] = (string) file_get_contents($path);
            $docs[$name] = self::decode($raw[$name], $name);
        }
        $files = [];
        foreach ($raw as $name => $bytes) {
            $files[$name] = ['sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes)];
        }
        ksort($files);
        $bundleHash = hash('sha256', implode("\n", array_map(static fn(string $n, array $f): string => "{$n}:{$f['sha256']}", array_keys($files), $files)));

        $manifest = self::manifest($docs['manifesto.json']);
        $sectors = self::sectors($docs['setores.geojson'], $manifest['bounds']);
        [$run, $forecast] = self::forecast($docs['previsao.json'], $manifest);
        $evaluation = self::evaluation($docs['avaliacao.json'], $run);
        $replay = self::replay($docs['retrospecto.json'], $run, $evaluation);
        self::model($docs['modelo.json'], $run, $evaluation);
        self::status($docs['status.json'], $run);

        return [
            'sha256' => $bundleHash,
            'files' => $files,
            'run' => $run + ['model_issued_at' => $replay['model_issued_at']],
            'evaluation_json' => $raw['avaliacao.json'],
            'model_json' => $raw['modelo.json'],
            'provenance_json' => $raw['manifesto.json'],
            'sectors' => $sectors,
            'forecast' => $forecast,
            'replay' => $replay['rows'],
        ];
    }

    /** @return array<string, mixed> */
    private static function decode(string $bytes, string $name): array
    {
        if (!mb_check_encoding($bytes, 'UTF-8')) {
            self::fail($name, 'O arquivo não está em UTF-8.');
        }
        try {
            // JSON estrito: NaN/Infinity e texto truncado são erro de sintaxe; números enormes viram INF e são recusados abaixo.
            $data = json_decode($bytes, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            self::fail($name, 'JSON inválido ou truncado: ' . $e->getMessage() . '.');
        }
        if (!is_array($data) || array_is_list($data)) {
            self::fail($name, 'Esperado um objeto JSON.');
        }
        array_walk_recursive($data, static function (mixed $v) use ($name): void {
            if (is_float($v) && !is_finite($v)) {
                self::fail($name, 'Número não finito.');
            }
        });

        return $data;
    }

    /**
     * @param array<string, mixed> $m
     * @return array{source_sha256: string, data_through: string, bounds: array{south: float, north: float, west: float, east: float}}
     */
    private static function manifest(array $m): array
    {
        $p = 'manifesto.json';
        self::same($m, 'schema_version', 1, $p);
        self::same($m, 'protocol_version', SectorForecast::PROTOCOL, $p);
        $source = self::object($m, 'source', $p);
        foreach (['source_url', 'source_catalog'] as $key) {
            if (preg_match('#^https://[a-z0-9.-]+/\S*$#i', self::text($source, $key, 300, "{$p}.source")) !== 1) {
                self::fail("{$p}.source.{$key}", 'Endereço da fonte inválido.');
            }
        }
        self::same($source, 'license', 'CC-BY-4.0', "{$p}.source");
        self::date($source, 'snapshot_date', "{$p}.source");
        $bounds = self::object(self::object($m, 'filters', $p), 'bounds', "{$p}.filters");
        $box = [];
        foreach (['south', 'north', 'west', 'east'] as $key) {
            $box[$key] = self::number($bounds, $key, "{$p}.filters.bounds", signed: true);
        }
        // Área de estudo dentro do entorno de Marília usado pelos formulários (não é o limite municipal).
        $area = Input::AREA;
        if (!($box['south'] < $box['north'] && $box['west'] < $box['east'] && $box['south'] >= $area['lat_min'] && $box['north'] <= $area['lat_max']
            && $box['west'] >= $area['lng_min'] && $box['east'] <= $area['lng_max'])) {
            self::fail("{$p}.filters.bounds", 'Retângulo de estudo fora do contrato.');
        }
        foreach (self::object($m, 'counts', $p) as $key => $count) {
            if (!is_int($count) || $count < 0) {
                self::fail("{$p}.counts.{$key}", 'Contagem inteira não negativa esperada.');
            }
        }
        self::hash($m, 'daily_sha256', $p);
        self::strings($m, 'limitations', $p);

        return ['source_sha256' => self::hash($source, 'source_sha256', "{$p}.source"), 'data_through' => self::date($source, 'data_through', "{$p}.source"), 'bounds' => $box];
    }

    /**
     * Quatro retângulos [longitude, latitude] = quadrantes do retângulo de estudo divididos no ponto médio.
     *
     * @param array<string, mixed> $g
     * @param array{south: float, north: float, west: float, east: float} $bounds
     * @return array<string, array<string, mixed>>
     */
    private static function sectors(array $g, array $bounds): array
    {
        $p = 'setores.geojson';
        self::same($g, 'type', 'FeatureCollection', $p);
        $features = self::items($g, 'features', $p, 4, 4);
        $midLat = ($bounds['south'] + $bounds['north']) / 2;
        $midLng = ($bounds['west'] + $bounds['east']) / 2;
        $expected = [
            'NO' => [$midLat, $bounds['north'], $bounds['west'], $midLng],
            'NE' => [$midLat, $bounds['north'], $midLng, $bounds['east']],
            'SO' => [$bounds['south'], $midLat, $bounds['west'], $midLng],
            'SE' => [$bounds['south'], $midLat, $midLng, $bounds['east']],
        ];
        $sectors = [];
        foreach ($features as $i => $feature) {
            $f = "{$p}.features[{$i}]";
            self::same($feature, 'type', 'Feature', $f);
            $props = self::object($feature, 'properties', $f);
            $id = self::choice($props, 'id', SectorForecast::SECTORS, "{$f}.properties");
            if (isset($sectors[$id])) {
                self::fail("{$f}.properties.id", 'Setor repetido.');
            }
            self::same($props, 'experimental', true, "{$f}.properties");
            self::same($props, 'official_neighborhood', false, "{$f}.properties");
            $geometry = self::object($feature, 'geometry', $f);
            self::same($geometry, 'type', 'Polygon', "{$f}.geometry");
            $rings = self::items($geometry, 'coordinates', "{$f}.geometry", 1, 1);
            if (!is_array($rings[0]) || !array_is_list($rings[0]) || count($rings[0]) !== 5) {
                self::fail("{$f}.geometry", 'Esperado um retângulo: um anel com cinco posições (a última repete a primeira).');
            }
            $lngs = [];
            $lats = [];
            foreach ($rings[0] as $pos) {
                if (!is_array($pos) || !array_is_list($pos) || count($pos) !== 2 || !self::isNumber($pos[0]) || !self::isNumber($pos[1])
                    || abs((float) $pos[0]) > 180 || abs((float) $pos[1]) > 90) {
                    self::fail("{$f}.geometry", 'Posição inválida (esperado [longitude, latitude] em WGS84).');
                }
                $lngs[] = (float) $pos[0];
                $lats[] = (float) $pos[1];
            }
            $box = ['south' => min($lats), 'north' => max($lats), 'west' => min($lngs), 'east' => max($lngs)];
            $corners = array_unique(array_map(static fn(float $x, float $y): string => "{$x}|{$y}", array_slice($lngs, 0, 4), array_slice($lats, 0, 4)));
            $closed = $lngs[0] === $lngs[4] && $lats[0] === $lats[4];
            $axisAligned = count(array_unique($lngs)) === 2 && count(array_unique($lats)) === 2 && count($corners) === 4;
            [$s, $n, $w, $e] = $expected[$id];
            if (!$closed || !$axisAligned || !self::close($box['south'], $s) || !self::close($box['north'], $n) || !self::close($box['west'], $w) || !self::close($box['east'], $e)) {
                self::fail("{$f}.geometry", "Geometria do setor {$id} fora do contrato (quadrante do retângulo de estudo, [longitude, latitude]).");
            }
            $centroid = self::object($props, 'centroid', "{$f}.properties");
            $lat = self::number($centroid, 'lat', "{$f}.properties.centroid", signed: true);
            $lng = self::number($centroid, 'lng', "{$f}.properties.centroid", signed: true);
            if (!self::close($lat, ($s + $n) / 2) || !self::close($lng, ($w + $e) / 2)) {
                self::fail("{$f}.properties.centroid", 'Centro diferente do centro do retângulo.');
            }
            $sectors[$id] = ['name' => self::text($props, 'name', 80, "{$f}.properties")] + $box + ['center_lat' => $lat, 'center_lng' => $lng];
        }

        return $sectors;
    }

    /**
     * @param array<string, mixed> $d
     * @param array{source_sha256: string, data_through: string, bounds: array<string, float>} $manifest
     * @return array{0: array<string, mixed>, 1: array<string, array<string, mixed>>}
     */
    private static function forecast(array $d, array $manifest): array
    {
        $p = 'previsao.json';
        self::same($d, 'schema_version', 1, $p);
        self::same($d, 'protocol_version', SectorForecast::PROTOCOL, $p);
        self::same($d, 'mode', 'forecast', $p);
        $asOf = self::date($d, 'as_of', $p);
        $until = self::date($d, 'until', $p);
        if ($until !== self::addDays($asOf, 7)) {
            self::fail("{$p}.until", 'O período previsto deve ter exatamente sete dias, com fim exclusivo.');
        }
        $through = self::date($d, 'data_reference_through', $p);
        if ($through >= $asOf || $through > $manifest['data_through']) {
            self::fail("{$p}.data_reference_through", 'A referência dos dados deve ser anterior ao período previsto e coberta pelo snapshot.');
        }
        if (self::days($through, $asOf) > self::MAX_REFERENCE_AGE_DAYS) {
            self::fail("{$p}.data_reference_through", 'Mais de 180 dias entre a referência dos dados e a emissão: gere uma execução nova.');
        }
        $generated = self::text($d, 'generated_at_utc', 40, $p);
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/', $generated) !== 1) {
            self::fail("{$p}.generated_at_utc", 'Instante ISO 8601 com fuso esperado.');
        }
        if (self::hash($d, 'source_snapshot_sha256', $p) !== $manifest['source_sha256']) {
            self::fail("{$p}.source_snapshot_sha256", 'Snapshot diferente do informado no manifesto.');
        }
        $method = self::choice($d, 'selected_method', array_keys(SectorForecast::METHODS), $p);
        self::same($d, 'candidate_method', 'poisson', $p);
        self::same($d, 'unit', 'expected_registered_crashes_in_7_days', $p);
        self::same($d, 'test_status', 'experimental_retrospective', $p);
        $run = [
            'protocol_version' => SectorForecast::PROTOCOL, 'as_of' => $asOf, 'until' => $until, 'data_reference_through' => $through,
            'generated_at' => Time::toDb(new DateTimeImmutable($generated)), 'source_snapshot_sha256' => $manifest['source_sha256'],
            'selected_method' => $method, 'candidate_method' => 'poisson',
            'test_relative_gain' => self::number($d, 'test_relative_gain', $p, signed: true), 'notice' => self::text($d, 'notice', 300, $p),
        ];
        $forecast = [];
        foreach (self::items($d, 'sectors', $p, 4, 4) as $i => $s) {
            $f = "{$p}.sectors[{$i}]";
            $id = self::choice($s, 'sector_id', SectorForecast::SECTORS, $f);
            if (isset($forecast[$id])) {
                self::fail("{$f}.sector_id", 'Setor repetido.');
            }
            $row = [
                'rank' => self::int($s, 'rank', $f, 1, 4),
                'priority' => self::choice($s, 'priority', ['PRIORITARIA', 'ACOMPANHAR'], $f),
                'expected_count' => self::number($s, 'expected_count', $f),
                'expected_ml' => self::number($s, 'expected_count_ml', $f),
                'expected_baseline' => self::number($s, 'expected_count_baseline', $f),
            ] + self::evidence(self::object($s, 'evidence', $f), "{$f}.evidence", $asOf);
            if ($row['history_through'] !== $through) {
                self::fail("{$f}.evidence.history_through", 'O histórico deve terminar na referência dos dados.');
            }
            if (!self::close($row['expected_count'], $row[SectorForecast::COLUMN[$method]])) {
                self::fail("{$f}.expected_count", 'A estimativa principal deve ser a do método escolhido no ajuste.');
            }
            if (!self::close($row['baseline_check'], $row['expected_baseline'])) {
                self::fail("{$f}.evidence.baseline_expected_count", 'Valor da referência diferente da estimativa de referência.');
            }
            unset($row['baseline_check']);
            if ($row['priority'] !== ($row['rank'] === 1 ? 'PRIORITARIA' : 'ACOMPANHAR')) {
                self::fail("{$f}.priority", 'Prioridade incoerente com a posição.');
            }
            $forecast[$id] = $row;
        }
        $ranks = SectorForecast::rank(array_map(static fn(array $r): float => $r['expected_count'], $forecast));
        foreach ($forecast as $id => $row) {
            if ($row['rank'] !== $ranks[$id]) {
                self::fail("{$p}.sectors", 'A ordem deve ser decrescente pela estimativa, com desempate pelo código do setor.');
            }
        }

        return [$run, $forecast];
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private static function evaluation(array $d, array $run): array
    {
        $p = 'avaliacao.json';
        self::same($d, 'schema_version', 1, $p);
        self::same($d, 'protocol_version', SectorForecast::PROTOCOL, $p);
        self::hash($d, 'config_sha256', $p);
        self::same($d, 'selected_before_test', $run['selected_method'], $p);
        $alpha = self::number($d, 'selected_alpha', $p);
        $model = self::modelCoefficients(self::object($d, 'test_model', $p), "{$p}.test_model", $alpha);
        $metrics = [];
        foreach (['poisson', 'baseline'] as $method) {
            $m = self::object($d, $method, $p);
            $f = "{$p}.{$method}";
            $metrics[$method] = [
                'mae' => self::number($m, 'mae', $f), 'deviance' => self::number($m, 'mean_poisson_deviance', $f),
                'observed' => self::int($m, 'observed_count', $f), 'predicted' => self::number($m, 'predicted_count', $f),
                'rows' => self::int($m, 'rows', $f, 1, 5000), 'weeks' => self::int($m, 'weeks', $f, 1, 1250),
                'captured' => self::int($m, 'top_budget_captured', $f), 'fraction' => self::number($m, 'top_budget_capture_fraction', $f),
                'from' => self::date($m, 'from', $f), 'until' => self::date($m, 'until', $f),
            ];
            self::same($m, 'top_sectors_budget', 1, $f);
            $x = $metrics[$method];
            if ($x['rows'] !== $x['weeks'] * 4 || $x['captured'] > $x['observed'] || $x['from'] >= $x['until']
                || !self::close($x['fraction'], $x['observed'] === 0 ? 0.0 : $x['captured'] / $x['observed'])) {
                self::fail($f, 'Métricas incoerentes entre si.');
            }
        }
        foreach (['from', 'until', 'rows', 'weeks', 'observed'] as $key) {
            if ($metrics['poisson'][$key] !== $metrics['baseline'][$key]) {
                self::fail($p, 'Os dois métodos precisam ser medidos no mesmo período e nas mesmas linhas.');
            }
        }
        $gain = self::number($d, 'poisson_deviance_relative_gain', $p, signed: true);
        if (!self::close($gain, $run['test_relative_gain']) || !self::close($gain, 1 - $metrics['poisson']['deviance'] / max($metrics['baseline']['deviance'], PHP_FLOAT_MIN))) {
            self::fail("{$p}.poisson_deviance_relative_gain", 'Ganho relativo incoerente com os desvios ou com a previsão.');
        }
        if ($model['training_labels_until'] > $metrics['poisson']['from']) {
            self::fail("{$p}.test_model.training_labels_until", 'O modelo congelado do teste não pode ter rótulos dentro do teste.');
        }
        self::strings($d, 'limitations', $p);

        return ['alpha' => $alpha, 'metrics' => $metrics, 'test_model' => $model];
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $run
     * @param array<string, mixed> $evaluation
     * @return array{rows: list<array<string, mixed>>, model_issued_at: string}
     */
    private static function replay(array $d, array $run, array $evaluation): array
    {
        $p = 'retrospecto.json';
        self::same($d, 'schema_version', 1, $p);
        self::same($d, 'mode', 'historical_replay', $p);
        self::same($d, 'selected_method', $run['selected_method'], $p);
        $metrics = $evaluation['metrics'];
        $issued = self::date($d, 'model_issued_at', $p);
        if ($issued > $metrics['poisson']['from'] || $issued <= $evaluation['test_model']['training_labels_until']) {
            self::fail("{$p}.model_issued_at", 'O modelo do retrospecto deve ser emitido antes do teste e depois dos rótulos de treino.');
        }
        $rows = [];
        $seen = [];
        foreach (self::items($d, 'rows', $p, 4, 5000) as $i => $r) {
            $f = "{$p}.rows[{$i}]";
            $from = self::date($r, 'from', $f);
            $id = self::choice($r, 'sector_id', SectorForecast::SECTORS, $f);
            if (self::date($r, 'until', $f) !== self::addDays($from, 7) || $from < $metrics['poisson']['from'] || self::addDays($from, 7) > $metrics['poisson']['until']) {
                self::fail($f, 'Semana fora do período de teste ou sem sete dias.');
            }
            if (isset($seen["{$from}|{$id}"])) {
                self::fail($f, 'Semana/setor repetidos.');
            }
            $seen["{$from}|{$id}"] = true;
            $row = ['week_from' => $from, 'week_until' => self::addDays($from, 7), 'sector_id' => $id,
                'expected_ml' => self::number($r, 'expected_count_ml', $f), 'expected_baseline' => self::number($r, 'expected_count_baseline', $f),
                'observed_count' => self::int($r, 'observed_count', $f)] + self::evidence(self::object($r, 'evidence', $f), "{$f}.evidence", $from);
            if (!self::close($row['baseline_check'], $row['expected_baseline'])) {
                self::fail("{$f}.evidence.baseline_expected_count", 'Valor da referência diferente da estimativa de referência.');
            }
            unset($row['baseline_check']);
            $rows[] = $row;
        }
        $perWeek = array_count_values(array_column($rows, 'week_from'));
        if (array_unique($perWeek) !== [array_key_first($perWeek) => 4]) {
            self::fail("{$p}.rows", 'Cada semana precisa dos quatro setores.');
        }
        // O retrospecto precisa reproduzir as métricas declaradas na avaliação (mesmas linhas, mesmo orçamento).
        foreach (['poisson', 'baseline'] as $method) {
            $got = SectorForecast::replayMetrics($rows, $method);
            $want = $metrics[$method];
            if ($got['rows'] !== $want['rows'] || $got['weeks'] !== $want['weeks'] || $got['observed'] !== $want['observed']
                || $got['captured'] !== $want['captured'] || !self::close($got['mae'], $want['mae'])) {
                self::fail("{$p}.rows", "As linhas não reproduzem as métricas do método {$method} na avaliação.");
            }
        }

        return ['rows' => $rows, 'model_issued_at' => $issued];
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $run
     * @param array<string, mixed> $evaluation
     */
    private static function model(array $d, array $run, array $evaluation): void
    {
        $p = 'modelo.json';
        self::same($d, 'protocol_version', SectorForecast::PROTOCOL, $p);
        $model = self::modelCoefficients($d, $p, $evaluation['alpha']);
        if ($model['training_labels_until'] > $run['as_of']) {
            self::fail("{$p}.training_labels_until", 'O modelo da emissão não pode usar rótulos do período previsto.');
        }
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $run
     */
    private static function status(array $d, array $run): void
    {
        self::same($d, 'status', 'complete', 'status.json');
        self::same($d, 'final_test_evaluated', true, 'status.json');
        self::same($d, 'selected_method', $run['selected_method'], 'status.json');
    }

    /**
     * Modelo exportado em JSON (sem objetos serializados): nove variáveis na ordem do treino e números finitos.
     *
     * @param array<string, mixed> $m
     * @return array{training_labels_until: string}
     */
    private static function modelCoefficients(array $m, string $p, float $alpha): array
    {
        self::same($m, 'kind', self::MODEL_KIND, $p);
        self::same($m, 'feature_names', self::FEATURES, $p);
        foreach (['mean', 'scale', 'coefficients'] as $key) {
            $values = self::items($m, $key, $p, 9, 9);
            foreach ($values as $v) {
                if (!self::isNumber($v) || ($key === 'scale' && (float) $v <= 0)) {
                    self::fail("{$p}.{$key}", 'Nove números finitos esperados (escala positiva).');
                }
            }
        }
        self::number($m, 'intercept', $p, signed: true);
        if (!self::close(self::number($m, 'alpha', $p), $alpha)) {
            self::fail("{$p}.alpha", 'Regularização diferente da escolhida no ajuste.');
        }
        $from = self::date($m, 'training_from', $p);
        $until = self::date($m, 'training_labels_until', $p);
        self::int($m, 'training_rows', $p, 1, 100000);
        if ($from >= $until) {
            self::fail("{$p}.training_labels_until", 'Janela de treino vazia.');
        }

        return ['training_labels_until' => $until];
    }

    /**
     * Evidência do corte de informação. baseline_check é conferido pelo chamador e descartado.
     *
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private static function evidence(array $e, string $p, string $before): array
    {
        $start = self::date($e, 'history_start', $p);
        $through = self::date($e, 'history_through', $p);
        if ($through >= $before || $start !== self::addDays($through, -363)) {
            self::fail($p, 'Histórico de 52 semanas inválido ou posterior ao corte.');
        }
        $c13 = self::int($e, 'count_13w', $p);
        $c52 = self::int($e, 'count_52w', $p);
        $baseline = self::number($e, 'baseline_expected_count', $p);
        if ($c13 > $c52 || !self::close($baseline, ($c52 + 1) / 53)) {
            self::fail($p, 'Contagens incoerentes com a referência (taxa de 52 semanas suavizada: (n+1)/53).');
        }
        self::same($e, 'availability', self::AVAILABILITY, $p);

        return ['history_start' => $start, 'history_through' => $through, 'count_13w' => $c13, 'count_52w' => $c52,
            'data_age_days' => self::int($e, 'data_age_days', $p, 0, 400), 'baseline_check' => $baseline];
    }

    // ---------------------------------------------------------------- leitura estrita de campos

    /** @return never */
    private static function fail(string $path, string $message): never
    {
        throw new ValidationFailed([$path => $message], 'Execução recusada; nada foi gravado.');
    }

    /** @param array<string, mixed> $d */
    private static function same(array $d, string $key, mixed $expected, string $p): void
    {
        if (!array_key_exists($key, $d) || $d[$key] !== $expected) {
            self::fail("{$p}.{$key}", 'Valor fora do contrato (esperado ' . json_encode($expected, JSON_UNESCAPED_UNICODE) . ').');
        }
    }

    /**
     * @param array<string, mixed> $d
     * @return array<string, mixed>
     */
    private static function object(array $d, string $key, string $p): array
    {
        $v = $d[$key] ?? null;
        if (!is_array($v) || ($v !== [] && array_is_list($v))) {
            self::fail("{$p}.{$key}", 'Objeto esperado.');
        }

        return $v;
    }

    /**
     * @param array<string, mixed> $d
     * @return list<mixed>
     */
    private static function items(array $d, string $key, string $p, int $min, int $max): array
    {
        $v = $d[$key] ?? null;
        if (!is_array($v) || !array_is_list($v) || count($v) < $min || count($v) > $max) {
            self::fail("{$p}.{$key}", $min === $max ? "Lista com exatamente {$min} itens esperada." : "Lista com {$min} a {$max} itens esperada.");
        }
        return $v;
    }

    /** @param array<string, mixed> $d */
    private static function text(array $d, string $key, int $max, string $p): string
    {
        $v = $d[$key] ?? null;
        if (!is_string($v) || trim($v) === '' || mb_strlen($v) > $max || preg_match('/[\x00-\x09\x0B-\x1F\x7F]/', $v) === 1) {
            self::fail("{$p}.{$key}", "Texto de até {$max} caracteres esperado.");
        }

        return $v;
    }

    /** @param array<string, mixed> $d */
    private static function strings(array $d, string $key, string $p): void
    {
        foreach (self::items($d, $key, $p, 0, 20) as $i => $item) {
            self::text(['item' => $item], 'item', 300, "{$p}.{$key}[{$i}]");
        }
    }

    /**
     * @param array<string, mixed> $d
     * @param list<string> $allowed
     */
    private static function choice(array $d, string $key, array $allowed, string $p): string
    {
        $v = $d[$key] ?? null;
        if (!is_string($v) || !in_array($v, $allowed, true)) {
            self::fail("{$p}.{$key}", 'Valor desconhecido (permitidos: ' . implode(', ', $allowed) . ').');
        }

        return $v;
    }

    /** @param array<string, mixed> $d */
    private static function date(array $d, string $key, string $p): string
    {
        $v = $d[$key] ?? null;
        $parsed = is_string($v) ? DateTimeImmutable::createFromFormat('!Y-m-d', $v) : false;
        if ($parsed === false || $parsed->format('Y-m-d') !== $v) {
            self::fail("{$p}.{$key}", 'Data ISO (AAAA-MM-DD) esperada.');
        }

        return $v;
    }

    /** @param array<string, mixed> $d */
    private static function hash(array $d, string $key, string $p): string
    {
        $v = $d[$key] ?? null;
        if (!is_string($v) || preg_match('/^[0-9a-f]{64}$/', $v) !== 1) {
            self::fail("{$p}.{$key}", 'SHA-256 em hexadecimal (64 caracteres) esperado.');
        }

        return $v;
    }

    /** @param array<string, mixed> $d */
    private static function int(array $d, string $key, string $p, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $v = $d[$key] ?? null;
        if (!is_int($v) || $v < $min || $v > $max) {
            self::fail("{$p}.{$key}", "Inteiro entre {$min} e {$max} esperado.");
        }

        return $v;
    }

    /**
     * Número finito; não negativo, salvo $signed.
     *
     * @param array<string, mixed> $d
     */
    private static function number(array $d, string $key, string $p, bool $signed = false): float
    {
        $v = $d[$key] ?? null;
        if (!self::isNumber($v) || (!$signed && (float) $v < 0)) {
            self::fail("{$p}.{$key}", $signed ? 'Número finito esperado.' : 'Número finito e não negativo esperado.');
        }

        return (float) $v;
    }

    private static function isNumber(mixed $v): bool
    {
        return (is_int($v) || is_float($v)) && is_finite((float) $v);
    }

    private static function close(float $a, float $b): bool
    {
        return abs($a - $b) <= self::TOLERANCE * max(1.0, abs($b));
    }

    private static function addDays(string $date, int $days): string
    {
        return (new DateTimeImmutable($date, Time::utc()))->modify(sprintf('%+d days', $days))->format('Y-m-d');
    }

    private static function days(string $from, string $to): int
    {
        return (int) (new DateTimeImmutable($from, Time::utc()))->diff(new DateTimeImmutable($to, Time::utc()))->format('%r%a');
    }
}
