<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/** SQL das ordens de manutenção. Ordens nunca são apagadas: conclusão e cancelamento preservam o histórico. */
final class MaintenanceRepository
{
    private const SELECT = 'SELECT m.*, e.CODE AS EQUIPMENT_CODE, e.TYPE_CODE, e.STATUS AS EQUIPMENT_STATUS, e.STREET, e.ADDRESS_REF,
            r.NAME AS REGION_NAME, uo.DISPLAY_NAME AS OPENED_BY_NAME, uc.DISPLAY_NAME AS CLOSED_BY_NAME
        FROM MAINTENANCE_ORDER m
        JOIN EQUIPMENT e ON e.ID = m.EQUIPMENT_ID
        LEFT JOIN REGION r ON r.ID = e.REGION_ID
        LEFT JOIN APP_USER uo ON uo.ID = m.OPENED_BY
        LEFT JOIN APP_USER uc ON uc.ID = m.CLOSED_BY';

    public function __construct(private readonly Database $db) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::trim($this->db->one(self::SELECT . ' WHERE m.ID = ?', [$id]));
    }

    /** @return array<string, mixed>|null */
    public function lock(int $id): ?array
    {
        return self::trim($this->db->one('SELECT * FROM MAINTENANCE_ORDER WHERE ID = ? WITH LOCK', [$id]));
    }

    /**
     * @param array{equipment_id: int, kind: string, priority: string, due_date: ?string, reason: string, source: string, opened_at: string, opened_by: ?int, state?: string} $o
     */
    public function insert(array $o): int
    {
        return $this->db->insert(
            'INSERT INTO MAINTENANCE_ORDER (EQUIPMENT_ID, KIND, PRIORITY, STATE, DUE_DATE, REASON, SOURCE, OPENED_AT, OPENED_BY) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$o['equipment_id'], $o['kind'], $o['priority'], $o['state'] ?? 'ABERTA', $o['due_date'], $o['reason'], $o['source'], $o['opened_at'], $o['opened_by']],
        );
    }

    public function markStarted(int $id, string $at): void
    {
        $this->db->execute("UPDATE MAINTENANCE_ORDER SET STATE = 'EM_EXECUCAO', STARTED_AT = ?, VERSION_NO = VERSION_NO + 1 WHERE ID = ?", [$at, $id]);
    }

    public function markClosed(int $id, string $state, string $at, ?int $by, string $resolution, ?string $resultStatus): void
    {
        $this->db->execute(
            'UPDATE MAINTENANCE_ORDER SET STATE = ?, CLOSED_AT = ?, CLOSED_BY = ?, RESOLUTION = ?, RESULT_STATUS = ?, VERSION_NO = VERSION_NO + 1 WHERE ID = ?',
            [$state, $at, $by, $resolution, $resultStatus, $id],
        );
    }

    /**
     * Ordens em aberto (ABERTA ou EM_EXECUCAO), opcionalmente restritas a equipamentos.
     *
     * @param list<int>|null $equipmentIds
     * @return list<array<string, mixed>>
     */
    public function open(?array $equipmentIds = null): array
    {
        $sql = self::SELECT . " WHERE m.STATE IN ('ABERTA', 'EM_EXECUCAO') AND e.ARCHIVED_AT IS NULL";
        $params = [];
        if ($equipmentIds !== null) {
            if ($equipmentIds === []) {
                return [];
            }
            $sql .= ' AND m.EQUIPMENT_ID IN (' . Database::placeholders(count($equipmentIds)) . ')';
            $params = $equipmentIds;
        }

        return array_map(self::trim(...), $this->db->all($sql . ' ORDER BY m.DUE_DATE NULLS FIRST, m.ID', $params));
    }

    /** @return list<array<string, mixed>> */
    public function forEquipment(int $equipmentId, int $limit = 30): array
    {
        return array_map(self::trim(...), $this->db->all(self::SELECT . ' WHERE m.EQUIPMENT_ID = ? ORDER BY m.OPENED_AT DESC ROWS ?', [$equipmentId, $limit]));
    }

    /** @return list<array<string, mixed>> */
    public function recentlyClosed(int $limit = 20): array
    {
        return array_map(self::trim(...), $this->db->all(self::SELECT . " WHERE m.STATE IN ('CONCLUIDA', 'CANCELADA') ORDER BY m.CLOSED_AT DESC ROWS ?", [$limit]));
    }

    public function equipmentOf(int $orderId): ?int
    {
        $id = $this->db->value('SELECT EQUIPMENT_ID FROM MAINTENANCE_ORDER WHERE ID = ?', [$orderId]);

        return $id === null ? null : (int) $id;
    }

    /**
     * Ordens ABERTA/EM_EXECUCAO de um equipamento, inclusive arquivado (para as regras de início e arquivamento).
     *
     * @return list<array<string, mixed>>
     */
    public function openFor(int $equipmentId): array
    {
        return array_map(self::trim(...), $this->db->all(
            "SELECT ID, KIND, PRIORITY, STATE, DUE_DATE, REASON FROM MAINTENANCE_ORDER WHERE EQUIPMENT_ID = ? AND STATE IN ('ABERTA', 'EM_EXECUCAO') ORDER BY ID",
            [$equipmentId],
        ));
    }

    public function hasOpenOfKind(int $equipmentId, string $kind): bool
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM MAINTENANCE_ORDER WHERE EQUIPMENT_ID = ? AND KIND = ? AND STATE IN ('ABERTA', 'EM_EXECUCAO')", [$equipmentId, $kind]) > 0;
    }

    /**
     * Todas as ordens (para a análise preventiva), com instantes em UTC.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(self::trim(...), $this->db->all('SELECT ID, EQUIPMENT_ID, KIND, PRIORITY, STATE, DUE_DATE, OPENED_AT, STARTED_AT, CLOSED_AT, RESULT_STATUS FROM MAINTENANCE_ORDER ORDER BY OPENED_AT'));
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private static function trim(?array $row): ?array
    {
        return $row === null ? null : array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
    }
}
