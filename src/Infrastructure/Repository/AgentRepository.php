<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Application\Filters;
use Fiscaliza\Infrastructure\Database;

/** SQL de agentes (recursos operacionais fictícios; não são contas de acesso). */
final class AgentRepository
{
    private const SELECT = 'SELECT g.*, a.NAME AS AGENCY_NAME, a.CODE AS AGENCY_CODE, r.NAME AS REGION_NAME, r.ZONE AS REGION_ZONE
        FROM AGENT g JOIN AGENCY a ON a.ID = g.AGENCY_ID LEFT JOIN REGION r ON r.ID = g.REGION_ID';

    private const WRITABLE = ['CODE', 'CALL_SIGN', 'ROLE', 'AGENCY_ID', 'STATUS', 'STATUS_CHANGED_AT', 'BASE_NAME', 'BASE_STREET',
        'BASE_REF', 'BASE_CEP', 'REGION_ID', 'BASE_LAT', 'BASE_LNG', 'POS_LAT', 'POS_LNG', 'POS_OBSERVED_AT', 'POS_RECEIVED_AT',
        'ARCHIVED_AT', 'CREATED_AT', 'UPDATED_AT'];

    public const SORTS = [
        'codigo' => 'g.CODE',
        'nome' => 'g.CALL_SIGN',
        'funcao' => 'g.ROLE, g.CODE',
        'situacao' => 'g.STATUS, g.CODE',
        'bairro' => 'r.NAME, g.CODE',
    ];

    public function __construct(private readonly Database $db) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::hydrate($this->db->one(self::SELECT . ' WHERE g.ID = ?', [$id]));
    }

    /** @return array<string, mixed>|null */
    public function lock(int $id): ?array
    {
        return self::hydrate($this->db->one('SELECT * FROM AGENT WHERE ID = ? WITH LOCK', [$id]));
    }

    public function nextCode(string $prefix): string
    {
        $last = (string) $this->db->value('SELECT MAX(CODE) FROM AGENT WHERE CODE STARTING WITH ?', [$prefix . '-']);
        $number = $last === '' ? 0 : (int) substr($last, strlen($prefix) + 1);

        return sprintf('%s-%03d', $prefix, $number + 1);
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        $values = self::onlyWritable($values);

        return $this->db->insert(
            'INSERT INTO AGENT (' . implode(', ', array_keys($values)) . ') VALUES (' . Database::placeholders(count($values)) . ')',
            array_values($values),
        );
    }

    /** @param array<string, mixed> $values */
    public function updateVersioned(int $id, int $expectedVersion, array $values): bool
    {
        $values = self::onlyWritable($values);
        $set = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($values)));

        return $this->db->execute(
            "UPDATE AGENT SET {$set}, VERSION_NO = VERSION_NO + 1 WHERE ID = ? AND VERSION_NO = ?",
            [...array_values($values), $id, $expectedVersion],
        ) === 1;
    }

    /** @param array<string, mixed> $values */
    public function updateState(int $id, array $values): void
    {
        $values = self::onlyWritable($values);
        $set = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($values)));
        $this->db->execute("UPDATE AGENT SET {$set}, VERSION_NO = VERSION_NO + 1 WHERE ID = ?", [...array_values($values), $id]);
    }

    /** Posição observada: só avança no tempo (evento atrasado não regride a posição atual). */
    public function movePosition(int $id, string $lat, string $lng, string $observedAt, string $receivedAt): bool
    {
        return $this->db->execute(
            'UPDATE AGENT SET POS_LAT = ?, POS_LNG = ?, POS_OBSERVED_AT = ?, POS_RECEIVED_AT = ? WHERE ID = ? AND (POS_OBSERVED_AT IS NULL OR POS_OBSERVED_AT < ?)',
            [$lat, $lng, $observedAt, $receivedAt, $id, $observedAt],
        ) === 1;
    }

    /** @return array{rows: list<array<string, mixed>>, total: int} */
    public function search(Filters $filters, string $text, bool $archived, string $sort, int $page, int $perPage): array
    {
        [$where, $params] = self::where($filters, $archived);
        if ($text !== '') {
            $where .= ' AND (g.CODE CONTAINING ? OR g.CALL_SIGN CONTAINING ? OR g.BASE_NAME CONTAINING ?)';
            array_push($params, $text, $text, $text);
        }
        $order = self::SORTS[$sort] ?? self::SORTS['codigo'];
        $total = (int) $this->db->value('SELECT COUNT(*) FROM AGENT g LEFT JOIN REGION r ON r.ID = g.REGION_ID WHERE ' . $where, $params);
        $rows = $this->db->all(
            self::SELECT . " WHERE {$where} ORDER BY {$order} OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, ($page - 1) * $perPage, $perPage],
        );

        return ['rows' => array_map(self::hydrate(...), $rows), 'total' => $total];
    }

    /** @return list<array<string, mixed>> */
    public function forCentral(Filters $filters): array
    {
        if (!$filters->includesAgents()) {
            return [];
        }
        [$where, $params] = self::where($filters, false);

        return array_map(self::hydrate(...), $this->db->all(self::SELECT . " WHERE {$where} ORDER BY g.CODE", $params));
    }

    /** @return list<array<string, mixed>> */
    public function allActive(): array
    {
        return array_map(self::hydrate(...), $this->db->all(self::SELECT . ' WHERE g.ARCHIVED_AT IS NULL ORDER BY g.ID'));
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function where(Filters $filters, bool $archived): array
    {
        $where = $archived ? 'g.ARCHIVED_AT IS NOT NULL' : 'g.ARCHIVED_AT IS NULL';
        $params = [];
        foreach ([['g.ROLE', $filters->agentRoles()], ['g.STATUS', $filters->agentStatuses()], ['g.REGION_ID', $filters->regions], ['g.AGENCY_ID', $filters->agencies]] as [$column, $values]) {
            if ($values !== []) {
                $where .= " AND {$column} IN (" . Database::placeholders(count($values)) . ')';
                array_push($params, ...$values);
            }
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private static function onlyWritable(array $values): array
    {
        $values = array_change_key_case($values, CASE_UPPER);
        $unknown = array_diff(array_keys($values), self::WRITABLE);
        if ($unknown !== []) {
            throw new \LogicException('Coluna não gravável: ' . implode(', ', $unknown));
        }

        return $values;
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
        foreach (['base_lat', 'base_lng', 'pos_lat', 'pos_lng'] as $c) {
            $row[$c] = isset($row[$c]) ? (float) $row[$c] : null;
        }

        return $row;
    }
}
