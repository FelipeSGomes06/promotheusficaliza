<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use DateTimeImmutable;
use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/**
 * Atualização automática (urban:update) com um ZIP no formato do Infosiga montado a partir das contagens reais por
 * quadrado: o cálculo semanal em PHP precisa reproduzir a execução de referência do treino Python.
 */
final class UrbanUpdaterTest extends FirebirdTestCase
{
    private const HEADER = 'id_sinistro;tipo_registro;data_sinistro;hora_sinistro;tipo_via;latitude;longitude;municipio';
    /** @var list<string> */
    private array $files = [];
    private Services $today;

    protected function setUp(): void
    {
        parent::setUp();
        // Mesmo dia da execução de referência: corte de publicação em 01/08/2026.
        $this->today = $this->services->withClock(new Clock(new DateTimeImmutable('2026-10-20 15:00:00', Time::utc())));
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->files, is_file(...)));
    }

    public function testWeeklyForecastFromTheMonthlyFileReproducesTheReferenceRun(): void
    {
        $reference = $this->importReference();
        $message = $this->today->urbanUpdater()->ingest($this->zipFromGrid(), 'teste');

        self::assertStringContainsString('912 registros elegíveis de 2025-08-02 a 2026-09-30', $message);
        $auto = $this->today->urbanUpdater()->forecast('2026-10-20');
        self::assertNotNull($auto['run_id']);

        $ref = $this->services->urbanRepository()->sectors($reference);
        $new = $this->services->urbanRepository()->sectors($auto['run_id']);
        foreach (SectorForecast::SECTORS as $id) {
            self::assertSame(
                [$ref[$id]['count_13w'], $ref[$id]['count_52w'], $ref[$id]['rank_no'], $ref[$id]['expected_baseline']],
                [$new[$id]['count_13w'], $new[$id]['count_52w'], $new[$id]['rank_no'], $new[$id]['expected_baseline']],
                $id,
            );
            self::assertEqualsWithDelta($ref[$id]['expected_ml'], $new[$id]['expected_ml'], 1e-12, "{$id}: Poisson em PHP = Python");
        }
        $cells = static fn(array $rows): array => array_map(static fn(array $c): array => [$c['cell_no'], $c['count_52w'], $c['expected']], $rows);
        self::assertSame($cells($this->services->urbanRepository()->topCells($reference, 64)), $cells($this->services->urbanRepository()->topCells($auto['run_id'], 64)));
        $run = $this->services->urbanRepository()->latestRun();
        self::assertSame([$auto['run_id'], []], [$run['id'], $run['files']], 'a automática vira a atual; arquivos vazios = automática');
        self::assertSame($reference, $this->services->urbanRepository()->referenceRun()['id'], 'o retrospecto continua o da referência');
    }

    public function testSameFileAndSameWeekAreNotStoredTwice(): void
    {
        $this->importReference();
        $zip = $this->zipFromGrid();
        $this->today->urbanUpdater()->ingest($zip, 'teste');
        $first = $this->today->urbanUpdater()->forecast(null);

        self::assertStringContainsString('igual ao já guardado', $this->today->urbanUpdater()->ingest($zip, 'teste'));
        self::assertSame($first['run_id'], $this->today->urbanUpdater()->forecast(null)['run_id']);
        self::assertStringContainsString('2026-10-19', $first['message'], 'sem data: a segunda-feira da semana');
        self::assertSame(1, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_SNAPSHOT'));
        $update = $this->today->urbanUpdater()->update('https://exemplo.invalido/nao-usado.zip', sys_get_temp_dir());
        self::assertSame(true, $update['ok']);
        self::assertStringContainsString('Download mensal já feito neste mês', $update['messages'][0], 'só um download por mês');
    }

    /**
     * A07 da auditoria de 25/10/2026: no mês seguinte o Infosiga ainda pode servir os mesmos bytes. O download
     * bem-sucedido conta como checagem do mês, e a rodada seguinte (6 h depois) não baixa de novo; falha não conta.
     */
    public function testIdenticalFileInTheNextMonthCountsAsThatMonthsCheck(): void
    {
        $this->importReference();
        $zip = $this->zipFromGrid();
        $this->today->urbanUpdater()->ingestDownloaded($zip, 'download de outubro');
        $november = fn(string $utc): Services => $this->services->withClock(new Clock(new DateTimeImmutable($utc, Time::utc())));
        $unreachable = 'https://127.0.0.1:9/dados_infosiga.zip';

        $failed = $november('2026-11-03 09:00:00')->urbanUpdater()->update($unreachable, sys_get_temp_dir());
        self::assertStringContainsString('Não foi possível baixar', $failed['messages'][0]);
        $retry = $november('2026-11-03 15:00:00')->urbanUpdater()->update($unreachable, sys_get_temp_dir());
        self::assertStringContainsString('Não foi possível baixar', $retry['messages'][0], 'falha não marca o mês: a rodada seguinte tenta de novo');

        self::assertStringContainsString('Arquivo igual', $november('2026-11-03 15:00:00')->urbanUpdater()->ingestDownloaded($zip, 'download de novembro'));
        $next = $november('2026-11-03 21:00:00')->urbanUpdater()->update($unreachable, sys_get_temp_dir());
        self::assertStringContainsString('Download mensal já feito neste mês', $next['messages'][0]);
        self::assertSame(1, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_SNAPSHOT'), 'uma cópia só das contagens');
    }

    public function testFiltersKeepOnlyEligibleMariliaRecordsOfTheLastTwelveMonths(): void
    {
        $this->importReference();
        $rows = [
            '1;SINISTRO FATAL;10/09/2026;10:00;VIAS URBANAS;-22,2188;-49,95;MARILIA',
            '1;SINISTRO FATAL;10/09/2026;10:00;VIAS URBANAS;-22,2188;-49,95;MARILIA',           // id repetido
            '2;NOTIFICACAO;10/09/2026;10:00;VIAS URBANAS;-22,2188;-49,95;MARILIA',
            '3;SINISTRO NAO FATAL;10/09/2026;10:00;RODOVIAS;-22,2188;-49,95;MARILIA',
            '4;SINISTRO NAO FATAL;10/09/2026;10:00;VIAS URBANAS;;;MARILIA',
            '5;SINISTRO NAO FATAL;10/09/2026;10:00;VIAS URBANAS;-22,30;-49,95;MARILIA',          // fora do retângulo
            '6;SINISTRO NAO FATAL;10/09/2024;10:00;VIAS URBANAS;-22,2188;-49,95;MARILIA',        // antes dos 12 meses
            '7;SINISTRO NAO FATAL;10/09/2026;10:00;VIAS URBANAS;-22,2188;-49,95;BAURU',
            "8;SINISTRO NAO FATAL;11/09/2026;10:00;VIAS URBANAS;-22,2188;-49,95;MAR\xCDLIA",
        ];
        $this->today->urbanUpdater()->ingest($this->zip($rows), 'teste');

        $audit = $this->services->urbanRepository()->latestSnapshot()['audit'];
        self::assertEquals(
            ['marilia_records' => 8, 'duplicate_id' => 1, 'notification_or_other' => 1, 'nonurban_or_unknown' => 1,
                'missing_or_invalid_gps' => 1, 'outside_study_rectangle' => 1, 'before_window' => 1, 'eligible' => 2],
            array_intersect_key($audit, array_flip(['marilia_records', 'duplicate_id', 'notification_or_other', 'nonurban_or_unknown', 'missing_or_invalid_gps', 'outside_study_rectangle', 'before_window', 'eligible'])),
        );
        self::assertSame(2, $this->services->urbanRepository()->cellCounts($this->services->urbanRepository()->latestSnapshot()['id'], '2026-09-01', '2026-09-30')[27]);
        self::assertStringContainsString('não cobrem as 52 semanas', $this->today->urbanUpdater()->forecast('2027-01-04')['message'], 'dados que não chegam ao corte da semana: não inventa previsão');
    }

    public function testBrokenFilesAreRefusedWithoutWritingAndDownloadFailureKeepsTheForecastStep(): void
    {
        $this->assertRefusal(fn() => $this->today->urbanUpdater()->ingest($this->zip(['x']), 'teste'), RuleViolation::class, 'execução de referência');
        $this->importReference();
        $this->assertRefusal(fn() => $this->today->urbanUpdater()->ingest($this->zip([], 'outro.csv'), 'teste'), ValidationFailed::class, 'Nenhum sinistros_');
        $this->assertRefusal(fn() => $this->today->urbanUpdater()->ingest($this->zip(['1;2'], 'sinistros_2025-2026.csv', 'id_sinistro;municipio'), 'teste'), ValidationFailed::class, 'sem as colunas');
        $notZip = $this->temp('.zip');
        file_put_contents($notZip, 'isto não é um zip');
        $this->assertRefusal(fn() => $this->today->urbanUpdater()->ingest($notZip, 'teste'), ValidationFailed::class, 'ZIP válido');
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_SNAPSHOT'));

        $update = $this->today->urbanUpdater()->update('https://127.0.0.1:9/dados_infosiga.zip', sys_get_temp_dir());
        self::assertFalse($update['ok']);
        self::assertStringContainsString('Não foi possível baixar', $update['messages'][0]);
        self::assertStringContainsString('o primeiro download', $update['messages'][1], 'a etapa semanal roda e explica por que não calculou');
    }

    public function testPoissonFormulaMatchesThePythonParityVectors(): void
    {
        $payload = json_decode((string) file_get_contents(self::ROOT . '/ml/marilia-setores/runs/primeiro-treino-verificado/vetores_paridade.json'), true);
        foreach ($payload['vectors'] as $v) {
            self::assertEqualsWithDelta($v['expected'], SectorForecast::poisson($payload['model'], $v['x']), 1e-10 * max(1.0, abs($v['expected'])));
        }
        self::assertCount(20, $payload['vectors']);
    }

    private function importReference(): int
    {
        return $this->today->urbanImporter()->import(
            self::ROOT . '/' . UrbanImporter::DEFAULT_RUN,
            self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS,
            self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST,
            self::ROOT . '/' . UrbanImporter::DEFAULT_GRID,
        )['id'];
    }

    /** ZIP no formato do Infosiga com um registro no centro do quadrado para cada contagem real dos 12 meses. */
    private function zipFromGrid(): string
    {
        $bounds = ['south' => -22.28, 'north' => -22.14, 'west' => -50.02, 'east' => -49.86];
        $rows = [];
        $handle = fopen(self::ROOT . '/' . UrbanImporter::DEFAULT_GRID, 'r');
        fgetcsv($handle, null, ',', '"', '');
        while (($r = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($r[0] < '2025-08-02') {
                continue;
            }
            $box = SectorForecast::cellBox($bounds, (int) $r[1]);
            $date = implode('/', array_reverse(explode('-', $r[0])));
            for ($i = 0; $i < (int) $r[2]; $i++) {
                $rows[] = sprintf(
                    '%d;SINISTRO NAO FATAL;%s;12:00;VIAS URBANAS;%s;%s;MARILIA',
                    count($rows) + 1,
                    $date,
                    str_replace('.', ',', (string) (($box['south'] + $box['north']) / 2)),
                    str_replace('.', ',', (string) (($box['west'] + $box['east']) / 2)),
                );
            }
        }
        fclose($handle);

        return $this->zip($rows);
    }

    /** @param list<string> $rows */
    private function zip(array $rows, string $member = 'sinistros_2025-2026.csv', string $header = self::HEADER): string
    {
        $path = $this->temp('.zip');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($member, implode("\r\n", [$header, ...$rows]) . "\r\n");
        $zip->close();

        return $path;
    }

    private function temp(string $suffix): string
    {
        return $this->files[] = sys_get_temp_dir() . '/fiscaliza-infosiga-' . bin2hex(random_bytes(6)) . $suffix;
    }

    /** @param class-string<\Throwable> $class */
    private function assertRefusal(callable $action, string $class, string $message): void
    {
        try {
            $action();
            self::fail("Era esperada a recusa: {$message}");
        } catch (\Throwable $e) {
            self::assertInstanceOf($class, $e, $e->getMessage());
            self::assertStringContainsString($message, $e instanceof ValidationFailed ? implode(' ', $e->errors) . $e->getMessage() : $e->getMessage());
        }
    }
}
