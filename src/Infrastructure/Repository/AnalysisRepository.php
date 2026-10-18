<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/** Execuções da análise preventiva e seus resultados por equipamento. */
final class AnalysisRepository
{
    public function __construct(private readonly Database $db) {}

    /**
     * @param array<string, mixed> $run
     */
    public function insertRun(array $run): int
    {
        return $this->db->insert(
            'INSERT INTO ANALYSIS_RUN (METHOD_VERSION, AS_OF, HORIZON_DAYS, DATA_FROM, DATA_TO, EVENTS_USED, PARAMS, EVALUATION, CREATED_AT, CREATED_BY) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$run['method'], $run['as_of'], $run['horizon'], $run['data_from'], $run['data_to'], $run['events_used'],
                json_encode($run['params'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), json_encode($run['evaluation'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $run['created_at'], $run['created_by']],
        );
    }

    /** @param array<string, mixed> $r */
    public function insertResult(int $runId, array $r): void
    {
        $this->db->execute(
            'INSERT INTO ANALYSIS_RESULT (RUN_ID, EQUIPMENT_ID, ATTENTION, SCORE, FACTORS, RECOMMENDATION, HISTORY_DAYS) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$runId, $r['equipment_id'], $r['attention'], $r['score'], json_encode(['fatores' => $r['factors'], 'administrativo' => $r['admin_flags']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $r['recommendation'], $r['history_days']],
        );
    }

    /** @return array<string, mixed>|null */
    public function latestRun(): ?array
    {
        $row = $this->db->one('SELECT r.*, u.DISPLAY_NAME AS CREATED_BY_NAME FROM ANALYSIS_RUN r LEFT JOIN APP_USER u ON u.ID = r.CREATED_BY ORDER BY r.ID DESC ROWS 1');
        if ($row === null) {
            return null;
        }
        $row['params'] = json_decode((string) $row['params'], true) ?: [];
        $row['evaluation'] = json_decode((string) $row['evaluation'], true) ?: [];

        return $row;
    }

    /** @return list<array<string, mixed>> */
    public function results(int $runId): array
    {
        $rows = $this->db->all(
            "SELECT ar.*, e.CODE, e.TYPE_CODE, e.STATUS, e.MANUFACTURER, e.MODEL, r.NAME AS REGION_NAME
             FROM ANALYSIS_RESULT ar JOIN EQUIPMENT e ON e.ID = ar.EQUIPMENT_ID LEFT JOIN REGION r ON r.ID = e.REGION_ID
             WHERE ar.RUN_ID = ?
             ORDER BY CASE ar.ATTENTION WHEN 'ALTA' THEN 0 WHEN 'MODERADA' THEN 1 WHEN 'CORRETIVA' THEN 2 WHEN 'INSUFICIENTE' THEN 3 ELSE 4 END, ar.SCORE DESC NULLS LAST, e.CODE",
            [$runId],
        );

        return array_map(static function (array $row): array {
            $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
            $decoded = json_decode((string) $row['factors'], true) ?: [];
            $row['factors'] = $decoded['fatores'] ?? [];
            $row['admin_flags'] = $decoded['administrativo'] ?? [];

            return $row;
        }, $rows);
    }

    /** @return array<string, mixed>|null resultado mais recente para um equipamento */
    public function latestForEquipment(int $equipmentId): ?array
    {
        $row = $this->db->one(
            'SELECT ar.*, r.AS_OF FROM ANALYSIS_RESULT ar JOIN ANALYSIS_RUN r ON r.ID = ar.RUN_ID WHERE ar.EQUIPMENT_ID = ? ORDER BY ar.RUN_ID DESC ROWS 1',
            [$equipmentId],
        );
        if ($row === null) {
            return null;
        }
        $decoded = json_decode((string) $row['factors'], true) ?: [];
        $row['factors'] = $decoded['fatores'] ?? [];
        $row['admin_flags'] = $decoded['administrativo'] ?? [];
        $row['attention'] = rtrim((string) $row['attention']);

        return $row;
    }
}
