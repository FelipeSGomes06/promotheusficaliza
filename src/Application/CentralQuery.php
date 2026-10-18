<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\Indicators;
use Fiscaliza\Domain\MaintenanceUrgency;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\AlertRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\EventRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;
use Fiscaliza\Infrastructure\Repository\MetaRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/**
 * Instantâneo da central: recursos, indicadores, resumo por bairro, manutenções e alertas, todos lidos numa única
 * transação SNAPSHOT e derivados do mesmo conjunto filtrado — mapa, lista e cartões nunca divergem.
 */
final class CentralQuery
{
    public function __construct(
        private readonly Database $db,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly MaintenanceRepository $maintenance,
        private readonly AlertRepository $alerts,
        private readonly EventRepository $events,
        private readonly MetaRepository $meta,
        private readonly ReferenceRepository $references,
        private readonly Clock $clock,
        private readonly int $simIntervalSeconds = 5,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(Filters $filters): array
    {
        return $this->db->transaction(function () use ($filters): array {
            $now = $this->clock->now();
            $today = Time::localDate($now);
            $equipmentRows = $this->equipment->forCentral($filters);
            $agentRows = $this->agents->forCentral($filters);
            $orders = $this->maintenance->open(array_map(static fn($e) => (int) $e['id'], $equipmentRows));
            $alerts = $this->alerts->unresolved();
            $meta = $this->meta->all();
            $watermark = $this->events->watermark();

            $orders = array_map(static fn($o) => $o + ['urgency' => MaintenanceUrgency::classify((string) $o['priority'], $o['due_date'], $today)], $orders);
            $ordersByEquipment = [];
            foreach ($orders as $o) {
                $ordersByEquipment[(int) $o['equipment_id']][] = $o;
            }
            $alertCount = [];
            foreach ($alerts as $a) {
                $alertCount[$a['resource_kind'] . ':' . $a['resource_id']] = ($alertCount[$a['resource_kind'] . ':' . $a['resource_id']] ?? 0) + 1;
            }

            $resources = [];
            foreach ($equipmentRows as $e) {
                $resources[] = $this->equipmentResource($e, $ordersByEquipment[(int) $e['id']] ?? [], $alertCount['EQUIPAMENTO:' . $e['id']] ?? 0, $now);
            }
            foreach ($agentRows as $g) {
                $resources[] = $this->agentResource($g, $alertCount['AGENTE:' . $g['id']] ?? 0, $now);
            }

            $inSet = array_flip(array_map(static fn($r) => $r['kind'] . ':' . $r['id'], $resources));
            $visibleAlerts = array_values(array_filter($alerts, static fn($a) => isset($inSet[$a['resource_kind'] . ':' . $a['resource_id']])));
            $equipmentForKpi = array_map(static fn($r) => ['status' => $r['status'], 'freshness' => $r['freshness']], array_filter($resources, static fn($r) => $r['kind'] === 'EQUIPAMENTO'));
            $agentsForKpi = array_map(static fn($r) => ['status' => $r['status'], 'freshness' => $r['freshness']], array_filter($resources, static fn($r) => $r['kind'] === 'AGENTE'));

            $lastTick = Time::fromDb($meta['last_sim_tick_at']['text'] ?? null);

            return [
                'generated_at' => Time::iso($now),
                'filters' => $filters->toArray(),
                'scope' => ['equipment' => $filters->includesEquipment(), 'agents' => $filters->includesAgents()],
                'kpis' => Indicators::compute(array_values($equipmentForKpi), array_values($agentsForKpi)),
                'resources' => $resources,
                'regions' => $this->regionSummary($resources, $visibleAlerts),
                'maintenance' => $this->maintenanceLists($orders),
                'alerts' => array_map(static fn($a) => [
                    'id' => (int) $a['id'], 'kind' => $a['resource_kind'], 'resource_id' => (int) $a['resource_id'], 'resource_code' => $a['resource_code'],
                    'reason' => $a['reason'], 'severity' => $a['severity'], 'state' => $a['state'], 'message' => $a['message'],
                    'opened_at' => Time::iso(Time::fromDb($a['opened_at'])), 'ack_by' => $a['ack_by_name'], 'region' => $a['region_name'],
                ], $visibleAlerts),
                'sync' => [
                    'last_event_id' => $watermark['last_id'],
                    'last_event_received_at' => Time::iso(Time::fromDb($watermark['last_received_at'])),
                    'simulator_last_tick_at' => Time::iso($lastTick),
                    'simulator_running' => $lastTick !== null && $now->getTimestamp() - $lastTick->getTimestamp() <= max(30, 4 * $this->simIntervalSeconds),
                    'duplicates_ignored' => $meta['duplicates_ignored']['number'] ?? 0,
                    'late_events' => $meta['late_events']['number'] ?? 0,
                ],
            ];
        }, snapshot: true);
    }

    /**
     * @param array<string, mixed> $e
     * @param list<array<string, mixed>> $orders
     * @return array<string, mixed>
     */
    private function equipmentResource(array $e, array $orders, int $alerts, \DateTimeImmutable $now): array
    {
        $type = EquipmentCatalog::get($e['type_code']);
        $status = EquipmentStatus::from($e['status']);
        $signal = Freshness::of(Time::fromDb($e['last_seen_at']), $now, $status->expectsSignal());
        usort($orders, static fn($a, $b) => $a['urgency']->rank() <=> $b['urgency']->rank());
        $top = $orders[0] ?? null;

        // Recurso móvel: última posição observada, se houver; senão a base. Fixo: instalação ou planejado.
        $usePosition = ($type->mobile ?? false) && $e['pos_lat'] !== null;
        $position = $usePosition
            ? ['lat' => $e['pos_lat'], 'lng' => $e['pos_lng'], 'kind' => 'ULTIMA_POSICAO', 'observed_at' => Time::iso(Time::fromDb($e['pos_observed_at'])),
                'freshness' => Freshness::of(Time::fromDb($e['pos_observed_at']), $now, true, Freshness::POSITION_CURRENT, Freshness::POSITION_STALE)->value]
            : ['lat' => $e['lat'], 'lng' => $e['lng'], 'kind' => $e['location_kind'], 'observed_at' => null, 'freshness' => null];

        return [
            'kind' => 'EQUIPAMENTO',
            'id' => (int) $e['id'],
            'code' => $e['code'],
            'category' => $e['type_code'],
            'category_label' => $type->label ?? $e['type_code'],
            'title' => $e['code'],
            'subtitle' => trim($e['manufacturer'] . ' ' . $e['model']),
            'status' => $status->value,
            'status_label' => $status->label(),
            'glyph' => $status->glyph(),
            'freshness' => $signal->value,
            'freshness_label' => $signal->label(),
            'last_signal_at' => Time::iso(Time::fromDb($e['last_seen_at'])),
            'position' => $position,
            'base' => $usePosition ? ['lat' => $e['lat'], 'lng' => $e['lng']] : null,
            'agency' => $e['agency_name'],
            'agency_id' => (int) $e['agency_id'],
            'region' => $e['region_name'],
            'region_id' => $e['region_id'] === null ? null : (int) $e['region_id'],
            'address' => implode(' · ', array_filter([$e['street'], $e['address_ref']])),
            'highlights' => array_slice($type?->describe($e['specs']) ?? [], 0, 3),
            'maintenance' => $top === null ? null : [
                'urgency' => $top['urgency']->value, 'label' => $top['urgency']->label(), 'due_date' => $top['due_date'],
                'reason' => $top['reason'], 'order_id' => (int) $top['id'], 'state' => $top['state'], 'open_orders' => count($orders),
            ],
            'alerts' => $alerts,
        ];
    }

    /**
     * @param array<string, mixed> $g
     * @return array<string, mixed>
     */
    private function agentResource(array $g, int $alerts, \DateTimeImmutable $now): array
    {
        $status = AgentStatus::from($g['status']);
        $role = AgentRole::from($g['role']);
        $freshness = Freshness::of(Time::fromDb($g['pos_observed_at']), $now, $status->isOnShift(), Freshness::POSITION_CURRENT, Freshness::POSITION_STALE);
        $hasPosition = $g['pos_lat'] !== null && $status->isOnShift();

        return [
            'kind' => 'AGENTE',
            'id' => (int) $g['id'],
            'code' => $g['code'],
            'category' => $role->category(),
            'category_label' => $role->label(),
            'title' => $g['call_sign'],
            'subtitle' => $role->label() . ' · ' . $g['code'],
            'status' => $status->value,
            'status_label' => $status->label(),
            'glyph' => $status->glyph(),
            'freshness' => $freshness->value,
            'freshness_label' => $freshness->label(),
            'last_signal_at' => Time::iso(Time::fromDb($g['pos_observed_at'])),
            'position' => $hasPosition
                ? ['lat' => $g['pos_lat'], 'lng' => $g['pos_lng'], 'kind' => 'ULTIMA_POSICAO', 'observed_at' => Time::iso(Time::fromDb($g['pos_observed_at'])), 'freshness' => $freshness->value]
                : ['lat' => $g['base_lat'], 'lng' => $g['base_lng'], 'kind' => 'BASE', 'observed_at' => null, 'freshness' => null],
            'base' => ['lat' => $g['base_lat'], 'lng' => $g['base_lng'], 'name' => $g['base_name']],
            'agency' => $g['agency_name'],
            'agency_id' => (int) $g['agency_id'],
            'region' => $g['region_name'],
            'region_id' => $g['region_id'] === null ? null : (int) $g['region_id'],
            'address' => implode(' · ', array_filter([$g['base_name'], $g['base_street']])),
            'highlights' => [],
            'maintenance' => null,
            'alerts' => $alerts,
        ];
    }

    /**
     * Consolidação por bairro (recursos sem bairro ficam em "Sem bairro").
     *
     * @param list<array<string, mixed>> $resources
     * @param list<array<string, mixed>> $alerts
     * @return list<array<string, mixed>>
     */
    private function regionSummary(array $resources, array $alerts): array
    {
        $regions = [];
        $zones = array_column($this->references->regions(), 'zone', 'id');
        foreach ($resources as $r) {
            $key = $r['region_id'] ?? 0;
            $regions[$key] ??= ['region_id' => $r['region_id'], 'name' => $r['region'] ?? 'Sem bairro', 'zone' => $zones[$key] ?? null,
                'equipment' => 0, 'operational' => 0, 'in_service' => 0, 'unavailable' => 0, 'urgent_maintenance' => 0, 'agents_active' => 0, 'alerts' => 0];
            if ($r['kind'] === 'EQUIPAMENTO') {
                $status = EquipmentStatus::from($r['status']);
                $regions[$key]['equipment']++;
                $regions[$key]['operational'] += (int) ($status === EquipmentStatus::Operacional);
                $regions[$key]['in_service'] += (int) $status->isInService();
                $regions[$key]['unavailable'] += (int) $status->isUnavailable();
                $regions[$key]['urgent_maintenance'] += (int) in_array($r['maintenance']['urgency'] ?? null, ['IMEDIATA', 'VENCIDA'], true);
            } else {
                $regions[$key]['agents_active'] += (int) ($r['status'] === AgentStatus::EmAtividade->value);
            }
            $regions[$key]['alerts'] += $r['alerts'];
        }
        foreach ($regions as &$region) {
            $region['availability'] = $region['in_service'] === 0 ? null : round($region['operational'] / $region['in_service'], 4);
        }
        unset($region);
        usort($regions, static fn($a, $b) => [$b['unavailable'] + $b['urgent_maintenance'], $a['name']] <=> [$a['unavailable'] + $a['urgent_maintenance'], $b['name']]);

        return $regions;
    }

    /**
     * @param list<array<string, mixed>> $orders já com 'urgency'
     * @return array{urgent: list<array<string, mixed>>, upcoming: list<array<string, mixed>>}
     */
    private function maintenanceLists(array $orders): array
    {
        $shape = static fn($o) => [
            'order_id' => (int) $o['id'], 'equipment_id' => (int) $o['equipment_id'], 'equipment_code' => $o['equipment_code'], 'kind' => $o['kind'],
            'urgency' => $o['urgency']->value, 'urgency_label' => $o['urgency']->label(), 'due_date' => $o['due_date'], 'reason' => $o['reason'],
            'state' => $o['state'], 'region' => $o['region_name'],
        ];

        return [
            'urgent' => array_values(array_map($shape, array_filter($orders, static fn($o) => $o['urgency']->isUrgent()))),
            'upcoming' => array_values(array_map($shape, array_filter($orders, static fn($o) => $o['urgency'] === MaintenanceUrgency::Proxima))),
        ];
    }
}
