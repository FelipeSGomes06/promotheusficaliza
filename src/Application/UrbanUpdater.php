<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use DateTimeImmutable;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\MetaRepository;
use Fiscaliza\Infrastructure\Repository\UrbanRepository;

/**
 * Atualização automática do planejamento por setores (`urban:update`, agendável):
 *
 * - **mensal:** baixa o arquivo aberto do Infosiga-SP uma vez por mês, lê só os sinistros de Marília com os mesmos
 *   filtros do treino e guarda as contagens por quadrado de 2 km e dia dos **últimos 12 meses** usados na conta
 *   (52 semanas até o corte de publicação), descartando o arquivo;
 * - **semanal:** recalcula a previsão da segunda-feira (setores e quadrados) com a média das 52 semanas, e o Poisson
 *   experimental com o modelo congelado da execução de referência. Método, modelo e avaliação não mudam: só os números.
 *
 * Idempotente: o mesmo arquivo não é gravado duas vezes, e a mesma semana com o mesmo arquivo também não.
 */
final class UrbanUpdater
{
    public const SOURCE_URL = 'https://infosiga.detran.sp.gov.br/painel/download/file/dados_infosiga.zip';
    public const MAX_DOWNLOAD_BYTES = 512 * 1024 * 1024;
    private const REQUIRED = ['id_sinistro', 'tipo_registro', 'data_sinistro', 'tipo_via', 'latitude', 'longitude', 'municipio'];
    /** "MARILIA", "MARÍLIA" e "MARíLIA" depois de strtoupper (o arquivo vem em Windows-1252). */
    private const MARILIA = ['MARILIA', "MAR\xCDLIA", "MAR\xEDLIA"];
    private const REFUSED = 'Arquivo do Infosiga recusado; nada foi gravado.';
    /** Último download bem-sucedido (APP_META), mesmo quando o arquivo veio igual e não gerou snapshot novo. */
    public const LAST_CHECK = 'infosiga_ultima_checagem';

    public function __construct(private readonly Database $db, private readonly UrbanRepository $urban, private readonly Clock $clock, private readonly MetaRepository $meta) {}

    /**
     * Rotina para agendar várias vezes ao dia: download só se ainda não houve neste mês (ou se forçado / arquivo local),
     * depois a previsão da semana. Falha no download não impede a previsão com os dados anteriores.
     *
     * @return array{ok: bool, messages: list<string>}
     */
    public function update(string $url, string $workDir, ?string $localZip = null, bool $forceDownload = false, ?string $week = null): array
    {
        $messages = [];
        $ok = true;
        $latest = $this->urban->latestSnapshot();
        $thisMonth = substr(Time::localDate($this->clock->now()), 0, 7);
        // Mês da última checagem: o marcador (vale também para arquivo igual, que não cria snapshot) ou o snapshot mais novo.
        $checked = max($this->meta->text(self::LAST_CHECK) ?? '', $latest['downloaded_at'] ?? '');
        try {
            $messages[] = match (true) {
                $localZip !== null => $this->ingest($localZip, 'arquivo local ' . basename($localZip)),
                $forceDownload || $checked === '' || substr(Time::localDate(Time::fromDb($checked) ?? $this->clock->now()), 0, 7) !== $thisMonth
                    => $this->download($url, $workDir),
                default => 'Download mensal já feito neste mês (snapshot #' . ($latest['id'] ?? '?') . ').',
            };
        } catch (ValidationFailed $e) {
            $ok = false;
            $messages[] = $e->getMessage() . ' ' . implode(' ', $e->errors);
        } catch (\RuntimeException $e) {
            $ok = false;
            $messages[] = $e->getMessage();
        }
        $messages[] = $this->forecast($week)['message'];

        return ['ok' => $ok, 'messages' => $messages];
    }

