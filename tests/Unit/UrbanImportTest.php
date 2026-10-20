<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\ValidationFailed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Contrato v1 da importação (sem banco): a execução de referência passa; arquivos fora do contrato são recusados. */
final class UrbanImportTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const KIT = self::ROOT . '/ml/marilia-setores';
    private ?string $dir = null;

    protected function tearDown(): void
    {
        if ($this->dir !== null) {
            array_map(unlink(...), glob($this->dir . '/*') ?: []);
            rmdir($this->dir);
        }
    }

    public function testReferenceCopyIsByteIdenticalToTheCodexManifest(): void
    {
        $manifest = json_decode((string) file_get_contents(self::KIT . '/ENTREGA-MANIFESTO.json'), true, 8, JSON_THROW_ON_ERROR);
        foreach ($manifest['files'] as $file) {
            self::assertSame($file['sha256'], hash_file('sha256', self::KIT . '/' . $file['path']), $file['path']);
        }
        self::assertCount(28, $manifest['files']);
    }

    public function testReferenceRunIsValidAndKeepsEveryDigit(): void
    {
        $bundle = self::readDefault();

        self::assertSame(['NO', 'NE', 'SO', 'SE'], array_keys($bundle['sectors']));
        self::assertSame('baseline', $bundle['run']['selected_method']);
        self::assertSame(['2026-10-20', '2026-10-27', '2026-07-31'], [$bundle['run']['as_of'], $bundle['run']['until'], $bundle['run']['data_reference_through']]);
        self::assertSame(0.027747636950151033, $bundle['run']['test_relative_gain']);
        self::assertSame(1, $bundle['forecast']['SO']['rank']);
        self::assertSame(5.622641509433962, $bundle['forecast']['SO']['expected_count']);
        self::assertSame(5.7739887317055345, $bundle['forecast']['SO']['expected_ml']);
        self::assertSame(['south' => -22.28, 'north' => -22.21, 'west' => -50.02, 'east' => -49.94], array_intersect_key($bundle['sectors']['SO'], array_flip(['south', 'north', 'west', 'east'])));
        self::assertCount(204, $bundle['replay']);
        self::assertSame('2025-01-01', $bundle['run']['model_issued_at']);
        self::assertSame($bundle['sha256'], self::readDefault()['sha256'], 'mesmos bytes, mesmo identificador');
    }

    public function testReplayRowsReproduceTheDeclaredTestMetrics(): void
    {
        $rows = self::readDefault()['replay'];

        $poisson = SectorForecast::replayMetrics($rows, 'poisson');
        $baseline = SectorForecast::replayMetrics($rows, 'baseline');
        self::assertSame([246, 626, 51, 204], [$poisson['captured'], $poisson['observed'], $poisson['weeks'], $poisson['rows']]);
        self::assertSame(233, $baseline['captured']);
        self::assertEqualsWithDelta(1.5903305092254256, $poisson['mae'], 1e-12);
        self::assertEqualsWithDelta(0.3929712460063898, $poisson['captured'] / $poisson['observed'], 1e-15);
    }

    public function testGridCellsMatchTheRunAndRankTheCityCentreFirst(): void
    {
        $cells = UrbanImporter::cells(self::ROOT . '/' . UrbanImporter::DEFAULT_GRID, self::readDefault());

        self::assertCount(64, $cells);
        $first = array_values(array_filter($cells, static fn(array $c): bool => $c['rank'] === 1))[0];
        self::assertSame([27, 'SO', 198, 54], [$first['cell'], $first['sector_id'], $first['count_52w'], $first['count_13w']]);
        self::assertSame((198 + 0.1) / 52.1, $first['expected']);
        self::assertEqualsWithDelta(['south' => -22.2275, 'north' => -22.21, 'west' => -49.96, 'east' => -49.94], array_intersect_key($first, array_flip(['south', 'north', 'west', 'east'])), 1e-9);
        self::assertSame(297, array_sum(array_column(array_filter($cells, static fn(array $c): bool => $c['sector_id'] === 'SO'), 'count_52w')), 'soma do setor = execução');
        self::assertSame(['SO', 'NO', 'SE', 'NE'], [SectorForecast::cellSector(0), SectorForecast::cellSector(32), SectorForecast::cellSector(7), SectorForecast::cellSector(63)]);
    }

    /** @return iterable<string, array{\Closure(string): string, string}> */
    public static function invalidGrids(): iterable
    {
        yield 'contagem de outro snapshot' => [static fn(string $raw): string => preg_replace('/^(2026-07-\d\d,27,)(\d+)/m', '${1}99', $raw, 1) ?? '', 'grade'];
        yield 'quadrado inexistente' => [static fn(string $raw): string => $raw . "2026-07-01,64,1\n", 'grade['];
        yield 'linha repetida' => [static fn(string $raw): string => $raw . (explode("\n", $raw)[1] ?? '') . "\n", 'grade['];
        yield 'cabeçalho trocado' => [static fn(string $raw): string => "cell,date,count\n" . substr($raw, strpos($raw, "\n") + 1), 'grade'];
    }

    /** @param \Closure(string): string $mutate */
    #[DataProvider('invalidGrids')]
    public function testGridOutsideTheContractIsRejected(\Closure $mutate, string $errorKeyPrefix): void
    {
        $this->dir = sys_get_temp_dir() . '/fiscaliza-grade-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0700);
        $raw = str_replace("\r\n", "\n", (string) file_get_contents(self::ROOT . '/' . UrbanImporter::DEFAULT_GRID));
        file_put_contents("{$this->dir}/grade.csv", $mutate($raw));
        try {
            UrbanImporter::cells("{$this->dir}/grade.csv", self::readDefault());
            self::fail('Grade fora do contrato foi aceita.');
        } catch (ValidationFailed $e) {
            self::assertStringStartsWith($errorKeyPrefix, (string) array_key_first($e->errors), (string) json_encode($e->errors, JSON_UNESCAPED_UNICODE));
        }
    }

    public function testRankIsDescendingWithLexicalTieBreak(): void
    {
        self::assertSame(['NO' => 1, 'NE' => 2, 'SO' => 3, 'SE' => 4], SectorForecast::rank(['SO' => 1.0, 'NE' => 1.0, 'NO' => 2.0, 'SE' => 0.0]));
        self::assertSame([], SectorForecast::rank([]));
        self::assertSame([2 => 1, 3 => 2, 10 => 3], SectorForecast::rankCells([10 => 1.0, 3 => 1.0, 2 => 2.0]), 'empate pelo número, não pelo texto');
    }

    public function testDistanceIsZeroInsideAndStraightLineOutside(): void
    {
        $box = ['south' => -22.28, 'north' => -22.21, 'west' => -50.02, 'east' => -49.94];
        self::assertSame(0.0, SectorForecast::distanceToBoxKm(-22.25, -49.98, $box));
        // 0,01° a leste da borda, na latitude de Marília: ~1,03 km.
        self::assertEqualsWithDelta(1.03, SectorForecast::distanceToBoxKm(-22.25, -49.93, $box), 0.01);
    }

    /** @return iterable<string, array{string, \Closure(string): ?string, string}> */
    public static function invalidBundles(): iterable
    {
        $json = static fn(\Closure $change): \Closure => static function (string $raw) use ($change): string {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            $change($data);

            return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        };
        yield 'arquivo truncado' => ['previsao.json', static fn(string $raw): string => substr($raw, 0, -40), 'previsao.json'];
        yield 'versão desconhecida' => ['previsao.json', $json(static function (array &$d): void {
            $d['schema_version'] = 2;
        }), 'previsao.json.schema_version'];
        yield 'protocolo desconhecido' => ['avaliacao.json', $json(static function (array &$d): void {
            $d['protocol_version'] = 'outro-v2';
        }), 'avaliacao.json.protocol_version'];
        yield 'setor inválido' => ['previsao.json', $json(static function (array &$d): void {
            $d['sectors'][0]['sector_id'] = 'XX';
        }), 'previsao.json.sectors[0].sector_id'];
        yield 'NaN' => ['previsao.json', static fn(string $raw): string => preg_replace('/"expected_count": [0-9.]+/', '"expected_count": NaN', $raw, 1) ?? '', 'previsao.json'];
        yield 'número infinito' => ['previsao.json', static fn(string $raw): string => preg_replace('/"expected_count": [0-9.]+/', '"expected_count": 1e999', $raw, 1) ?? '', 'previsao.json'];
        yield 'estimativa negativa' => ['previsao.json', $json(static function (array &$d): void {
            $d['sectors'][0]['expected_count_ml'] = -0.1;
        }), 'previsao.json.sectors[0].expected_count_ml'];
        yield 'contagem fracionária' => ['retrospecto.json', $json(static function (array &$d): void {
            $d['rows'][3]['observed_count'] = 1.5;
        }), 'retrospecto.json.rows[3].observed_count'];
        yield 'ordem incoerente' => ['previsao.json', $json(static function (array &$d): void {
            [$d['sectors'][0]['rank'], $d['sectors'][2]['rank']] = [$d['sectors'][2]['rank'], $d['sectors'][0]['rank']];
            [$d['sectors'][0]['priority'], $d['sectors'][2]['priority']] = [$d['sectors'][2]['priority'], $d['sectors'][0]['priority']];
        }), 'previsao.json.sectors'];
        yield 'período de oito dias' => ['previsao.json', $json(static function (array &$d): void {
            $d['until'] = '2026-10-28';
        }), 'previsao.json.until'];
        yield 'snapshot diferente do manifesto' => ['previsao.json', $json(static function (array &$d): void {
            $d['source_snapshot_sha256'] = str_repeat('a', 64);
        }), 'previsao.json.source_snapshot_sha256'];
        yield 'coordenadas [lat, lng]' => ['setores.geojson', $json(static function (array &$d): void {
            $d['features'][0]['geometry']['coordinates'][0] = array_map(static fn(array $p): array => [$p[1], $p[0]], $d['features'][0]['geometry']['coordinates'][0]);
        }), 'setores.geojson.features[0].geometry'];
        yield 'setor deslocado' => ['setores.geojson', $json(static function (array &$d): void {
            foreach ($d['features'][1]['geometry']['coordinates'][0] as &$p) {
                $p[0] += 0.01;
            }
        }), 'setores.geojson.features[1].geometry'];
        yield 'bairro oficial' => ['setores.geojson', $json(static function (array &$d): void {
            $d['features'][2]['properties']['official_neighborhood'] = true;
        }), 'setores.geojson.features[2].properties.official_neighborhood'];
        yield 'retrospecto adulterado' => ['retrospecto.json', $json(static function (array &$d): void {
            $d['rows'][0]['observed_count'] += 1;
        }), 'retrospecto.json.rows'];
        yield 'execução piloto' => ['status.json', $json(static function (array &$d): void {
            $d['status'] = 'piloto';
        }), 'status.json.status'];
        yield 'arquivo ausente' => ['modelo.json', static fn(string $raw): ?string => null, 'modelo.json'];
        yield 'arquivo grande demais' => ['previsao.json', static fn(string $raw): string => $raw . str_repeat(' ', UrbanImporter::MAX_FILE_BYTES), 'previsao.json'];
    }

    /** @param \Closure(string): ?string $mutate */
    #[DataProvider('invalidBundles')]
    public function testFilesOutsideTheContractAreRejected(string $file, \Closure $mutate, string $errorKey): void
    {
        $this->dir = sys_get_temp_dir() . '/fiscaliza-urbano-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0700);
        $sources = ['setores.geojson' => self::KIT . '/dados/setores.geojson', 'manifesto.json' => self::KIT . '/dados/manifesto.json'];
        foreach (['previsao.json', 'avaliacao.json', 'retrospecto.json', 'modelo.json', 'status.json'] as $name) {
            $sources[$name] = self::KIT . '/runs/primeiro-treino-verificado/' . $name;
        }
        foreach ($sources as $name => $source) {
            $content = (string) file_get_contents($source);
            $content = $name === $file ? $mutate($content) : $content;
            if ($content !== null) {
                file_put_contents("{$this->dir}/{$name}", $content);
            }
        }

        try {
            UrbanImporter::read($this->dir, "{$this->dir}/setores.geojson", "{$this->dir}/manifesto.json");
            self::fail('Conjunto fora do contrato foi aceito.');
        } catch (ValidationFailed $e) {
            self::assertSame([$errorKey], array_keys($e->errors), (string) json_encode($e->errors, JSON_UNESCAPED_UNICODE));
            self::assertStringContainsString('nada foi gravado', $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private static function readDefault(): array
    {
        return UrbanImporter::read(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST);
    }
}
