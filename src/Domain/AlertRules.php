<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

use DateTimeImmutable;

/**
 * Condições que mantêm um alerta aberto. O alerta nasce quando a condição aparece e é resolvido automaticamente
 * quando ela desaparece; reconhecer um alerta registra ciência, mas não resolve a causa.
 *
 * @phpstan-type Condition array{kind: string, resource_id: int, reason: string, severity: string, message: string}
 */
final class AlertRules
{
    /**
     * @param list<array<string, mixed>> $equipment linhas ativas (status, last_seen_at, code, id)
     * @param list<array<string, mixed>> $agents    linhas ativas (status, pos_observed_at, call_sign, id)
     * @param list<array<string, mixed>> $openOrders ordens ABERTA/EM_EXECUCAO (equipment_id, priority, due_date)
     * @return array<string, Condition> chave = OPEN_KEY
     */
    public static function conditions(array $equipment, array $agents, array $openOrders, DateTimeImmutable $now): array
    {
        $conditions = [];
        $add = static function (string $kind, int $id, string $reason, string $severity, string $message) use (&$conditions): void {
            $conditions["{$kind}:{$id}:{$reason}"] = ['kind' => $kind, 'resource_id' => $id, 'reason' => $reason, 'severity' => $severity, 'message' => $message];
        };

        foreach ($equipment as $e) {
            $status = EquipmentStatus::from($e['status']);
            $id = (int) $e['id'];
            if ($status === EquipmentStatus::Furtado) {
                $add('EQUIPAMENTO', $id, 'FURTO', 'CRITICA', "{$e['code']}: registrado como furtado.");
            }
            if ($status === EquipmentStatus::Falha) {
                $add('EQUIPAMENTO', $id, 'FALHA', 'ALTA', "{$e['code']}: mau funcionamento.");
            }
            $signal = Freshness::of(Time::fromDb($e['last_seen_at'] ?? null), $now, $status->expectsSignal());
            if ($signal === Freshness::SemInformacao) {
                $add('EQUIPAMENTO', $id, 'SEM_COMUNICACAO', 'MEDIA', "{$e['code']}: sem sinal há mais de " . (Freshness::SIGNAL_STALE / 60) . ' min (situação não confirmada).');
            }
        }

        $today = Time::localDate($now);
        $codes = array_column($equipment, 'code', 'id');
        foreach ($openOrders as $o) {
            $id = (int) $o['equipment_id'];
            if (!isset($codes[$id])) {
                continue;
            }
            $urgency = MaintenanceUrgency::classify((string) $o['priority'], $o['due_date'] ?? null, $today);
            if ($urgency === MaintenanceUrgency::Imediata) {
                $add('EQUIPAMENTO', $id, 'MANUTENCAO_IMEDIATA', 'ALTA', "{$codes[$id]}: manutenção imediata pendente.");
            } elseif ($urgency === MaintenanceUrgency::Vencida) {
                $add('EQUIPAMENTO', $id, 'MANUTENCAO_VENCIDA', 'MEDIA', "{$codes[$id]}: manutenção programada vencida em {$o['due_date']}.");
            }
        }

        foreach ($agents as $a) {
            $status = AgentStatus::from($a['status']);
            $position = Freshness::of(Time::fromDb($a['pos_observed_at'] ?? null), $now, $status->isActive(), Freshness::POSITION_CURRENT, Freshness::POSITION_STALE);
            if ($position === Freshness::SemInformacao) {
                $add('AGENTE', (int) $a['id'], 'SEM_POSICAO', 'MEDIA', "{$a['call_sign']}: em atividade sem posição recente.");
            }
        }

        return $conditions;
    }
}
