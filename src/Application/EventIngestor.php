<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\EventRepository;
use Fiscaliza\Infrastructure\Repository\MetaRepository;

/**
 * Entrada de observações de campo (simuladas). Separa evento ocorrido, evento recebido e estado atual:
 *
 * - duplicata (mesmo EVENT_UID) é ignorada e contada, sem novo histórico nem alerta;
 * - evento mais antigo que o estado atual entra no histórico como HISTORICO, sem regredir o estado;
 * - telemetria só alterna OPERACIONAL ↔ FALHA; estados administrativos (manutenção, desativado, furtado,
 *   não instalado) prevalecem e o evento fica apenas no histórico;
 * - posição só avança no tempo.
 */
final class EventIngestor
{
    public const APPLIED = 'APLICADO';
    public const HISTORICAL = 'HISTORICO';
    public const DUPLICATE = 'DUPLICADO';

    public function __construct(
        private readonly Database $db,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly EventRepository $events,
        private readonly MetaRepository $meta,
        private readonly Clock $clock,
    ) {}

    /**
     * Observação de situação de um equipamento (OPERACIONAL ou FALHA).
     *
     * @return string APLICADO | HISTORICO | DUPLICADO
     */
    public function equipmentStatus(string $uid, int $equipmentId, EquipmentStatus $reported, \DateTimeImmutable $occurredAt, string $detail, string $source = 'SIMULADOR', ?int $actor = null): string
    {
        if (!in_array($reported, [EquipmentStatus::Operacional, EquipmentStatus::Falha], true)) {
            throw new \InvalidArgumentException('Telemetria só informa OPERACIONAL ou FALHA.');
        }

        return $this->db->transaction(function () use ($uid, $equipmentId, $reported, $occurredAt, $detail, $source, $actor): string {
            if ($this->events->exists($uid)) {
                return $this->duplicate();
            }
            $current = $this->equipment->lock($equipmentId) ?? throw new NotFound('Equipamento não encontrado.');
            $from = EquipmentStatus::from($current['status']);
            $occurred = Time::toDb($occurredAt);
            $now = Time::toDb($this->clock->now());

            [$outcome, $why] = match (true) {
                $current['archived_at'] !== null => [self::HISTORICAL, 'equipamento arquivado'],
                $from->isAdministrative() => [self::HISTORICAL, "estado administrativo \"{$from->label()}\" prevalece"],
                $occurred < (string) $current['status_changed_at'] => [self::HISTORICAL, 'evento atrasado: estado atual é mais recente'],
                $from === $reported => [self::HISTORICAL, 'sem mudança de situação'],
                default => [self::APPLIED, ''],
            };
            if ($outcome === self::APPLIED) {
                $this->equipment->updateState($equipmentId, ['status' => $reported->value, 'status_changed_at' => $occurred, 'updated_at' => $now]);
            }
            // Qualquer observação recebida é sinal de vida do aparelho.
            $this->equipment->touchSignal($equipmentId, min($occurred, $now));

            $summary = $outcome === self::APPLIED ? "{$from->label()} → {$reported->label()}: {$detail}" : "Recebido \"{$reported->label()}\" ({$why}): {$detail}";
            $inserted = $this->events->append([
                'uid' => $uid, 'kind' => 'EQUIPAMENTO', 'resource_id' => $equipmentId, 'type' => 'SITUACAO', 'source' => $source, 'actor' => $actor,
                'occurred_at' => $occurred, 'received_at' => $now, 'outcome' => $outcome, 'summary' => $summary,
                'payload' => ['de' => $from->value, 'para' => $reported->value, 'motivo' => $detail] + ($outcome === self::HISTORICAL ? ['ignorado_porque' => $why] : []),
            ]);
            if ($inserted === null) {
                // Mesmo UID gravado por outra conexão entre a verificação e o INSERT: desfaz a mudança de estado.
                throw new DuplicateEvent();
            }
            if ($outcome === self::HISTORICAL && str_starts_with($why, 'evento atrasado')) {
                $this->meta->increment('late_events', 1, $now);
            }

            return $outcome;
        });
    }

    /**
     * Posição observada de agente ou de equipamento móvel. Com $keepHistory = false atualiza só o estado atual
     * (o simulador grava no histórico uma amostra a cada N posições, para controlar volume). $detail identifica a
     * origem no resumo (ex.: recebimento simulado pela demonstração).
     */
    public function position(string $uid, string $kind, int $resourceId, string $lat, string $lng, \DateTimeImmutable $occurredAt, bool $keepHistory, string $source = 'SIMULADOR', ?int $actor = null, string $detail = ''): string
    {
        return $this->db->transaction(function () use ($uid, $kind, $resourceId, $lat, $lng, $occurredAt, $keepHistory, $source, $actor, $detail): string {
            if ($keepHistory && $this->events->exists($uid)) {
                return $this->duplicate();
            }
            $occurred = Time::toDb($occurredAt);
            $now = Time::toDb($this->clock->now());
            if ($kind === 'AGENTE') {
                $moved = $this->agents->movePosition($resourceId, $lat, $lng, $occurred, $now);
            } else {
                $current = $this->equipment->lock($resourceId) ?? throw new NotFound('Equipamento não encontrado.');
                $moved = $current['pos_observed_at'] === null || (string) $current['pos_observed_at'] < $occurred;
                if ($moved) {
                    $this->equipment->updateState($resourceId, ['pos_lat' => $lat, 'pos_lng' => $lng, 'pos_observed_at' => $occurred, 'pos_received_at' => $now]);
                }
                $this->equipment->touchSignal($resourceId, min($occurred, $now));
            }
            $outcome = $moved ? self::APPLIED : self::HISTORICAL;
            if ($keepHistory) {
                $this->events->append([
                    'uid' => $uid, 'kind' => $kind, 'resource_id' => $resourceId, 'type' => 'POSICAO', 'source' => $source, 'actor' => $actor,
                    'occurred_at' => $occurred, 'received_at' => $now, 'outcome' => $outcome,
                    'summary' => ($moved ? "Posição {$lat}, {$lng}" : "Posição antiga {$lat}, {$lng} (não substitui a atual)") . ($detail !== '' ? " ({$detail})" : ''),
                    'payload' => ['lat' => $lat, 'lng' => $lng],
                ]) ?? throw new DuplicateEvent();
            }

            return $outcome;
        });
    }

    private function duplicate(): string
    {
        $this->meta->increment('duplicates_ignored', 1, Time::toDb($this->clock->now()));

        return self::DUPLICATE;
    }
}
