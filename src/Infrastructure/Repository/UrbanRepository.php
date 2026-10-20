<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/**
 * SQL das execuções importadas do planejamento por setores (somente leitura e inserção: uma execução importada não
 * muda). Estimativas em DECFLOAT(34) entram pelo texto mais curto do double e voltam como float idêntico.
 */
final class UrbanRepository
{
    private const RUN_COLUMNS = 'ID, BUNDLE_SHA256, PROTOCOL_VERSION, AS_OF, UNTIL_DATE, DATA_REFERENCE_THROUGH, GENERATED_AT, SOURCE_SNAPSHOT_SHA256,
        SELECTED_METHOD, CANDIDATE_METHOD, TEST_RELATIVE_GAIN, MODEL_ISSUED_AT, NOTICE, EVALUATION, PROVENANCE, FILES, IMPORTED_AT';

    public function __construct(private readonly Database $db) {}

    public function findByBundle(string $sha256): ?int
    {
        $id = $this->db->value('SELECT ID FROM URBAN_RUN WHERE BUNDLE_SHA256 = ?', [$sha256]);

        return $id === null ? null : (int) $id;
    }

    /**
     * Grava a execução validada por UrbanImporter::read (chamar dentro de uma transação).
     *
     * @param array<string, mixed> $bundle
     */
    public function insertRun(array $bundle, string $importedAt): int
    {
        $r = $bundle['run'];
        $id = $this->db->insert(
            'INSERT INTO URBAN_RUN (BUNDLE_SHA256, PROTOCOL_VERSION, AS_OF, UNTIL_DATE, DATA_REFERENCE_THROUGH, GENERATED_AT, SOURCE_SNAPSHOT_SHA256,
                SELECTED_METHOD, CANDIDATE_METHOD, TEST_RELATIVE_GAIN, MODEL_ISSUED_AT, NOTICE, EVALUATION, MODEL, PROVENANCE, FILES, IMPORTED_AT)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$bundle['sha256'], $r['protocol_version'], $r['as_of'], $r['until'], $r['data_reference_through'], $r['generated_at'], $r['source_snapshot_sha256'],
                $r['selected_method'], $r['candidate_method'], self::dec($r['test_relative_gain']), $r['model_issued_at'], $r['notice'],
                $bundle['evaluation_json'], $bundle['model_json'], $bundle['provenance_json'], json_encode($bundle['files'], JSON_THROW_ON_ERROR), $importedAt],
        );
        foreach ($bundle['sectors'] as $sectorId => $s) {
            $this->db->execute(
                'INSERT INTO URBAN_SECTOR (RUN_ID, SECTOR_ID, NAME, SOUTH, NORTH, WEST, EAST, CENTER_LAT, CENTER_LNG) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $sectorId, $s['name'], ...array_map(self::coordinate(...), [$s['south'], $s['north'], $s['west'], $s['east'], $s['center_lat'], $s['center_lng']])],
            );
        }
        foreach ($bundle['forecast'] as $sectorId => $f) {
            $this->db->execute(
                'INSERT INTO URBAN_FORECAST (RUN_ID, SECTOR_ID, RANK_NO, PRIORITY, EXPECTED_COUNT, EXPECTED_ML, EXPECTED_BASELINE, HISTORY_START, HISTORY_THROUGH,
                    COUNT_13W, COUNT_52W, DATA_AGE_DAYS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $sectorId, $f['rank'], $f['priority'], self::dec($f['expected_count']), self::dec($f['expected_ml']), self::dec($f['expected_baseline']),
                    $f['history_start'], $f['history_through'], $f['count_13w'], $f['count_52w'], $f['data_age_days']],
            );
        }
        foreach ($bundle['replay'] as $r) {
            $this->db->execute(
                'INSERT INTO URBAN_REPLAY (RUN_ID, WEEK_FROM, WEEK_UNTIL, SECTOR_ID, EXPECTED_ML, EXPECTED_BASELINE, OBSERVED_COUNT, HISTORY_START, HISTORY_THROUGH,
                    COUNT_13W, COUNT_52W, DATA_AGE_DAYS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $r['week_from'], $r['week_until'], $r['sector_id'], self::dec($r['expected_ml']), self::dec($r['expected_baseline']), $r['observed_count'],
                    $r['history_start'], $r['history_through'], $r['count_13w'], $r['count_52w'], $r['data_age_days']],
            );
        }

        return $id;
    }

    /** @param list<array<string, mixed>> $cells quadrados de UrbanImporter::cells */
    public function insertCells(int $runId, array $cells): void
    {
        foreach ($cells as $c) {
            $this->db->execute(
                'INSERT INTO URBAN_CELL (RUN_ID, CELL_NO, SECTOR_ID, SOUTH, NORTH, WEST, EAST, COUNT_13W, COUNT_52W, EXPECTED, RANK_NO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$runId, $c['cell'], $c['sector_id'], ...array_map(self::coordinate(...), [$c['south'], $c['north'], $c['west'], $c['east']]),
                    $c['count_13w'], $c['count_52w'], self::dec($c['expected']), $c['rank']],
            );
        }
    }

    public function cellCount(int $runId): int
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM URBAN_CELL WHERE RUN_ID = ?', [$runId]);
    }

    /**
     * Quadrados de maior estimativa, com a parcela dos registros de 52 semanas da área de estudo.
     *
     * @return list<array<string, mixed>>
     */
    public function topCells(int $runId, int $limit): array
    {
        return array_map(
            static fn(array $r): array => self::numbers($r, ['south', 'north', 'west', 'east', 'expected', 'share']),
            $this->db->all(
                'SELECT c.CELL_NO, c.SECTOR_ID, s.NAME AS SECTOR_NAME, c.SOUTH, c.NORTH, c.WEST, c.EAST, c.COUNT_13W, c.COUNT_52W, c.EXPECTED, c.RANK_NO,
                        CAST(c.COUNT_52W AS DOUBLE PRECISION) / NULLIF((SELECT SUM(t.COUNT_52W) FROM URBAN_CELL t WHERE t.RUN_ID = c.RUN_ID), 0) AS SHARE
                 FROM URBAN_CELL c JOIN URBAN_SECTOR s ON s.RUN_ID = c.RUN_ID AND s.SECTOR_ID = c.SECTOR_ID
                 WHERE c.RUN_ID = ? AND c.RANK_NO <= ? ORDER BY c.RANK_NO',
                [$runId, $limit],
            ),
        );
    }

    /**
     * Execução que a tela usa: a de período mais recente (empate: a importada por último).
     *
     * @return array<string, mixed>|null
     */
    public function latestRun(): ?array
    {
        return self::hydrateRun($this->db->one('SELECT FIRST 1 ' . self::RUN_COLUMNS . ' FROM URBAN_RUN ORDER BY AS_OF DESC, ID DESC'));
    }

    /**
     * Setores da execução com a previsão e as evidências, na ordem importada.
     *
     * @return array<string, array<string, mixed>> código do setor => linha
     */
    public function sectors(int $runId): array
    {
        $rows = $this->db->all(
            'SELECT s.SECTOR_ID, s.NAME, s.SOUTH, s.NORTH, s.WEST, s.EAST, s.CENTER_LAT, s.CENTER_LNG, f.RANK_NO, f.PRIORITY, f.EXPECTED_COUNT, f.EXPECTED_ML,
                    f.EXPECTED_BASELINE, f.HISTORY_START, f.HISTORY_THROUGH, f.COUNT_13W, f.COUNT_52W, f.DATA_AGE_DAYS
             FROM URBAN_SECTOR s JOIN URBAN_FORECAST f ON f.RUN_ID = s.RUN_ID AND f.SECTOR_ID = s.SECTOR_ID WHERE s.RUN_ID = ? ORDER BY f.RANK_NO',
            [$runId],
        );
        $sectors = [];
        foreach ($rows as $row) {
            $row = self::numbers($row, ['south', 'north', 'west', 'east', 'center_lat', 'center_lng', 'expected_count', 'expected_ml', 'expected_baseline']);
            $sectors[$row['sector_id']] = $row;
        }

        return $sectors;
    }

    /**
     * Execução de referência: a mais recente que tem retrospecto (importada do treino). As semanais automáticas copiam
     * dela o método, o modelo congelado e a avaliação; o retrospecto da tela também vem dela.
     *
     * @return array<string, mixed>|null
     */
    public function referenceRun(): ?array
    {
        $run = self::hydrateRun($this->db->one('SELECT FIRST 1 ' . self::RUN_COLUMNS . ' FROM URBAN_RUN r
            WHERE EXISTS (SELECT 1 FROM URBAN_REPLAY p WHERE p.RUN_ID = r.ID) ORDER BY AS_OF DESC, ID DESC'));
        if ($run !== null) {
            $run['model'] = json_decode((string) $this->db->value('SELECT MODEL FROM URBAN_RUN WHERE ID = ?', [$run['id']]), true, 32, JSON_THROW_ON_ERROR);
        }

        return $run;
    }

    public function findSnapshot(string $sha256): ?int
    {
        $id = $this->db->value('SELECT ID FROM URBAN_SNAPSHOT WHERE SHA256 = ?', [$sha256]);

        return $id === null ? null : (int) $id;
    }

    /** @return array<string, mixed>|null */
    public function latestSnapshot(): ?array
    {
        $row = $this->db->one('SELECT FIRST 1 ID, SHA256, SOURCE_URL, DOWNLOADED_AT, BYTES, DATA_FROM, DATA_THROUGH, ELIGIBLE, AUDIT FROM URBAN_SNAPSHOT ORDER BY ID DESC');
        if ($row === null) {
            return null;
        }
        $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        $row['id'] = (int) $row['id'];
        $row['audit'] = json_decode((string) $row['audit'], true, 8, JSON_THROW_ON_ERROR);

        return $row;
    }

    /**
     * Grava um snapshot mensal e suas contagens diárias por quadrado (chamar dentro de uma transação).
     *
     * @param array<string, mixed> $snapshot
     * @param array<string, array<int, int>> $daily dia => [quadrado => contagem]
     */
    public function insertSnapshot(array $snapshot, array $daily): int
    {
        $id = $this->db->insert(
            'INSERT INTO URBAN_SNAPSHOT (SHA256, SOURCE_URL, DOWNLOADED_AT, BYTES, DATA_FROM, DATA_THROUGH, ELIGIBLE, AUDIT) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$snapshot['sha256'], $snapshot['source_url'], $snapshot['downloaded_at'], $snapshot['bytes'], $snapshot['data_from'], $snapshot['data_through'],
                $snapshot['eligible'], json_encode($snapshot['audit'], JSON_THROW_ON_ERROR)],
        );
        foreach ($daily as $day => $cells) {
            foreach ($cells as $cell => $count) {
                $this->db->execute('INSERT INTO URBAN_DAILY (SNAPSHOT_ID, DAY_DATE, CELL_NO, COUNT_N) VALUES (?, ?, ?, ?)', [$id, $day, $cell, $count]);
            }
        }

        return $id;
    }

    /**
     * Registros por quadrado entre duas datas (inclusivas) de um snapshot; quadrados sem registro valem zero.
     *
     * @return array<int, int>
     */
    public function cellCounts(int $snapshotId, string $from, string $through): array
    {
        $counts = array_fill(0, 64, 0);
        foreach ($this->db->all('SELECT CELL_NO, SUM(COUNT_N) AS N FROM URBAN_DAILY WHERE SNAPSHOT_ID = ? AND DAY_DATE BETWEEN ? AND ? GROUP BY CELL_NO', [$snapshotId, $from, $through]) as $row) {
            $counts[(int) $row['cell_no']] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * Registros por mês de um snapshot (para comparar revisões entre downloads).
     *
     * @return array<string, int> AAAA-MM => registros
     */
    public function monthlyTotals(int $snapshotId): array
    {
        $totals = [];
        foreach ($this->db->all('SELECT DAY_DATE, SUM(COUNT_N) AS N FROM URBAN_DAILY WHERE SNAPSHOT_ID = ? GROUP BY DAY_DATE', [$snapshotId]) as $row) {
            $month = substr((string) $row['day_date'], 0, 7);
            $totals[$month] = ($totals[$month] ?? 0) + (int) $row['n'];
        }

        return $totals;
    }

    /** @return list<string> início de cada semana do retrospecto, em ordem */
    public function replayWeeks(int $runId): array
    {
        return array_map(strval(...), array_column($this->db->all('SELECT DISTINCT WEEK_FROM FROM URBAN_REPLAY WHERE RUN_ID = ? ORDER BY WEEK_FROM', [$runId]), 'week_from'));
    }

    /**
     * Linhas do retrospecto (todas, ou só de uma semana).
     *
     * @return list<array<string, mixed>>
     */
    public function replay(int $runId, ?string $week = null): array
    {
        $sql = 'SELECT WEEK_FROM, WEEK_UNTIL, SECTOR_ID, EXPECTED_ML, EXPECTED_BASELINE, OBSERVED_COUNT, HISTORY_START, HISTORY_THROUGH, COUNT_13W, COUNT_52W, DATA_AGE_DAYS
                FROM URBAN_REPLAY WHERE RUN_ID = ?' . ($week === null ? '' : ' AND WEEK_FROM = ?') . ' ORDER BY WEEK_FROM, SECTOR_ID';

        return array_map(
            static fn(array $r): array => self::numbers($r, ['expected_ml', 'expected_baseline']),
            $this->db->all($sql, $week === null ? [$runId] : [$runId, $week]),
        );
    }

    /** Texto decimal mais curto que reproduz o double (serialize_precision = -1). */
    private static function dec(float $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private static function coordinate(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $columns
     * @return array<string, mixed>
     */
    private static function numbers(array $row, array $columns): array
    {
        $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        foreach ($columns as $c) {
            $row[$c] = (float) $row[$c];
        }
        foreach (['rank_no', 'count_13w', 'count_52w', 'data_age_days', 'observed_count', 'cell_no'] as $c) {
            if (isset($row[$c])) {
                $row[$c] = (int) $row[$c];
            }
        }

        return $row;
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private static function hydrateRun(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        $row['id'] = (int) $row['id'];
        $row['test_relative_gain'] = (float) $row['test_relative_gain'];
        foreach (['evaluation', 'provenance', 'files'] as $c) {
            $row[$c] = json_decode((string) $row[$c], true, 32, JSON_THROW_ON_ERROR);
        }

        return $row;
    }
}
