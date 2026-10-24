<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Application\FineFilters;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Infrastructure\Database;

/**
 * SQL da aba Multas: projeção (FINE), constatações documentais e histórico próprio (estes dois só recebem INSERT).
 * Linhas voltam com chaves minúsculas; SOURCE_SNAPSHOT, RADAR_DETAILS e PAYLOAD são decodificados.
 */
final class FineRepository
{
    /** LATER_DOC_STATUS: a última conferência "depois da multa" da identidade atual (mesma escolha de FineDocumentStatus::select). */
    private const SELECT = 'SELECT f.*, a.CODE AS AGENCY_CODE, a.NAME AS AGENCY_NAME, r.NAME AS REGION_NAME,
        e.CODE AS EQUIPMENT_CODE, e.ARCHIVED_AT AS EQUIPMENT_ARCHIVED_AT, g.CODE AS AGENT_CODE, g.CALL_SIGN AS AGENT_CALL_SIGN,
        g.ARCHIVED_AT AS AGENT_ARCHIVED_AT, uc.DISPLAY_NAME AS CREATED_BY_NAME, uu.DISPLAY_NAME AS UPDATED_BY_NAME, ux.DISPLAY_NAME AS CANCELLED_BY_NAME,
        (SELECT FIRST 1 c.DOCUMENT_STATUS FROM FINE_DOCUMENT_CHECK c WHERE c.FINE_ID = f.ID AND c.SCOPE = \'POSTERIOR\' AND c.SUBJECT_VERSION = f.SUBJECT_VERSION
            ORDER BY c.CHECKED_AT DESC, c.ID DESC) AS LATER_DOC_STATUS
        FROM FINE f LEFT JOIN AGENCY a ON a.ID = f.AGENCY_ID LEFT JOIN REGION r ON r.ID = f.REGION_ID
        LEFT JOIN EQUIPMENT e ON e.ID = f.EQUIPMENT_ID LEFT JOIN AGENT g ON g.ID = f.AGENT_ID
        LEFT JOIN APP_USER uc ON uc.ID = f.CREATED_BY LEFT JOIN APP_USER uu ON uu.ID = f.UPDATED_BY LEFT JOIN APP_USER ux ON ux.ID = f.CANCELLED_BY';

    /** Colunas que os casos de uso podem gravar (lista fechada: nomes de coluna nunca vêm da requisição). */
    private const WRITABLE = ['RECORD_STATE', 'DESCRIPTION', 'OCCURRED_AT', 'INFRACTION_KIND', 'LEGAL_REFERENCE', 'INFRACTION_CODE', 'INFRACTION_DETAIL',
        'AGENCY_ID', 'EXTERNAL_AIT', 'AIT_KEY', 'AIT_MISSING_REASON', 'STREET', 'ADDRESS_REF', 'REGION_ID', 'LAT', 'LNG', 'NOTES', 'SOURCE_KIND',
        'EQUIPMENT_ID', 'AGENT_ID', 'SOURCE_NOTE', 'SOURCE_SNAPSHOT', 'SPEED_LIMIT_KMH', 'SPEED_MEASURED_KMH', 'SPEED_CONSIDERED_KMH', 'RADAR_DETAILS',
        'EVIDENCE_REFERENCE', 'VEHICLE_PLATE', 'VEHICLE_UF', 'VEHICLE_MANUFACTURER', 'VEHICLE_MODEL', 'VEHICLE_SPECIES', 'VEHICLE_COLOR', 'VEHICLE_YEAR',
        'VEHICLE_RENAVAM', 'RENAVAM_MISSING_REASON', 'PROCESS_STAGE', 'PAYMENT_STATUS', 'CONTEST_STATUS', 'CONTEST_KIND', 'NOMINAL_AMOUNT_CENTS',
        'AUTUATION_ISSUED_ON', 'AUTUATION_RECEIVED_ON', 'PENALTY_ISSUED_ON', 'PENALTY_RECEIVED_ON', 'PAYMENT_DUE_ON', 'DEFENSE_DUE_ON', 'APPEAL_DUE_ON',
        'PAID_ON', 'PAID_AMOUNT_CENTS', 'PAYMENT_REFERENCE', 'CONTEST_SUBMITTED_ON', 'CONTEST_REFERENCE', 'CONTEST_DECIDED_ON', 'CONTEST_RESULT_NOTE',
        'CLOSED_ON', 'CLOSURE_REFERENCE', 'CLOSURE_NOTE', 'OCC_DOC_STATUS', 'OCC_DOC_REVIEW_ON', 'ATTENTION_COUNT', 'SUBJECT_VERSION', 'CREATED_AT',
        'UPDATED_AT', 'CREATED_BY', 'UPDATED_BY', 'CREATED_SOURCE', 'REGISTERED_AT', 'CANCELLED_AT', 'CANCELLED_BY', 'CANCEL_SOURCE', 'CANCEL_REASON'];

