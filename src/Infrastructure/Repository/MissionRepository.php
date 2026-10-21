<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/**
 * SQL das missões fictícias de reforço. A reserva do agente é a própria missão em CONFIRMADA (índice único parcial
 * UX_URBAN_MISSION_ACTIVE); a tabela AGENT não é alterada, então o simulador nunca apaga uma reserva.
 */
final class MissionRepository
{
    private const SELECT = 'SELECT m.*, g.CODE AS AGENT_CODE, g.CALL_SIGN AS AGENT_CALL_SIGN, s.NAME AS SECTOR_NAME,
            uc.DISPLAY_NAME AS CONFIRMED_BY_NAME, ux.DISPLAY_NAME AS CLOSED_BY_NAME
        FROM URBAN_MISSION m JOIN AGENT g ON g.ID = m.AGENT_ID JOIN URBAN_SECTOR s ON s.RUN_ID = m.RUN_ID AND s.SECTOR_ID = m.SECTOR_ID
        JOIN APP_USER uc ON uc.ID = m.CONFIRMED_BY LEFT JOIN APP_USER ux ON ux.ID = m.CLOSED_BY';

    public function __construct(private readonly Database $db) {}

    /** @param array<string, mixed> $m */
    public function insert(array $m): int
    {
        return $this->db->insert(
            'INSERT INTO URBAN_MISSION (RUN_ID, SECTOR_ID, PERIOD_FROM, PERIOD_UNTIL, METHOD, EXPECTED_COUNT, RANK_NO, AGENT_ID, SELECTION, REASON,
                JUSTIFICATION, SUPPORT_POINT, NOTE, STATE, CONFIRMED_AT, CONFIRMED_BY, CELL_NO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$m['run_id'], $m['sector_id'], $m['period_from'], $m['period_until'], $m['method'], json_encode((float) $m['expected_count'], JSON_THROW_ON_ERROR),
                $m['rank_no'], $m['agent_id'], $m['selection'], $m['reason'], $m['justification'], $m['support_point'], $m['note'], 'CONFIRMADA',
                $m['confirmed_at'], $m['confirmed_by'], $m['cell_no'] ?? null],
        );
    }

    /** @return array<string, mixed>|null */
    public function lock(int $id): ?array
    {
        return self::hydrate($this->db->one('SELECT * FROM URBAN_MISSION WHERE ID = ? WITH LOCK', [$id]));
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::hydrate($this->db->one(self::SELECT . ' WHERE m.ID = ?', [$id]));
    }

    /** Encerra uma missão vigente; false se ela já tinha sido concluída ou cancelada. */
    public function close(int $id, string $state, string $at, int $by, string $reason): bool
    {
        return $this->db->execute(
            "UPDATE URBAN_MISSION SET STATE = ?, CLOSED_AT = ?, CLOSED_BY = ?, CLOSE_REASON = ? WHERE ID = ? AND STATE = 'CONFIRMADA'",
            [$state, $at, $by, $reason, $id],
        ) === 1;
    }

    /** Missão vigente do agente, se houver. */
    public function activeForAgent(int $agentId): ?int
    {
        $id = $this->db->value("SELECT ID FROM URBAN_MISSION WHERE AGENT_ID = ? AND STATE = 'CONFIRMADA'", [$agentId]);

        return $id === null ? null : (int) $id;
    }

    /** @return array<int, int> agente => missão vigente */
    public function activeByAgent(): array
    {
        $rows = $this->db->all("SELECT AGENT_ID, ID FROM URBAN_MISSION WHERE STATE = 'CONFIRMADA'");

        return array_combine(array_map(intval(...), array_column($rows, 'agent_id')), array_map(intval(...), array_column($rows, 'id')));
    }

    /**
     * Vigentes primeiro, depois as encerradas mais recentes.
     *
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        return array_map(self::hydrate(...), $this->db->all(
            self::SELECT . " ORDER BY IIF(m.STATE = 'CONFIRMADA', 0, 1), COALESCE(m.CLOSED_AT, m.CONFIRMED_AT) DESC, m.ID DESC ROWS ?",
            [$limit],
        ));
    }

    public function log(int $missionId, ?string $from, string $to, int $actor, string $at, ?string $note): void
    {
        $this->db->execute(
            'INSERT INTO URBAN_MISSION_LOG (MISSION_ID, FROM_STATE, TO_STATE, ACTOR_USER_ID, AT_TIME, NOTE) VALUES (?, ?, ?, ?, ?, ?)',
            [$missionId, $from, $to, $actor, $at, $note],
        );
    }

    /**
     * Histórico das transições, por missão.
     *
     * @param list<int> $missionIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function logs(array $missionIds): array
    {
        if ($missionIds === []) {
            return [];
        }
        $rows = $this->db->all(
            'SELECT l.MISSION_ID, l.FROM_STATE, l.TO_STATE, l.AT_TIME, l.NOTE, u.DISPLAY_NAME AS ACTOR_NAME FROM URBAN_MISSION_LOG l
             JOIN APP_USER u ON u.ID = l.ACTOR_USER_ID WHERE l.MISSION_ID IN (' . Database::placeholders(count($missionIds)) . ') ORDER BY l.MISSION_ID, l.ID',
            $missionIds,
        );
        $logs = [];
        foreach ($rows as $row) {
            $logs[(int) $row['mission_id']][] = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        }

        return $logs;
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private static function hydrate(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        $row['id'] = (int) $row['id'];
        $row['agent_id'] = (int) $row['agent_id'];
        $row['expected_count'] = (float) $row['expected_count'];
        $row['cell_no'] = isset($row['cell_no']) ? (int) $row['cell_no'] : null;

        return $row;
    }
}