    public function download(string $url, string $workDir): string
    {
        if (!str_starts_with($url, 'https://')) {
            throw new \RuntimeException('Endereço do Infosiga precisa ser https.');
        }
        if (!is_dir($workDir) && !mkdir($workDir, 0775, true) && !is_dir($workDir)) {
            throw new \RuntimeException('Não foi possível criar a pasta de trabalho do download.');
        }
        $tmp = $workDir . '/infosiga-' . bin2hex(random_bytes(6)) . '.zip';
        $out = fopen($tmp, 'wb') ?: throw new \RuntimeException('Não foi possível gravar o download.');
        // curl, e não fopen: allow_url_fopen fica desligado no php.ini do projeto. Só https, inclusive nos redirecionamentos.
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_FILE => $out, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS_STR => 'https', CURLOPT_REDIR_PROTOCOLS_STR => 'https', CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_TIMEOUT => 900,
            CURLOPT_MAXFILESIZE_LARGE => self::MAX_DOWNLOAD_BYTES, CURLOPT_FAILONERROR => true,
            CURLOPT_USERAGENT => 'PromotheusFiscaliza/1.0 (atualizacao mensal de dados abertos)']);
        $ok = curl_exec($curl);
        $error = curl_error($curl);
        fclose($out);
        try {
            $bytes = (int) filesize($tmp);
            if ($ok !== true || $bytes === 0 || $bytes > self::MAX_DOWNLOAD_BYTES) {
                throw new \RuntimeException('Não foi possível baixar o arquivo do Infosiga-SP agora (' . ($error ?: 'resposta vazia') . '); a previsão segue com os dados anteriores.');
            }

            return $this->ingestDownloaded($tmp, $url);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Arquivo baixado com sucesso: guarda (ou reconhece como igual) e marca o mês como checado. Arquivo recusado não
     * marca, e a próxima rodada tenta de novo.
     */
    public function ingestDownloaded(string $zipPath, string $source): string
    {
        $message = $this->ingest($zipPath, $source);
        $this->meta->set(self::LAST_CHECK, Time::toDb($this->clock->now()), null, Time::toDb($this->clock->now()));

        return $message;
    }

    /** Lê o ZIP do Infosiga e grava os últimos 12 meses de contagens por quadrado e dia. */
    public function ingest(string $zipPath, string $source): string
    {
        $sha = (string) hash_file('sha256', $zipPath);
        $existing = $this->urban->findSnapshot($sha);
        if ($existing !== null) {
            return "Arquivo igual ao já guardado (snapshot #{$existing}); nada mudou.";
        }
        $reference = $this->urban->referenceRun() ?? throw new RuleViolation('Importe antes a execução de referência: php bin/fiscaliza urban:import.');
        $bounds = UrbanImporter::bounds($this->urban->sectors($reference['id']));
        $today = Time::localDate($this->clock->now());
        $from = self::addDays(SectorForecast::cutoff($today), -364);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::RDONLY) !== true) {
            throw new ValidationFailed(['arquivo' => 'Não é um ZIP válido.'], self::REFUSED);
        }
        $members = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (preg_match('/^sinistros_[0-9-]+\.csv$/', $name) === 1) {
                $members[] = $name;
            }
        }
        if ($members === []) {
            $zip->close();
            throw new ValidationFailed(['arquivo' => 'Nenhum sinistros_*.csv dentro do ZIP.'], self::REFUSED);
        }
        sort($members);
        $audit = ['marilia_records' => 0, 'duplicate_id' => 0, 'invalid_date' => 0, 'before_window' => 0, 'notification_or_other' => 0,
            'nonurban_or_unknown' => 0, 'missing_or_invalid_gps' => 0, 'outside_study_rectangle' => 0, 'eligible' => 0];
        $daily = [];
        $seen = [];
        $through = null;
        foreach ($members as $name) {
            $stream = $zip->getStream($name) ?: throw new ValidationFailed(['arquivo' => "Não foi possível ler {$name}."], self::REFUSED);
            $header = fgetcsv($stream, null, ';', '"', '');
            $index = is_array($header) ? array_flip(array_map(static fn($h) => trim((string) $h), $header)) : [];
            $missing = array_diff(self::REQUIRED, array_keys($index));
            if ($missing !== []) {
                fclose($stream);
                $zip->close();
                throw new ValidationFailed(['arquivo' => "{$name} sem as colunas " . implode(', ', $missing) . '.'], self::REFUSED);
            }
            while (($row = fgetcsv($stream, null, ';', '"', '')) !== false) {
                $value = static fn(string $column): string => trim((string) ($row[$index[$column]] ?? ''));
                if (!in_array(strtoupper($value('municipio')), self::MARILIA, true)) {
                    continue;
                }
                $audit['marilia_records']++;
                if (isset($seen[$value('id_sinistro')])) {
                    $audit['duplicate_id']++;
                    continue;
                }
                $seen[$value('id_sinistro')] = true;
                $date = DateTimeImmutable::createFromFormat('!d/m/Y', $value('data_sinistro'), Time::utc());
                if ($date === false || $date->format('d/m/Y') !== $value('data_sinistro')) {
                    $audit['invalid_date']++;
                    continue;
                }
                $day = $date->format('Y-m-d');
                $through = max($through ?? $day, $day);
                $lat = str_replace(',', '.', $value('latitude'));
                $lng = str_replace(',', '.', $value('longitude'));
                $outcome = match (true) {
                    $day < $from => 'before_window',
                    !in_array($value('tipo_registro'), ['SINISTRO FATAL', 'SINISTRO NAO FATAL'], true) => 'notification_or_other',
                    $value('tipo_via') !== 'VIAS URBANAS' => 'nonurban_or_unknown',
                    !is_numeric($lat) || !is_numeric($lng) => 'missing_or_invalid_gps',
                    default => SectorForecast::cellOf((float) $lat, (float) $lng, $bounds) ?? 'outside_study_rectangle',
                };
                if (is_int($outcome)) {
                    $daily[$day][$outcome] = ($daily[$day][$outcome] ?? 0) + 1;
                    $audit['eligible']++;
                } else {
                    $audit[$outcome]++;
                }
            }
            fclose($stream);
        }
        $zip->close();

        $latest = $this->urban->latestSnapshot();
        if ($audit['eligible'] === 0 || $through === null || $through > $today || $through < $from) {
            throw new ValidationFailed(['arquivo' => 'Sem registros elegíveis de Marília nos últimos 12 meses ou datas fora do esperado.'], self::REFUSED);
        }
        if ($latest !== null && $through < (string) $latest['data_through']) {
            throw new ValidationFailed(['arquivo' => "O arquivo termina em {$through}, antes do já guardado ({$latest['data_through']})."], self::REFUSED);
        }
        // Revisão de meses passados (o Infosiga corrige registros antigos): registra a maior variação mensal.
        if ($latest !== null) {
            $before = $this->urban->monthlyTotals($latest['id']);
            $now = [];
            foreach ($daily as $day => $cells) {
                $now[substr($day, 0, 7)] = ($now[substr($day, 0, 7)] ?? 0) + array_sum($cells);
            }
            $common = array_intersect_key($before, $now);
            unset($common[substr((string) $latest['data_through'], 0, 7)], $common[substr($from, 0, 7)]);
            $audit['maior_revisao_mensal'] = $common === [] ? 0.0 : max(array_map(static fn(string $m): float => abs($now[$m] - $before[$m]) / max(1, $before[$m]), array_keys($common)));
        }
        ksort($daily);
        $id = $this->db->transaction(fn(): int => $this->urban->insertSnapshot([
            'sha256' => $sha, 'source_url' => mb_substr($source, 0, 300), 'downloaded_at' => Time::toDb($this->clock->now()), 'bytes' => (int) filesize($zipPath),
            'data_from' => $from, 'data_through' => $through, 'eligible' => $audit['eligible'], 'audit' => $audit,
        ], $daily));
        $revision = ($audit['maior_revisao_mensal'] ?? 0) > 0.25 ? sprintf(' Atenção: um mês já guardado mudou %d%% nesta versão do Infosiga.', (int) round($audit['maior_revisao_mensal'] * 100)) : '';

        return sprintf('Snapshot #%d: %d registros elegíveis de %s a %s guardados (12 meses usados na conta).%s', $id, $audit['eligible'], $from, $through, $revision);
    }

    /**
     * Previsão da semana a partir do snapshot mais recente, gravada como execução comum (sem retrospecto próprio).
     *
     * @return array{message: string, run_id: ?int}
     */
    public function forecast(?string $asOf = null): array
    {
        $asOf ??= (new DateTimeImmutable(Time::localDate($this->clock->now()), Time::utc()))->modify('monday this week')->format('Y-m-d');
        $snapshot = $this->urban->latestSnapshot();
        $reference = $this->urban->referenceRun();
        if ($snapshot === null || $reference === null) {
            return ['message' => 'Previsão semanal não calculada: falta ' . ($reference === null ? 'importar a execução de referência (urban:import)' : 'o primeiro download do Infosiga') . '.', 'run_id' => null];
        }
        $cutoff = SectorForecast::cutoff($asOf);
        $through = self::addDays($cutoff, -1);
        $start = self::addDays($through, -363);
        if ((string) $snapshot['data_from'] > $start || (string) $snapshot['data_through'] < $through) {
            return ['message' => "Previsão de {$asOf} não calculada: os dados guardados ({$snapshot['data_from']} a {$snapshot['data_through']}) não cobrem as 52 semanas até {$through}.", 'run_id' => null];
        }
        $sha = hash('sha256', "auto|{$snapshot['sha256']}|{$asOf}");
        $existing = $this->urban->findByBundle($sha);
        if ($existing !== null) {
            return ['message' => "Previsão da semana de {$asOf} já calculada (execução #{$existing}).", 'run_id' => $existing];
        }

        $c52 = $this->urban->cellCounts($snapshot['id'], $start, $through);
        $c13 = $this->urban->cellCounts($snapshot['id'], self::addDays($through, -90), $through);
        $sectors = $this->urban->sectors($reference['id']);
        $method = (string) $reference['selected_method'];
        $forecast = [];
        foreach (SectorForecast::SECTORS as $sectorId) {
            $mine = array_filter(array_keys($c52), static fn(int $c): bool => SectorForecast::cellSector($c) === $sectorId);
            $n52 = array_sum(array_intersect_key($c52, array_flip($mine)));
            $n13 = array_sum(array_intersect_key($c13, array_flip($mine)));
            $baseline = ($n52 + 1) / 53;
            $ml = SectorForecast::poisson($reference['model'], SectorForecast::poissonFeatures($sectorId, $n13, $n52, $asOf));
            $forecast[$sectorId] = ['expected_count' => $method === 'baseline' ? $baseline : $ml, 'expected_ml' => $ml, 'expected_baseline' => $baseline,
                'history_start' => $start, 'history_through' => $through, 'count_13w' => $n13, 'count_52w' => $n52,
                'data_age_days' => self::days($cutoff, $asOf)];
        }
        foreach (SectorForecast::rank(array_map(static fn(array $f): float => $f['expected_count'], $forecast)) as $sectorId => $rank) {
            $forecast[$sectorId] += ['rank' => $rank, 'priority' => $rank === 1 ? 'PRIORITARIA' : 'ACOMPANHAR'];
        }
        $provenance = $reference['provenance'];
        $snapshotDate = Time::localDate(Time::fromDb($snapshot['downloaded_at']) ?? $this->clock->now());
        $provenance['source'] = ['source_url' => $snapshot['source_url'], 'source_catalog' => $provenance['source']['source_catalog'] ?? 'https://dadosabertos.sp.gov.br/dataset/sinistros-infosiga',
            'source_sha256' => $snapshot['sha256'], 'license' => 'CC-BY-4.0', 'snapshot_date' => $snapshotDate, 'data_through' => $snapshot['data_through']];
        $provenance['counts'] = $snapshot['audit'];
        $bundle = [
            'sha256' => $sha,
            'files' => [],
            'run' => ['protocol_version' => SectorForecast::PROTOCOL, 'as_of' => $asOf, 'until' => self::addDays($asOf, 7), 'data_reference_through' => $through,
                'generated_at' => Time::toDb($this->clock->now()), 'source_snapshot_sha256' => $snapshot['sha256'], 'selected_method' => $method,
                'candidate_method' => 'poisson', 'test_relative_gain' => $reference['test_relative_gain'], 'model_issued_at' => $reference['model_issued_at'],
                'notice' => "Previsão automática semanal: média das 52 semanas recalculada com o Infosiga baixado em {$snapshotDate}; mesmo método da execução de referência #{$reference['id']}."],
            'evaluation_json' => json_encode($reference['evaluation'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'model_json' => json_encode($reference['model'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'provenance_json' => json_encode($provenance, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'sectors' => array_map(static fn(array $s): array => array_intersect_key($s, array_flip(['name', 'south', 'north', 'west', 'east', 'center_lat', 'center_lng'])), $sectors),
            'forecast' => $forecast,
            'replay' => [],
        ];
        $cells = UrbanImporter::cellRows($c13, $c52, UrbanImporter::bounds($sectors));
        try {
            $id = $this->db->transaction(function () use ($bundle, $cells): int {
                $id = $this->urban->insertRun($bundle, Time::toDb($this->clock->now()));
                $this->urban->insertCells($id, $cells);

                return $id;
            });
        } catch (ConstraintViolation $e) {
            if ($e->constraint !== 'UQ_URBAN_RUN_BUNDLE') {
                throw $e;
            }
            $id = (int) $this->urban->findByBundle($sha);
        }

        return ['message' => "Previsão da semana de {$asOf} calculada (execução #{$id}, dados até {$through}).", 'run_id' => $id];
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
