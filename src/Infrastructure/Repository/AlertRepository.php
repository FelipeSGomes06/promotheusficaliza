<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;

/** SQL de alertas. OPEN_KEY + índice único = no máximo um alerta não resolvido por causa. */
final class AlertRepository
{
    private const SELECT = "SELECT al.*, u.DISPLAY_NAME AS ACK_BY_NAME, ua.DISPLAY_NAME AS ASSIGNEE_NAME, ua.ACTIVE AS ASSIGNEE_ACTIVE,
            COALESCE(e.CODE, g.CODE) AS RESOURCE_CODE, COALESCE(e.TYPE_CODE, g.ROLE) AS RESOURCE_TYPE, COALESCE(re.NAME, rg.NAME) AS REGION_NAME
        FROM ALERT al
        LEFT JOIN APP_USER u ON u.ID = al.ACK_BY
        LEFT JOIN APP_USER ua ON ua.ID = al.ASSIGNEE_ID
        LEFT JOIN EQUIPMENT e ON al.RESOURCE_KIND = 'EQUIPAMENTO' AND e.ID = al.RESOURCE_ID
        LEFT JOIN REGION re ON re.ID = e.REGION_ID
        LEFT JOIN AGENT g ON al.RESOURCE_KIND = 'AGENTE' AND g.ID = al.RESOURCE_ID
        LEFT JOIN REGION rg ON rg.ID = g.REGION_ID";

