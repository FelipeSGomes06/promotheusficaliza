<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;

/**
 * Histórico de eventos (append-only). EVENT_UID único garante que um evento repetido não é gravado duas vezes,
 * inclusive quando chega por duas conexões ao mesmo tempo.
 */
final class EventRepository
{
    public function __construct(private readonly Database $db) {}

    /**
     * @param array{uid: string, kind: string, resource_id: int, type: string, source: string, actor: ?int,
     *     occurred_at: string, received_at: string, outcome: string, summary: string, payload?: array<string, mixed>} $e
     * @return int|null id gravado, ou null se o EVENT_UID já existia (duplicata ignorada)
     */
    public function append(array $e): ?int
    {
        try {
            return $this->db->insert(
                'INSERT INTO RESOURCE_EVENT (EVENT_UID, RESOURCE_KIND, RESOURCE_ID, EVENT_TYPE, SOURCE, ACTOR_USER_ID, OCCURRED_AT, RECEIVED_AT, OUTCOME, SUMMARY, PAYLOAD)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$e['uid'], $e['kind'], $e['resource_id'], $e['type'], $e['source'], $e['actor'], $e['occurred_at'], $e['received_at'],
                    $e['outcome'], mb_substr($e['summary'], 0, 240), ($e['payload'] ?? []) === [] ? null : json_encode($e['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
            );
        } catch (ConstraintViolation $violation) {
            if ($violation->constraint === 'UQ_RESOURCE_EVENT_UID') {
                return null;
            }
            throw $violation;
        }
    }

    public function exists(string $uid): bool
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM RESOURCE_EVENT WHERE EVENT_UID = ?', [$uid]) > 0;
    }

    /** @return list<array<string, mixed>> */
    public function forResource(string $kind, int $id, int $limit = 40): array
    {
        return array_map(self::decode(...), $this->db->all(
            'SELECT ev.*, u.DISPLAY_NAME AS ACTOR_NAME FROM RESOURCE_EVENT ev LEFT JOIN APP_USER u ON u.ID = ev.ACTOR_USER_ID
             WHERE ev.RESOURCE_KIND = ? AND ev.RESOURCE_ID = ? ORDER BY ev.OCCURRED_AT DESC, ev.ID DESC ROWS ?',
            [$kind, $id, $limit],
        ));
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 15): array
    {
        return array_map(self::decode(...), $this->db->all(
            "SELECT ev.ID, ev.RESOURCE_KIND, ev.RESOURCE_ID, ev.EVENT_TYPE, ev.SOURCE, ev.OCCURRED_AT, ev.RECEIVED_AT, ev.OUTCOME, ev.SUMMARY
             FROM RESOURCE_EVENT ev WHERE ev.EVENT_TYPE <> 'POSICAO' ORDER BY ev.ID DESC ROWS ?",
            [$limit],
        ));
    }

    /** @return array{last_id: int, last_received_at: ?string} */
    public function watermark(): array
    {
        $row = $this->db->one('SELECT MAX(ID) AS LAST_ID, MAX(RECEIVED_AT) AS LAST_RECEIVED_AT FROM RESOURCE_EVENT') ?? [];

        return ['last_id' => (int) ($row['last_id'] ?? 0), 'last_received_at' => $row['last_received_at'] ?? null];
    }

    /**
     * Eventos de mudança de situação de equipamentos recebidos até $until (exclusivo), em ordem de ocorrência —
     * base da análise. OCCURRED_AT diz quando aconteceu; RECEIVED_AT, quando o sistema passou a saber.
     *
     * @return list<array<string, mixed>>
     */
    public function equipmentStatusHistory(string $until): array
    {
        return array_map(self::decode(...), $this->db->all(
            "SELECT ID, RESOURCE_ID, OCCURRED_AT, RECEIVED_AT, OUTCOME, PAYLOAD FROM RESOURCE_EVENT
             WHERE RESOURCE_KIND = 'EQUIPAMENTO' AND EVENT_TYPE = 'SITUACAO' AND RECEIVED_AT < ? ORDER BY OCCURRED_AT, ID",
            [$until],
        ));
    }

    /**
     * Alterações de cadastro de equipamentos que mudaram a periodicidade preventiva, em ordem de registro:
     * permitem reconstruir a configuração válida em cada corte da avaliação retrospectiva.
     *
     * @return list<array<string, mixed>>
     */
    public function equipmentIntervalChanges(string $until): array
    {
        return array_map(self::decode(...), $this->db->all(
            "SELECT ID, RESOURCE_ID, RECEIVED_AT, PAYLOAD FROM RESOURCE_EVENT
             WHERE RESOURCE_KIND = 'EQUIPAMENTO' AND EVENT_TYPE = 'ALTERACAO' AND RECEIVED_AT < ? AND PAYLOAD CONTAINING 'maint_interval_days'
             ORDER BY RECEIVED_AT, ID",
            [$until],
        ));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function decode(array $row): array
    {
        $row = array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
        if (array_key_exists('payload', $row)) {
            $row['payload'] = is_string($row['payload']) && $row['payload'] !== '' ? (json_decode($row['payload'], true) ?: []) : [];
        }

        return $row;
    }
}
