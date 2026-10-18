<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Indicadores da central, calculados sobre exatamente o mesmo conjunto filtrado que alimenta mapa e lista.
 *
 * - total: equipamentos não arquivados no filtro;
 * - em serviço (denominador) = operacionais + mau funcionamento + em manutenção + furtados;
 *   não instalados e desativados ficam fora do denominador e aparecem em contagens próprias;
 * - disponibilidade registrada = operacionais / em serviço;
 * - disponibilidade confirmada = operacionais com sinal ATUAL / em serviço;
 * - denominador zero => null ("não se aplica"), nunca 0% nem 100%;
 * - agentes ativos = agentes não arquivados EM_ATIVIDADE no filtro (unidade diferente; nunca somada a equipamentos).
 */
final class Indicators
{
    /**
     * @param list<array{status: string, freshness: string}> $equipment
     * @param list<array{status: string, freshness: string}> $agents
     * @return array{equipment: array<string, int|float|null>, agents: array<string, int>}
     */
    public static function compute(array $equipment, array $agents): array
    {
        $count = static fn(array $rows, callable $test): int => count(array_filter($rows, $test));
        $statusOf = static fn(array $row): EquipmentStatus => EquipmentStatus::from($row['status']);

        $operational = $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Operacional);
        $confirmed = $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Operacional && $r['freshness'] === Freshness::Atual->value);
        $inService = $count($equipment, static fn($r) => $statusOf($r)->isInService());

        return [
            'equipment' => [
                'total' => count($equipment),
                'operational' => $operational,
                'unavailable' => $count($equipment, static fn($r) => $statusOf($r)->isUnavailable()),
                'failure' => $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Falha),
                'maintenance' => $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Manutencao),
                'stolen' => $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Furtado),
                'not_installed' => $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::NaoInstalado),
                'deactivated' => $count($equipment, static fn($r) => $statusOf($r) === EquipmentStatus::Desativado),
                'in_service' => $inService,
                'operational_confirmed' => $confirmed,
                'operational_unconfirmed' => $operational - $confirmed,
                'availability' => $inService === 0 ? null : round($operational / $inService, 4),
                'availability_confirmed' => $inService === 0 ? null : round($confirmed / $inService, 4),
            ],
            'agents' => [
                'total' => count($agents),
                'active' => $count($agents, static fn($r) => $r['status'] === AgentStatus::EmAtividade->value),
                'on_pause' => $count($agents, static fn($r) => $r['status'] === AgentStatus::EmPausa->value),
                'unavailable' => $count($agents, static fn($r) => $r['status'] === AgentStatus::Indisponivel->value),
                'off_duty' => $count($agents, static fn($r) => $r['status'] === AgentStatus::ForaDeServico->value),
                'position_not_current' => $count($agents, static fn($r) => AgentStatus::from($r['status'])->isOnShift() && $r['freshness'] !== Freshness::Atual->value),
            ],
        ];
    }
}