    public function __construct(private readonly Database $db) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::trim($this->db->one(self::SELECT . ' WHERE al.ID = ?', [$id]));
    }

    /** @return array<string, mixed>|null */
    public function lock(int $id): ?array
    {
        return self::trim($this->db->one('SELECT * FROM ALERT WHERE ID = ? WITH LOCK', [$id]));
    }

    /**
     * Alertas não resolvidos (ABERTO ou RECONHECIDO), por gravidade e antiguidade.
     *
     * @return list<array<string, mixed>>
     */
    public function unresolved(): array
    {
        return array_map(self::trim(...), $this->db->all(
            self::SELECT . " WHERE al.STATE <> 'RESOLVIDO'
             ORDER BY CASE al.SEVERITY WHEN 'CRITICA' THEN 0 WHEN 'ALTA' THEN 1 ELSE 2 END, al.OPENED_AT",
        ));
    }

    /**
     * Pendentes do recurso (todos, por mais antigos que sejam) seguidos dos encerrados mais recentes: o limite vale só
     * para o histórico encerrado.
     *
     * @return list<array<string, mixed>>
     */
    public function forResource(string $kind, int $id, int $limit = 20): array
    {
        return [...$this->unresolvedFor($kind, $id), ...$this->resolvedFor($kind, $id, $limit)];
    }

    /**
     * @return list<array<string, mixed>>
     * @phpstan-impure
     */
    public function unresolvedFor(string $kind, int $id): array
    {
        return array_map(self::trim(...), $this->db->all(self::SELECT . " WHERE al.RESOURCE_KIND = ? AND al.RESOURCE_ID = ? AND al.STATE <> 'RESOLVIDO' ORDER BY al.OPENED_AT", [$kind, $id]));
    }

    /** @return list<array<string, mixed>> */
    public function resolvedFor(string $kind, int $id, int $limit = 20): array
    {
        return array_map(self::trim(...), $this->db->all(self::SELECT . " WHERE al.RESOURCE_KIND = ? AND al.RESOURCE_ID = ? AND al.STATE = 'RESOLVIDO' ORDER BY al.RESOLVED_AT DESC, al.ID DESC ROWS ?", [$kind, $id, $limit]));
    }

    public function countResolvedFor(string $kind, int $id): int
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM ALERT WHERE RESOURCE_KIND = ? AND RESOURCE_ID = ? AND STATE = 'RESOLVIDO'", [$kind, $id]);
    }

    /** @return array<string, int> OPEN_KEY => ID dos alertas não resolvidos */
    public function openKeys(): array
    {
        $keys = [];
        foreach ($this->db->all("SELECT ID, OPEN_KEY FROM ALERT WHERE STATE <> 'RESOLVIDO'") as $row) {
            $keys[rtrim((string) $row['open_key'])] = (int) $row['id'];
        }

        return $keys;
    }

    /** @return bool false se outro processo abriu o mesmo alerta no mesmo instante (índice único) */
    public function open(string $kind, int $resourceId, string $reason, string $severity, string $message, string $at): bool
    {
        try {
            $this->db->insert(
                "INSERT INTO ALERT (RESOURCE_KIND, RESOURCE_ID, REASON, SEVERITY, STATE, OPEN_KEY, MESSAGE, OPENED_AT) VALUES (?, ?, ?, ?, 'ABERTO', ?, ?, ?)",
                [$kind, $resourceId, $reason, $severity, "{$kind}:{$resourceId}:{$reason}", $message, $at],
            );

            return true;
        } catch (ConstraintViolation $violation) {
            if ($violation->constraint === 'UQ_ALERT_OPEN_KEY') {
                return false;
            }
            throw $violation;
        }
    }

    public function resolve(int $id, string $at, string $reason): void
    {
        $this->db->execute("UPDATE ALERT SET STATE = 'RESOLVIDO', OPEN_KEY = NULL, RESOLVED_AT = ?, RESOLVED_REASON = ? WHERE ID = ? AND STATE <> 'RESOLVIDO'", [$at, $reason, $id]);
    }

    public function acknowledge(int $id, int $userId, string $at, ?string $note): bool
    {
        return $this->db->execute(
            "UPDATE ALERT SET STATE = 'RECONHECIDO', ACK_AT = ?, ACK_BY = ?, ACK_NOTE = ? WHERE ID = ? AND STATE = 'ABERTO'",
            [$at, $userId, $note, $id],
        ) === 1;
    }

    /** Novo estado do acompanhamento (responsável e próxima conferência); incrementa a versão FOLLOWUP_NO. */
    public function advance(int $id, ?int $assigneeId, ?string $nextCheckAt): void
    {
        $this->db->execute('UPDATE ALERT SET ASSIGNEE_ID = ?, NEXT_CHECK_AT = ?, FOLLOWUP_NO = FOLLOWUP_NO + 1 WHERE ID = ?', [$assigneeId, $nextCheckAt, $id]);
    }

    /** @param array{alert_id: int, kind: string, action_code?: ?string, note?: ?string, from?: ?int, to?: ?int, next_check_at?: ?string, by: int, at: string} $f */
    public function addFollowUp(array $f): int
    {
        return $this->db->insert(
            'INSERT INTO ALERT_FOLLOWUP (ALERT_ID, KIND, ACTION_CODE, NOTE, ASSIGNEE_FROM, ASSIGNEE_TO, NEXT_CHECK_AT, CREATED_BY, CREATED_AT) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$f['alert_id'], $f['kind'], $f['action_code'] ?? null, $f['note'] ?? null, $f['from'] ?? null, $f['to'] ?? null, $f['next_check_at'] ?? null, $f['by'], $f['at']],
        );
    }

    /**
     * Acompanhamento dos alertas, do mais antigo ao mais novo, com os nomes das contas envolvidas.
     *
     * @param list<int> $alertIds
     * @return array<int, list<array<string, mixed>>> alerta => registros
     */
    public function followUps(array $alertIds): array
    {
        if ($alertIds === []) {
            return [];
        }
        $rows = $this->db->all(
            'SELECT f.*, ub.DISPLAY_NAME AS CREATED_BY_NAME, uf.DISPLAY_NAME AS FROM_NAME, ut.DISPLAY_NAME AS TO_NAME
             FROM ALERT_FOLLOWUP f JOIN APP_USER ub ON ub.ID = f.CREATED_BY
             LEFT JOIN APP_USER uf ON uf.ID = f.ASSIGNEE_FROM LEFT JOIN APP_USER ut ON ut.ID = f.ASSIGNEE_TO
             WHERE f.ALERT_ID IN (' . Database::placeholders(count($alertIds)) . ') ORDER BY f.ID',
            $alertIds,
        );
        $byAlert = [];
        foreach ($rows as $row) {
            $byAlert[(int) $row['alert_id']][] = self::trim($row);
        }

        return $byAlert;
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