    /** Ordenações permitidas: chave pública => ORDER BY fixo (ID desempata). */
    private const ORDER = [
        'ocorrencia' => 'f.OCCURRED_AT DESC NULLS LAST, f.ID DESC',
        'codigo' => 'f.ID DESC',
        'atualizado' => 'f.UPDATED_AT DESC, f.ID DESC',
    ];

    public function __construct(private readonly Database $db) {}

    /**
     * @return array<string, mixed>|null
     * @phpstan-impure lido de novo após um conflito: o registro pode ter mudado entre duas chamadas
     */
    public function find(int $id): ?array
    {
        return self::hydrate($this->db->one(self::SELECT . ' WHERE f.ID = ?', [$id]));
    }

    /**
     * Lê e bloqueia a multa até o fim da transação. Ordem fixa de bloqueio: multa → equipamento → agente.
     *
     * @return array<string, mixed>|null
     */
    public function lock(int $id): ?array
    {
        return self::hydrate($this->db->one('SELECT * FROM FINE WHERE ID = ? WITH LOCK', [$id]));
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        $values = self::onlyWritable($values);

        return $this->db->insert(
            'INSERT INTO FINE (' . implode(', ', array_keys($values)) . ') VALUES (' . Database::placeholders(count($values)) . ')',
            array_values($values),
        );
    }

