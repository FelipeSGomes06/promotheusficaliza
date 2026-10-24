<?php

declare(strict_types=1);

namespace Fiscaliza\Demo;

use DateTimeImmutable;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Repository\MetaRepository;
use Fiscaliza\Services;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Carga do cenário sintético e geração de histórico reproduzível (mesma semente => mesmo cenário relativo à data
 * da carga). O histórico segue um processo simples e documentado (docs/analise-preventiva.md):
 *
 * - risco diário de falha = BASE × propensão × (1 + 2 × atraso relativo da preventiva) × (1 + 0,6 × falhas em 30 dias);
 * - ~28% das preventivas atrasam 8 a 30 dias; falha abre corretiva imediata, que é iniciada e concluída depois;
 * - eventos de situação, ordens e alterações são gravados como se tivessem ocorrido nas datas simuladas (origem SEMENTE).
 *
 * Executa com a identidade de migração (precisa apagar o histórico ao recarregar).
 */
final class DemoSeeder
{
    public const HISTORY_DAYS = 240;
    private const BASE_DAILY_FAILURE = 0.004;
    /** Equipamentos que param de enviar sinal (demonstra "sem comunicação" sem afirmar falha). */
    public const SILENT_SERIALS = ['OPB-1080-2290', 'TXC-0533'];
    /** Equipamentos cujo concentrador entrega o sinal com atraso fixo (demonstra "sinal atrasado" com o simulador ligado). */
    public const LAGGING_SERIALS = ['OPD-4K-1199'];
    public const LAG_MINUTES = 25;

    private Randomizer $rng;
    private DateTimeImmutable $now;
    private int $eventCounter = 0;

    public function __construct(private readonly Services $services, private readonly int $seed, private readonly string $profile = DemoScenario::COMPACT) {}

