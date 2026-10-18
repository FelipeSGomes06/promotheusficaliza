<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Application\Filters;
use Fiscaliza\Infrastructure\Database;

/** SQL de equipamentos. Linhas voltam com chaves minúsculas; SPECS é decodificado para array. */
final class EquipmentRepository
{
    private const SELECT = 'SELECT e.*, a.NAME AS AGENCY_NAME, a.CODE AS AGENCY_CODE, r.NAME AS REGION_NAME, r.ZONE AS REGION_ZONE
        FROM EQUIPMENT e JOIN AGENCY a ON a.ID = e.AGENCY_ID LEFT JOIN REGION r ON r.ID = e.REGION_ID';

    /** Colunas que os casos de uso podem gravar (lista fechada: nomes de coluna nunca vêm da requisição). */
    private const WRITABLE = ['CODE', 'ASSET_TAG', 'TYPE_CODE', 'MANUFACTURER', 'MODEL', 'SERIAL_NUMBER', 'AGENCY_ID', 'STATUS',
        'STATUS_CHANGED_AT', 'CEP', 'STREET', 'ADDRESS_REF', 'REGION_ID', 'LAT', 'LNG', 'LOCATION_KIND', 'POS_LAT', 'POS_LNG',
        'POS_OBSERVED_AT', 'POS_RECEIVED_AT', 'LAST_SEEN_AT', 'MAINT_INTERVAL_DAYS', 'SPECS', 'NOTES', 'INSTALLED_ON',
        'ARCHIVED_AT', 'CREATED_AT', 'UPDATED_AT'];

    /** Ordenações permitidas: chave pública => ORDER BY fixo. */
    public const SORTS = [
        'codigo' => 'e.CODE',
        'tipo' => 'e.TYPE_CODE, e.CODE',
        'situacao' => 'e.STATUS, e.CODE',
        'bairro' => 'r.NAME, e.CODE',
        'atualizado' => 'e.UPDATED_AT DESC, e.CODE',
    ];

    public function __construct(private readonly Database $db) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::hydrate($this->db->one(self::SELECT . ' WHERE e.ID = ?', [$id]));
    }

    /**
     * Lê e bloqueia a linha até o fim da transação (SELECT … WITH LOCK), serializando mudanças concorrentes
     * de situação do mesmo equipamento. Deve ser chamado dentro de Database::transaction().
     *
     * @return array<string, mixed>|null
     */
    public function lock(int $id): ?array
    {
        return self::hydrate($this->db->one('SELECT * FROM EQUIPMENT WHERE ID = ? WITH LOCK', [$id]));
    }

    public function nextCode(string $prefix): string
    {
        $last = (string) $this->db->value('SELECT MAX(CODE) FROM EQUIPMENT WHERE CODE STARTING WITH ?', [$prefix . '-']);
        $number = $last === '' ? 0 : (int) substr($last, strlen($prefix) + 1);

        return sprintf('%s-%04d', $prefix, $number + 1);
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        $values = self::onlyWritable($values);

        return $this->db->insert(
            'INSERT INTO EQUIPMENT (' . implode(', ', array_keys($values)) . ') VALUES (' . Database::placeholders(count($values)) . ')',
            array_values($values),
        );
    }

    /**
     * Atualização com bloqueio otimista: só grava se VERSION_NO ainda for $expectedVersion.
     *
     * @param array<string, mixed> $values
     * @return bool false quando outra pessoa salvou antes (conflito de edição)
     */
    public function updateVersioned(int $id, int $expectedVersion, array $values): bool
    {
        $values = self::onlyWritable($values);
        $set = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($values)));

        return $this->db->execute(
            "UPDATE EQUIPMENT SET {$set}, VERSION_NO = VERSION_NO + 1 WHERE ID = ? AND VERSION_NO = ?",
            [...array_values($values), $id, $expectedVersion],
        ) === 1;
    }

    /**
     * Atualização de estado operacional (situação, posição, sinal) sem exigir versão do formulário,
     * mas incrementando-a para que um formulário aberto detecte a mudança.
     *
     * @param array<string, mixed> $values
     */
    public function updateState(int $id, array $values): void
    {
        $values = self::onlyWritable($values);
        $set = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($values)));
        $this->db->execute("UPDATE EQUIPMENT SET {$set}, VERSION_NO = VERSION_NO + 1 WHERE ID = ?", [...array_values($values), $id]);
    }

    /** Batimento de sinal: não altera versão nem gera histórico (volume controlado). */
    public function touchSignal(int $id, string $seenAt): void
    {
        $this->db->execute('UPDATE EQUIPMENT SET LAST_SEEN_AT = ? WHERE ID = ? AND (LAST_SEEN_AT IS NULL OR LAST_SEEN_AT < ?)', [$seenAt, $id, $seenAt]);
    }

    /**
     * Lista paginada para o inventário.
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(Filters $filters, string $text, bool $archived, string $sort, int $page, int $perPage): array
    {
        [$where, $params] = self::where($filters, $archived);
        if ($text !== '') {
            $where .= ' AND (e.CODE CONTAINING ? OR e.SERIAL_NUMBER CONTAINING ? OR e.MANUFACTURER CONTAINING ? OR e.MODEL CONTAINING ? OR e.STREET CONTAINING ? OR e.ASSET_TAG CONTAINING ?)';
            array_push($params, $text, $text, $text, $text, $text, $text);
        }
        $order = self::SORTS[$sort] ?? self::SORTS['codigo'];
        $total = (int) $this->db->value('SELECT COUNT(*) FROM EQUIPMENT e LEFT JOIN REGION r ON r.ID = e.REGION_ID WHERE ' . $where, $params);
        $rows = $this->db->all(
            self::SELECT . " WHERE {$where} ORDER BY {$order} OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, ($page - 1) * $perPage, $perPage],
        );

        return ['rows' => array_map(self::hydrate(...), $rows), 'total' => $total];
    }

    /**
     * Todos os equipamentos ativos que atendem ao filtro (volume de demonstração: dezenas; sem paginação oculta).
     *
     * @return list<array<string, mixed>>
     */
    public function forCentral(Filters $filters): array
    {
        if (!$filters->includesEquipment()) {
            return [];
        }
        [$where, $params] = self::where($filters, false);

        return array_map(self::hydrate(...), $this->db->all(self::SELECT . " WHERE {$where} ORDER BY e.CODE", $params));
    }

    /** @return list<array<string, mixed>> */
    public function allActive(): array
    {
        return array_map(self::hydrate(...), $this->db->all(self::SELECT . ' WHERE e.ARCHIVED_AT IS NULL ORDER BY e.ID'));
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function where(Filters $filters, bool $archived): array
    {
        $where = $archived ? 'e.ARCHIVED_AT IS NOT NULL' : 'e.ARCHIVED_AT IS NULL';
        $params = [];
        foreach ([['e.TYPE_CODE', $filters->equipmentTypes()], ['e.STATUS', $filters->equipmentStatuses()], ['e.REGION_ID', $filters->regions], ['e.AGENCY_ID', $filters->agencies]] as [$column, $values]) {
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
        if (isset($values['SPECS']) && is_array($values['SPECS'])) {
            $values['SPECS'] = json_encode($values['SPECS'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
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
        $row['specs'] = is_string($row['specs'] ?? null) && $row['specs'] !== '' ? (json_decode($row['specs'], true) ?: []) : [];
        foreach (['lat', 'lng', 'pos_lat', 'pos_lng'] as $c) {
            $row[$c] = isset($row[$c]) ? (float) $row[$c] : null;
        }

        return $row;
    }
}