    /**
     * Toda mudança de domínio avança VERSION_NO; só grava se a versão ainda for a lida pelo formulário.
     *
     * @param array<string, mixed> $values
     */
    public function updateVersioned(int $id, int $expectedVersion, array $values): bool
    {
        $values = self::onlyWritable($values);
        $set = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($values)));

        return $this->db->execute(
            "UPDATE FINE SET {$set}, VERSION_NO = VERSION_NO + 1 WHERE ID = ? AND VERSION_NO = ?",
            [...array_values($values), $id, $expectedVersion],
        ) === 1;
    }

    /**
     * Lista, total e os três resumos com o MESMO filtro, numa leitura (o chamador abre a transação SNAPSHOT).
     * Sem JOIN com constatações/eventos: nenhuma multa é contada duas vezes.
     *
     * @return array{rows: list<array<string, mixed>>, total: int, attention: int, paid: int}
     */
    public function search(FineFilters $filters, string $today, int $page, int $perPage): array
    {
        [$where, $params] = self::where($filters);
        $counts = $this->db->one(
            "SELECT COUNT(*) AS TOTAL,
                SUM(CASE WHEN f.RECORD_STATE <> 'CANCELADO' AND (f.ATTENTION_COUNT > 0 OR f.OCC_DOC_REVIEW_ON < ?) THEN 1 ELSE 0 END) AS ATTENTION,
                SUM(CASE WHEN f.PAYMENT_STATUS = 'PAGAMENTO_INFORMADO' THEN 1 ELSE 0 END) AS PAID
             FROM FINE f WHERE {$where}",
            [$today, ...$params],
        ) ?? [];
        $rows = $this->db->all(
            self::SELECT . " WHERE {$where} ORDER BY " . (self::ORDER[$filters->sort] ?? self::ORDER['ocorrencia']) . ' OFFSET ? ROWS FETCH NEXT ? ROWS ONLY',
            [...$params, ($page - 1) * $perPage, $perPage],
        );

        return ['rows' => array_map(self::hydrate(...), $rows), 'total' => (int) ($counts['total'] ?? 0), 'attention' => (int) ($counts['attention'] ?? 0), 'paid' => (int) ($counts['paid'] ?? 0)];
    }

    /** @param array<string, mixed> $event */
    public function appendEvent(array $event): void
    {
        $this->db->execute(
            'INSERT INTO FINE_EVENT (FINE_ID, EVENT_UID, REQUEST_HASH, EVENT_TYPE, SOURCE, ACTOR_USER_ID, RECORDED_AT, SUMMARY, REASON, PAYLOAD) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$event['fine_id'], $event['uid'], $event['hash'], $event['type'], $event['source'], $event['actor'], $event['recorded_at'],
                mb_substr($event['summary'], 0, 240), $event['reason'], json_encode($event['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
        );
    }

    /** @return array<string, mixed>|null */
    public function eventByUid(string $uid): ?array
    {
        $row = $this->db->one('SELECT FINE_ID, EVENT_TYPE, ACTOR_USER_ID, REQUEST_HASH FROM FINE_EVENT WHERE EVENT_UID = ?', [$uid]);

        return $row === null ? null : array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
    }

    /**
     * Histórico em ordem de registro, mais recente primeiro, paginado (nenhum evento fica inacessível).
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function events(int $fineId, int $page, int $perPage): array
    {
        $rows = $this->db->all(
            'SELECT ev.*, u.DISPLAY_NAME AS ACTOR_NAME FROM FINE_EVENT ev LEFT JOIN APP_USER u ON u.ID = ev.ACTOR_USER_ID
             WHERE ev.FINE_ID = ? ORDER BY ev.RECORDED_AT DESC, ev.ID DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY',
            [$fineId, ($page - 1) * $perPage, $perPage],
        );

        return [
            'rows' => array_map(static function (array $row): array {
                $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
                $row['payload'] = is_string($row['payload']) && $row['payload'] !== '' ? (json_decode($row['payload'], true) ?: []) : [];

                return $row;
            }, $rows),
            'total' => (int) $this->db->value('SELECT COUNT(*) FROM FINE_EVENT WHERE FINE_ID = ?', [$fineId]),
        ];
    }

    /** @param array<string, mixed> $check */
    public function insertCheck(array $check): int
    {
        $check = array_change_key_case($check, CASE_UPPER);

        return $this->db->insert(
            'INSERT INTO FINE_DOCUMENT_CHECK (' . implode(', ', array_keys($check)) . ') VALUES (' . Database::placeholders(count($check)) . ')',
            array_values($check),
        );
    }

    /** @return list<array<string, mixed>> todas as constatações da multa, mais recentes (pelo instante da conferência) primeiro */
    public function checks(int $fineId): array
    {
        return array_map(
            static fn(array $row): array => array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row),
            $this->db->all(
                'SELECT c.*, u.DISPLAY_NAME AS ACTOR_NAME FROM FINE_DOCUMENT_CHECK c LEFT JOIN APP_USER u ON u.ID = c.ACTOR_USER_ID
                 WHERE c.FINE_ID = ? ORDER BY c.CHECKED_AT DESC, c.ID DESC',
                [$fineId],
            ),
        );
    }

    /**
     * Origens que o formulário oferece: radares do inventário e agentes de trânsito, em atividade primeiro e arquivados
     * por último (permitidos para registro histórico, com aviso). Volume de demonstração: dezenas.
     *
     * @return array{radars: list<array<string, mixed>>, agents: list<array<string, mixed>>}
     */
    public function sourceOptions(): array
    {
        $trim = static fn(array $row): array => array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);

        return [
            'radars' => array_map(static function (array $row) use ($trim): array {
                $row = $trim($row);
                $specs = is_string($row['specs'] ?? null) ? (json_decode($row['specs'], true) ?: []) : [];
                $row['speed_limit_kmh'] = $specs['speed_limit_kmh'] ?? null;
                $row['functions'] = $specs['functions'] ?? [];
                unset($row['specs']);

                return $row;
            }, $this->db->all("SELECT e.ID, e.CODE, e.MANUFACTURER, e.MODEL, e.STREET, e.STATUS, e.ARCHIVED_AT, e.SPECS, e.AGENCY_ID, a.CODE AS AGENCY_CODE
                FROM EQUIPMENT e JOIN AGENCY a ON a.ID = e.AGENCY_ID WHERE e.TYPE_CODE = 'RADAR' ORDER BY CASE WHEN e.ARCHIVED_AT IS NULL THEN 0 ELSE 1 END, e.CODE")),
            'agents' => array_map($trim, $this->db->all("SELECT g.ID, g.CODE, g.CALL_SIGN, g.STATUS, g.ARCHIVED_AT, g.AGENCY_ID, a.CODE AS AGENCY_CODE
                FROM AGENT g JOIN AGENCY a ON a.ID = g.AGENCY_ID WHERE g.ROLE = 'TRANSITO' ORDER BY CASE WHEN g.ARCHIVED_AT IS NULL THEN 0 ELSE 1 END, g.CODE")),
        ];
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function where(FineFilters $f): array
    {
        $states = $f->states();
        $where = 'f.RECORD_STATE IN (' . Database::placeholders(count($states)) . ')';
        $params = $states;
        foreach ([['f.SOURCE_KIND', $f->source], ['f.PROCESS_STAGE', $f->stage], ['f.AGENCY_ID', $f->agency], ['f.EQUIPMENT_ID', $f->radar],
            ['f.AGENT_ID', $f->agent], ['f.OCC_DOC_STATUS', $f->document], ['f.PAYMENT_STATUS', $f->payment]] as [$column, $value]) {
            if ($value !== null) {
                $where .= " AND {$column} = ?";
                $params[] = $value;
            }
        }
        [$start, $end] = $f->utcRange();
        if ($start !== null) {
            $where .= ' AND f.OCCURRED_AT >= ?';
            $params[] = $start;
        }
        if ($end !== null) {
            $where .= ' AND f.OCCURRED_AT < ?';
            $params[] = $end;
        }
        if ($f->q !== '') {
            $id = FineRules::idFromCode($f->q);
            if ($id !== null) {
                // Código interno não tem coluna própria: é derivado do ID.
                $where .= ' AND f.ID = ?';
                $params[] = $id;
            } else {
                // CAST: sem ele o Firebird tipa o parâmetro pela coluna (placa VARCHAR(7)) e recusa uma busca mais longa.
                $where .= ' AND (f.AIT_KEY CONTAINING CAST(? AS VARCHAR(60)) OR f.VEHICLE_PLATE CONTAINING CAST(? AS VARCHAR(60))'
                    . ' OR f.VEHICLE_RENAVAM CONTAINING CAST(? AS VARCHAR(60)) OR f.DESCRIPTION CONTAINING CAST(? AS VARCHAR(60)))';
                array_push($params, FineRules::aitKey($f->q), FineRules::plate($f->q), $f->q, $f->q);
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
        foreach (['SOURCE_SNAPSHOT', 'RADAR_DETAILS'] as $json) {
            if (array_key_exists($json, $values) && is_array($values[$json])) {
                $values[$json] = $values[$json] === [] ? null : json_encode($values[$json], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }
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
        foreach (['source_snapshot', 'radar_details'] as $json) {
            $row[$json] = is_string($row[$json] ?? null) && $row[$json] !== '' ? (json_decode($row[$json], true) ?: []) : [];
        }
        foreach (['occurred_at', 'created_at', 'updated_at', 'registered_at', 'cancelled_at'] as $ts) {
            $row[$ts] = is_string($row[$ts] ?? null) ? substr($row[$ts], 0, 19) : null;
        }
        foreach (['lat', 'lng'] as $c) {
            $row[$c] = isset($row[$c]) ? number_format((float) $row[$c], 6, '.', '') : null;
        }
        $row['code'] = FineRules::code((int) $row['id']);

        return $row;
    }
}