    /** @return array<string, int> resumo do que foi gravado */
    public function run(bool $wipeFirst): array
    {
        $db = $this->services->database();
        if ($wipeFirst) {
            $this->wipe();
        }
        $this->now = $this->services->clock()->now();
        $this->rng = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'promotheus-fiscaliza:' . $this->seed, true)));

        $counts = $db->transaction(function (): array {
            $refs = $this->services->referenceRepository();
            $agencyIds = [];
            foreach (DemoScenario::agencies() as [$code, $name, $kind]) {
                $agencyIds[$code] = $refs->insertAgency($code, $name, $kind);
            }
            $regionIds = [];
            foreach (DemoScenario::regions() as [$name, $zone, $lat, $lng]) {
                $regionIds[$name] = $refs->insertRegion($name, $zone, $lat, $lng);
            }
            $counts = ['órgãos' => count($agencyIds), 'bairros' => count($regionIds), 'equipamentos' => 0, 'agentes' => 0, 'eventos' => 0, 'ordens de manutenção' => 0];
            foreach (DemoScenario::equipment($this->profile) as $item) {
                [$events, $orders] = $this->seedEquipment($item, $agencyIds, $regionIds);
                $counts['equipamentos']++;
                $counts['eventos'] += $events;
                $counts['ordens de manutenção'] += $orders;
            }
            foreach (DemoScenario::agents($this->profile) as $agent) {
                $counts['eventos'] += $this->seedAgent($agent, $agencyIds, $regionIds);
                $counts['agentes']++;
            }
            $meta = $this->services->metaRepository();
            $at = Time::toDb($this->now);
            $meta->set('dataset', MetaRepository::DATASET_MARKER, $this->seed, $at);
            $meta->set('profile', $this->profile, null, $at);
            $meta->set('seeded_at', $at, null, $at);
            $meta->set('duplicates_ignored', null, 0, $at);
            $meta->set('late_events', null, 1, $at);
            $meta->set('sim_tick', null, 0, $at);

            return $counts;
        });

        $alerts = $db->transaction(fn() => $this->services->alerts()->reconcile());
        $counts['alertas abertos'] = $alerts['opened'];
        $run = $this->services->analysis()->run(null);
        $counts['análise preventiva (execução)'] = $run['id'];
        $counts['multas (cenário ' . FineScenario::VERSION . ')'] = FineScenario::seed($this->services, $this->profile);

        return $counts;
    }

    /**
     * Apaga dados de domínio preservando contas de acesso (credenciais criadas pelo avaliador). Missões fictícias saem
     * junto com os agentes; as execuções importadas do Infosiga (contagens reais agregadas, URBAN_RUN…) ficam.
     */
    private function wipe(): void
    {
        $db = $this->services->database();
        $db->transaction(function () use ($db): void {
            foreach (['FINE_DOCUMENT_CHECK', 'FINE_EVENT', 'FINE', 'URBAN_MISSION_LOG', 'URBAN_MISSION', 'ANALYSIS_RESULT', 'ANALYSIS_RUN', 'ALERT_FOLLOWUP', 'ALERT', 'RESOURCE_EVENT', 'MAINTENANCE_ORDER', 'EQUIPMENT', 'AGENT', 'REGION', 'AGENCY', 'APP_META'] as $table) {
                $db->execute("DELETE FROM {$table}");
            }
        });
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, int> $agencyIds
     * @param array<string, int> $regionIds
     * @return array{0: int, 1: int} eventos e ordens gravados
     */
    private function seedEquipment(array $item, array $agencyIds, array $regionIds): array
    {
        $type = EquipmentCatalog::get($item['type']) ?? throw new \LogicException('tipo do cenário');
        [$specs, $errors] = $type->validateSpecs($item['specs']);
        if ($errors !== []) {
            throw new \LogicException("Especificação inválida no cenário ({$item['serial']}): " . json_encode($errors));
        }
        $final = $item['final'];
        $installedDays = (int) $item['installed'];
        $createdAt = $this->now->modify('-' . max(10, $installedDays) . ' days')->setTime(10, 0);
        $repo = $this->services->equipmentRepository();
        $code = $repo->nextCode($type->prefix);
        $silent = in_array($item['serial'], self::SILENT_SERIALS, true);
        $lagging = in_array($item['serial'], self::LAGGING_SERIALS, true);

        $id = $repo->insert([
            'code' => $code, 'type_code' => $item['type'], 'manufacturer' => $item['maker'], 'model' => $item['model'], 'serial_number' => $item['serial'],
            'asset_tag' => $item['serial'] === null ? null : 'PAT-' . substr(hash('crc32b', $item['serial']), 0, 6),
            'agency_id' => $agencyIds[$item['agency']], 'status' => 'OPERACIONAL', 'status_changed_at' => Time::toDb($createdAt),
            'cep' => $item['cep'], 'street' => $item['street'], 'address_ref' => $item['ref'], 'region_id' => $regionIds[$item['region']],
            'lat' => $item['lat'], 'lng' => $item['lng'],
            'location_kind' => $type->mobile ? 'BASE' : ($final === 'NAO_INSTALADO' ? 'PLANEJADO' : 'INSTALADO'),
            'maint_interval_days' => $item['interval'], 'specs' => $specs,
            'installed_on' => $final === 'NAO_INSTALADO' ? null : Time::localDate($createdAt),
            'notes' => 'Registro sintético de demonstração.', 'created_at' => Time::toDb($createdAt), 'updated_at' => Time::toDb($this->now),
        ]);
        $events = [['type' => 'CADASTRO', 'at' => $createdAt, 'summary' => "Cadastro de {$type->label} {$code} (carga sintética).", 'payload' => []]];
        $orders = [];
        $status = 'OPERACIONAL';
        $changedAt = $createdAt;

        if ($final === 'NAO_INSTALADO') {
            $status = 'NAO_INSTALADO';
            $events[0]['summary'] = "Cadastro de {$type->label} {$code}: instalação planejada (carga sintética).";
        } else {
            $stopDaysAgo = match ($final) {
                'DESATIVADO' => 40,
                'FURTADO' => 3,
                'MANUTENCAO' => 2,
                'FALHA' => 1,
                default => 0,
            };
            [$status, $changedAt, $timeline, $orders] = $this->history($item, min(self::HISTORY_DAYS, $installedDays), $stopDaysAgo);
            $events = array_merge($events, $timeline);
            $at = $this->now->modify("-{$stopDaysAgo} days")->setTime(9, 30);
            if ($final === 'DESATIVADO' || $final === 'FURTADO') {
                $events[] = $this->statusEvent($status, $final, $at, $final === 'FURTADO' ? 'registro de furto (fictício)' : 'desativado para obra na via (fictício)');
                foreach ($orders as &$order) {
                    if (in_array($order['state'], ['ABERTA', 'EM_EXECUCAO'], true)) {
                        $order['state'] = 'CANCELADA';
                        $order['closed_at'] = $at;
                        $order['resolution'] = 'Cancelada: equipamento ' . strtolower($final) . '.';
                    }
                }
                unset($order);
                [$status, $changedAt] = [$final, $at];
            } elseif ($final === 'FALHA') {
                // Falha recente ainda sem atendimento: corretiva imediata aberta, não iniciada.
                $failAt = $this->now->modify('-' . $this->rng->getInt(200, 400) . ' minutes');
                $opened = $failAt->modify('+12 minutes');
                $reason = $this->failureReason($item['type']);
                $events[] = $this->statusEvent($status, 'FALHA', $failAt, $reason);
                $events[] = ['type' => 'MANUTENCAO_ABERTA', 'at' => $opened, 'summary' => "Corretiva imediata: {$reason}.", 'payload' => []];
                $orders[] = ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'state' => 'ABERTA', 'due' => null, 'opened_at' => $opened, 'started_at' => null,
                    'closed_at' => null, 'result' => null, 'reason' => ucfirst($reason) . '.', 'resolution' => null];
                [$status, $changedAt] = ['FALHA', $failAt];
            } elseif ($final === 'MANUTENCAO') {
                $opened = $at;
                $started = $this->now->modify('-1 day')->setTime(8, 15);
                $orders[] = ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'state' => 'EM_EXECUCAO', 'due' => null, 'opened_at' => $opened, 'started_at' => $started,
                    'closed_at' => null, 'result' => null, 'reason' => 'Revisão de freios e suspensão após inspeção.', 'resolution' => null];
                $events[] = ['type' => 'MANUTENCAO_ABERTA', 'at' => $opened, 'summary' => 'Ordem corretiva imediata aberta: revisão de freios e suspensão.', 'payload' => []];
                $events[] = $this->statusEvent($status, 'MANUTENCAO', $started, 'intervenção iniciada');
                [$status, $changedAt] = ['MANUTENCAO', $started];
            }
        }

        // Estado atual: sinal recente para quem comunica; posição para móveis.
        $state = ['status' => $status, 'status_changed_at' => Time::toDb($changedAt)];
        if (in_array($status, ['OPERACIONAL', 'FALHA'], true)) {
            $state['last_seen_at'] = Time::toDb(match (true) {
                $silent => $this->now->modify('-' . $this->rng->getInt(130, 300) . ' minutes'),
                $lagging => $this->now->modify('-' . self::LAG_MINUTES . ' minutes'),
                default => $this->now->modify('-' . $this->rng->getInt(3, 90) . ' seconds'),
            });
        }
        if ($type->mobile && $status === 'OPERACIONAL') {
            $state += $this->near((float) $item['lat'], (float) $item['lng'], 0.006) + ['pos_observed_at' => Time::toDb($this->now->modify('-' . $this->rng->getInt(5, 100) . ' seconds'))];
            $state['pos_received_at'] = $state['pos_observed_at'];
        } elseif ($type->mobile && $status === 'FURTADO') {
            // Última posição conhecida, antiga: exibida como observação desatualizada, nunca como posição atual.
            $old = $this->now->modify('-3 days')->setTime(9, 10);
            $state += $this->near((float) $item['lat'], (float) $item['lng'], 0.01) + ['pos_observed_at' => Time::toDb($old), 'pos_received_at' => Time::toDb($old)];
        }
        $repo->updateState($id, $state);

        // Preventiva aberta com data fixa (vencida, próxima ou futura), no lugar da que o histórico deixou em aberto.
        $pending = $item['serial'] === null ? null : (DemoScenario::PENDING_PREVENTIVE[$item['serial']] ?? null);
        if ($pending !== null && $item['interval'] !== null && !in_array($status, ['NAO_INSTALADO', 'DESATIVADO', 'FURTADO'], true)) {
            $orders = array_values(array_filter($orders, static fn(array $o): bool => !($o['kind'] === 'PREVENTIVA' && $o['state'] === 'ABERTA')));
            $due = $this->now->modify(sprintf('%+d days', $pending));
            $orders[] = ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'state' => 'ABERTA', 'due' => Time::localDate($due),
                'opened_at' => min($this->now->modify('-1 hour'), $due->modify('-10 days')->setTime(9, 0)), 'started_at' => null, 'closed_at' => null, 'result' => null,
                'reason' => "Preventiva periódica (a cada {$item['interval']} dias).", 'resolution' => null];
        }

        $orderRepo = $this->services->maintenanceRepository();
        $db = $this->services->database();
        foreach ($orders as $o) {
            $orderId = $orderRepo->insert(['equipment_id' => $id, 'kind' => $o['kind'], 'priority' => $o['priority'], 'state' => 'ABERTA', 'due_date' => $o['due'],
                'reason' => $o['reason'], 'source' => 'SEMENTE', 'opened_at' => Time::toDb($o['opened_at']), 'opened_by' => null]);
            $db->execute('UPDATE MAINTENANCE_ORDER SET STATE = ?, STARTED_AT = ?, CLOSED_AT = ?, RESULT_STATUS = ?, RESOLUTION = ? WHERE ID = ?', [
                $o['state'], $o['started_at'] === null ? null : Time::toDb($o['started_at']), $o['closed_at'] === null ? null : Time::toDb($o['closed_at']),
                $o['result'], $o['resolution'], $orderId,
            ]);
        }

        if ($item['serial'] === 'VS5-24-00201') {
            // Exemplo permanente de evento atrasado: chegou agora, ocorreu antes do estado atual, não regride a situação.
            $events[] = ['type' => 'SITUACAO', 'at' => $changedAt->modify('-2 hours'), 'received' => $this->now->modify('-4 minutes'), 'outcome' => 'HISTORICO',
                'summary' => 'Recebido "Mau funcionamento" (evento atrasado: estado atual é mais recente): perda de sinal do laço indutivo.',
                'payload' => ['de' => $status, 'para' => 'FALHA', 'motivo' => 'perda de sinal do laço indutivo', 'ignorado_porque' => 'evento atrasado: estado atual é mais recente']];
        }

        $eventRepo = $this->services->eventRepository();
        foreach ($events as $event) {
            $eventRepo->append([
                'uid' => sprintf('seed-%s-%04d', $code, ++$this->eventCounter), 'kind' => 'EQUIPAMENTO', 'resource_id' => $id, 'type' => $event['type'],
                'source' => 'SEMENTE', 'actor' => null, 'occurred_at' => Time::toDb($event['at']), 'received_at' => Time::toDb($event['received'] ?? $event['at']),
                'outcome' => $event['outcome'] ?? 'APLICADO', 'summary' => $event['summary'], 'payload' => $event['payload'],
            ]);
        }

        return [count($events), count($orders)];
    }

    /**
     * Linha do tempo de um equipamento instalado: falhas, corretivas e preventivas (algumas atrasadas).
     *
     * @param array<string, mixed> $item
     * @return array{0: string, 1: DateTimeImmutable, 2: list<array<string, mixed>>, 3: list<array<string, mixed>>}
     */
    private function history(array $item, int $days, int $stopDaysAgo): array
    {
        $interval = $item['interval'] === null ? null : (int) $item['interval'];
        $fragility = (float) $item['fragility'];
        $start = $this->now->modify("-{$days} days")->setTime(6, 0);
        $stop = $this->now->modify("-{$stopDaysAgo} days")->setTime(6, 0);
        $status = 'OPERACIONAL';
        $changedAt = $start;
        $events = [];
        $orders = [];
        $failures = [];
        $lastPreventive = $interval === null ? null : $start->modify('-' . $this->rng->getInt(0, $interval - 1) . ' days');
        $nextDue = $lastPreventive?->modify("+{$interval} days");
        $executeOn = null;
        $busyUntil = $start;

        for ($d = $days; $d > $stopDaysAgo; $d--) {
            $day = $this->now->modify("-{$d} days")->setTime(0, 0);
            if ($day < $busyUntil->setTime(0, 0)) {
                continue;
            }
            if ($nextDue !== null && $day >= $nextDue->setTime(0, 0)) {
                $executeOn ??= $this->rng->nextFloat() < 0.72 ? $nextDue : $nextDue->modify('+' . $this->rng->getInt(8, 30) . ' days');
                if ($day >= $executeOn->setTime(0, 0)) {
                    $opened = max($start, $nextDue->modify('-10 days')->setTime($this->rng->getInt(9, 17), $this->rng->getInt(0, 59)));
                    $started = $day->setTime($this->rng->getInt(8, 12), $this->rng->getInt(0, 59));
                    $done = $started->modify('+' . $this->rng->getInt(90, 300) . ' minutes');
                    if ($done < $stop) {
                        $late = $started->setTime(0, 0) > $nextDue->setTime(0, 0);
                        $orders[] = ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'state' => 'CONCLUIDA', 'due' => Time::localDate($nextDue), 'opened_at' => $opened,
                            'started_at' => $started, 'closed_at' => $done, 'result' => 'OPERACIONAL', 'reason' => "Preventiva periódica (a cada {$interval} dias).",
                            'resolution' => $late ? 'Executada com atraso; limpeza, calibração e testes.' : 'Limpeza, calibração e testes.'];
                        $events[] = ['type' => 'MANUTENCAO_ABERTA', 'at' => $opened, 'summary' => 'Preventiva programada para ' . Time::localDate($nextDue) . '.', 'payload' => []];
                        $events[] = $this->statusEvent('OPERACIONAL', 'MANUTENCAO', $started, 'preventiva iniciada');
                        $events[] = $this->statusEvent('MANUTENCAO', 'OPERACIONAL', $done, 'preventiva concluída' . ($late ? ' com atraso' : ''));
                        [$status, $changedAt, $lastPreventive, $busyUntil] = ['OPERACIONAL', $done, $done, $done];
                        $nextDue = $done->modify("+{$interval} days");
                        $executeOn = null;
                        continue;
                    }
                }
            }

            $sincePreventive = $lastPreventive === null ? 60 : ($day->getTimestamp() - $lastPreventive->getTimestamp()) / 86400;
            $overdue = $interval === null ? 0.0 : max(0.0, ($sincePreventive - $interval) / $interval);
            $recent = count(array_filter($failures, static fn(DateTimeImmutable $f) => $f > $day->modify('-30 days')));
            $risk = self::BASE_DAILY_FAILURE * $fragility * (1 + 2 * $overdue) * (1 + 0.6 * $recent);
            if ($this->rng->nextFloat() >= $risk) {
                continue;
            }
            $failAt = $day->setTime($this->rng->getInt(0, 23), $this->rng->getInt(0, 59));
            $opened = $failAt->modify('+' . $this->rng->getInt(5, 40) . ' minutes');
            $started = $opened->modify('+' . $this->rng->getInt(4, 30) . ' hours');
            $done = $started->modify('+' . $this->rng->getInt(2, 40) . ' hours');
            $cutoff = $stopDaysAgo > 0 ? $stop : $this->now;
            if ($stopDaysAgo > 0 && $done >= $stop) {
                continue; // estado final será definido pelo cenário; não deixa reparo pela metade
            }
            $failures[] = $failAt;
            $reason = $this->failureReason($item['type']);
            $events[] = $this->statusEvent('OPERACIONAL', 'FALHA', $failAt, $reason);
            $events[] = ['type' => 'MANUTENCAO_ABERTA', 'at' => $opened, 'summary' => "Corretiva imediata: {$reason}.", 'payload' => []];
            $order = ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'state' => 'ABERTA', 'due' => null, 'opened_at' => $opened, 'started_at' => null,
                'closed_at' => null, 'result' => null, 'reason' => ucfirst($reason) . '.', 'resolution' => null];
            [$status, $changedAt] = ['FALHA', $failAt];
            if ($started < $cutoff) {
                $events[] = $this->statusEvent('FALHA', 'MANUTENCAO', $started, 'corretiva iniciada');
                $order['state'] = 'EM_EXECUCAO';
                $order['started_at'] = $started;
                [$status, $changedAt] = ['MANUTENCAO', $started];
            }
            if ($done < $cutoff) {
                $events[] = $this->statusEvent('MANUTENCAO', 'OPERACIONAL', $done, 'corretiva concluída');
                $order = array_merge($order, ['state' => 'CONCLUIDA', 'closed_at' => $done, 'result' => 'OPERACIONAL', 'resolution' => 'Componente substituído e testado.']);
                [$status, $changedAt] = ['OPERACIONAL', $done];
            }
            $orders[] = $order;
            $busyUntil = $done < $cutoff ? $done : $this->now->modify('+1 year');
        }

        // Preventiva pendente ao final: vencida (atrasada) ou programada para os próximos dias.
        if ($nextDue !== null && $stopDaysAgo === 0 && $nextDue <= $this->now->modify('+21 days')) {
            $orders[] = ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'state' => 'ABERTA', 'due' => Time::localDate($nextDue),
                'opened_at' => max($start, min($this->now->modify('-1 hour'), $nextDue->modify('-10 days'))), 'started_at' => null, 'closed_at' => null, 'result' => null,
                'reason' => "Preventiva periódica (a cada {$interval} dias).", 'resolution' => null];
        }

        return [$status, $changedAt, $events, $orders];
    }

    /**
     * @param array{0: string, 1: string, 2: string, 3: string, 4: string, 5: bool} $agent
     * @param array<string, int> $agencyIds
     * @param array<string, int> $regionIds
     */
    private function seedAgent(array $agent, array $agencyIds, array $regionIds): int
    {
        [$callSign, $role, $agency, $baseKey, $status, $recentPosition] = $agent;
        [$baseName, $street, $cep, $region, $lat, $lng] = DemoScenario::bases()[$baseKey];
        $repo = $this->services->agentRepository();
        $prefix = ['TRANSITO' => 'AGT', 'GUARDA' => 'GCM', 'POLICIA' => 'POL'][$role];
        $createdAt = $this->now->modify('-' . $this->rng->getInt(60, 400) . ' days')->setTime(9, 0);
        $changedAt = $this->now->modify('-' . $this->rng->getInt(20, 300) . ' minutes');
        $row = [
            'code' => $repo->nextCode($prefix), 'call_sign' => $callSign, 'role' => $role, 'agency_id' => $agencyIds[$agency], 'status' => $status,
            'status_changed_at' => Time::toDb($changedAt), 'base_name' => $baseName, 'base_street' => $street, 'base_ref' => 'Endereço de base ilustrativo',
            'base_cep' => $cep, 'region_id' => $regionIds[$region], 'base_lat' => $lat, 'base_lng' => $lng,
            'created_at' => Time::toDb($createdAt), 'updated_at' => Time::toDb($changedAt),
        ];
        if (in_array($status, ['EM_ATIVIDADE', 'EM_PAUSA'], true)) {
            $observed = $recentPosition ? $this->now->modify('-' . $this->rng->getInt(5, 120) . ' seconds') : $this->now->modify('-52 minutes');
            $row += $this->near((float) $lat, (float) $lng, 0.008) + ['pos_observed_at' => Time::toDb($observed), 'pos_received_at' => Time::toDb($observed)];
        }
        $id = $repo->insert($row);
        $events = $this->services->eventRepository();
        $events->append(['uid' => sprintf('seed-%s-%04d', $row['code'], ++$this->eventCounter), 'kind' => 'AGENTE', 'resource_id' => $id, 'type' => 'CADASTRO',
            'source' => 'SEMENTE', 'actor' => null, 'occurred_at' => Time::toDb($createdAt), 'received_at' => Time::toDb($createdAt), 'outcome' => 'APLICADO',
            'summary' => "Cadastro de {$callSign} (agente fictício).", 'payload' => []]);
        $events->append(['uid' => sprintf('seed-%s-%04d', $row['code'], ++$this->eventCounter), 'kind' => 'AGENTE', 'resource_id' => $id, 'type' => 'SITUACAO',
            'source' => 'SEMENTE', 'actor' => null, 'occurred_at' => Time::toDb($changedAt), 'received_at' => Time::toDb($changedAt), 'outcome' => 'APLICADO',
            'summary' => 'Início do turno simulado → ' . strtolower(str_replace('_', ' ', $status)) . '.', 'payload' => ['para' => $status]]);

        return 2;
    }

    /** @return array{type: string, at: DateTimeImmutable, summary: string, payload: array<string, string>} */
    private function statusEvent(string $from, string $to, DateTimeImmutable $at, string $reason): array
    {
        $label = static fn(string $s) => \Fiscaliza\Domain\EquipmentStatus::from($s)->label();

        return ['type' => 'SITUACAO', 'at' => $at, 'summary' => "{$label($from)} → {$label($to)}: {$reason}", 'payload' => ['de' => $from, 'para' => $to, 'motivo' => $reason]];
    }

    /** @return array{pos_lat: string, pos_lng: string} */
    private function near(float $lat, float $lng, float $spread): array
    {
        return [
            'pos_lat' => number_format($lat + ($this->rng->nextFloat() - 0.5) * $spread, 6, '.', ''),
            'pos_lng' => number_format($lng + ($this->rng->nextFloat() - 0.5) * $spread, 6, '.', ''),
        ];
    }

    private function failureReason(string $type): string
    {
        $reasons = [
            'RADAR' => ['perda de sinal do laço indutivo', 'falha no módulo de captura', 'desalinhamento do sensor', 'queda de energia no gabinete'],
            'CAMERA' => ['perda de vídeo', 'falha de foco/zoom', 'infiltração na cúpula', 'falha de rede no poste'],
            'BALANCA' => ['sensor de carga descalibrado', 'falha no módulo de pesagem'],
            'CANCELA' => ['motor da haste travado', 'falha no leitor de acesso'],
            'TOTEM' => ['tela sem resposta', 'botão de ajuda inoperante', 'falha de conectividade'],
            'VEICULO' => ['pane elétrica', 'pneu danificado', 'falha no rastreador'],
            'PATINETE' => ['bateria não carrega', 'falha no freio'],
        ][$type] ?? ['falha não especificada'];

        return $reasons[$this->rng->getInt(0, count($reasons) - 1)];
    }
}
