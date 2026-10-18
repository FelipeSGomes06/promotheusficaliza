<?php

declare(strict_types=1);

namespace Fiscaliza\Demo;

use Fiscaliza\Application\DuplicateEvent;
use Fiscaliza\Application\EventIngestor;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Time;
use Fiscaliza\Services;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Simulador de campo: cada ciclo gera observações fictícias e as entrega pelos MESMOS casos de uso de ingestão,
 * persistindo no Firebird (nada é simulado só na interface).
 *
 * - batimentos de sinal dos equipamentos que comunicam (exceto os "silenciosos" do cenário);
 * - falhas e recuperações de telemetria com propensão por equipamento;
 * - deslocamento de agentes em atividade e de veículos/patinetes (histórico de posição a cada 6 ciclos);
 * - a cada 12 ciclos reenvia um evento já entregue (duplicata) e entrega um evento atrasado, para demonstrar
 *   que nenhum dos dois altera o estado atual.
 *
 * Ciclos numerados e semente fixa: a sequência de decisões é reproduzível.
 */
final class Simulator
{
    private const FAILURE_PER_TICK = 0.0004;
    private const RECOVERY_PER_TICK = 0.006;
    private const MAX_RADIUS = 0.009; // ~1 km em torno da base

    public function __construct(private readonly Services $services, private readonly int $seed) {}

    /** @return array<string, int|string> */
    public function tick(): array
    {
        $meta = $this->services->metaRepository();
        $tickNo = $meta->number('sim_tick') + 1;
        $rng = new Randomizer(new Xoshiro256StarStar(hash('sha256', "sim:{$this->seed}:{$tickNo}", true)));
        $ingest = $this->services->ingestor();
        $now = $this->services->clock()->now();
        $nowDb = Time::toDb($now);
        $stats = ['ciclo' => $tickNo, 'sinais' => 0, 'aplicados' => 0, 'historico' => 0, 'duplicados' => 0, 'posicoes' => 0];
        $count = static function (string $outcome) use (&$stats): void {
            $key = match ($outcome) {
                EventIngestor::APPLIED => 'aplicados',
                EventIngestor::HISTORICAL => 'historico',
                default => 'duplicados',
            };
            $stats[$key]++;
        };
        $deliver = static function (callable $send) use ($count): void {
            try {
                $count($send());
            } catch (DuplicateEvent) {
                $count(EventIngestor::DUPLICATE);
            }
        };

        $equipment = $this->services->equipmentRepository()->allActive();
        $this->services->database()->transaction(function () use ($equipment, $nowDb, &$stats): void {
            foreach ($equipment as $e) {
                if (EquipmentStatus::from($e['status'])->expectsSignal() && !in_array($e['serial_number'], DemoSeeder::SILENT_SERIALS, true)) {
                    $this->services->equipmentRepository()->touchSignal((int) $e['id'], $nowDb);
                    $stats['sinais']++;
                }
            }
        });

        foreach ($equipment as $e) {
            $status = EquipmentStatus::from($e['status']);
            $fragility = DemoScenario::fragility($e['serial_number'], $e['code']);
            $uid = "sim-{$this->seed}-{$tickNo}-eq{$e['id']}";
            if ($status === EquipmentStatus::Operacional && $rng->nextFloat() < self::FAILURE_PER_TICK * $fragility) {
                $deliver(fn() => $ingest->equipmentStatus($uid, (int) $e['id'], EquipmentStatus::Falha, $now, 'falha detectada pela telemetria simulada'));
            } elseif ($status === EquipmentStatus::Falha && $rng->nextFloat() < self::RECOVERY_PER_TICK) {
                $deliver(fn() => $ingest->equipmentStatus($uid, (int) $e['id'], EquipmentStatus::Operacional, $now, 'equipamento voltou a responder (telemetria simulada)'));
            }
            if ((EquipmentCatalog::get($e['type_code'])->mobile ?? false) && $status === EquipmentStatus::Operacional) {
                [$lat, $lng] = $this->step($rng, $e['pos_lat'] ?? $e['lat'], $e['pos_lng'] ?? $e['lng'], (float) $e['lat'], (float) $e['lng']);
                $deliver(fn() => $ingest->position("{$uid}-pos", 'EQUIPAMENTO', (int) $e['id'], $lat, $lng, $now, $tickNo % 6 === 0));
                $stats['posicoes']++;
            }
        }

        foreach ($this->services->agentRepository()->allActive() as $g) {
            $status = AgentStatus::from($g['status']);
            $uid = "sim-{$this->seed}-{$tickNo}-ag{$g['id']}";
            if ($status === AgentStatus::EmAtividade && $rng->nextFloat() < 0.003) {
                $this->services->agents()->changeStatus((int) $g['id'], AgentStatus::EmPausa->value, null, 'SIMULADOR', 'pausa simulada');
            } elseif ($status === AgentStatus::EmPausa && $rng->nextFloat() < 0.03) {
                $this->services->agents()->changeStatus((int) $g['id'], AgentStatus::EmAtividade->value, null, 'SIMULADOR', 'retorno simulado');
            }
            // Um agente do cenário segue "em atividade sem posição recente": seu rádio simulado não transmite.
            if ($status === AgentStatus::EmAtividade && $g['call_sign'] !== 'Hermes 06') {
                [$lat, $lng] = $this->step($rng, $g['pos_lat'] ?? $g['base_lat'], $g['pos_lng'] ?? $g['base_lng'], (float) $g['base_lat'], (float) $g['base_lng']);
                $deliver(fn() => $ingest->position("{$uid}-pos", 'AGENTE', (int) $g['id'], $lat, $lng, $now, $tickNo % 6 === 0));
                $stats['posicoes']++;
            }
        }

        if ($tickNo % 12 === 0 && $equipment !== []) {
            // Reenvio de um evento de posição já gravado (duplicata) e um evento de situação atrasado 20 minutos.
            $previous = $this->services->database()->value("SELECT FIRST 1 EVENT_UID FROM RESOURCE_EVENT WHERE SOURCE = 'SIMULADOR' ORDER BY ID DESC");
            if (is_string($previous) && str_starts_with(rtrim($previous), 'sim-')) {
                $deliver(fn() => $this->services->ingestor()->equipmentStatus(rtrim($previous), (int) $equipment[0]['id'], EquipmentStatus::Falha, $now, 'reenvio'));
            }
            $target = $equipment[$rng->getInt(0, count($equipment) - 1)];
            if (EquipmentStatus::from($target['status'])->expectsSignal()) {
                $late = $now->modify('-20 minutes');
                $reported = $target['status'] === 'OPERACIONAL' ? EquipmentStatus::Falha : EquipmentStatus::Operacional;
                if ((string) $target['status_changed_at'] > Time::toDb($late)) {
                    $deliver(fn() => $ingest->equipmentStatus("sim-{$this->seed}-{$tickNo}-late", (int) $target['id'], $reported, $late, 'leitura atrasada do concentrador (simulada)'));
                }
            }
        }

        $this->services->database()->transaction(function () use ($meta, $tickNo, $nowDb): void {
            $this->services->alerts()->reconcile();
            $meta->set('sim_tick', null, $tickNo, $nowDb);
            $meta->set('last_sim_tick_at', $nowDb, null, $nowDb);
        });

        return $stats;
    }

    /**
     * Passo aleatório limitado a um raio em torno da base (posições sempre plausíveis na área urbana).
     *
     * @return array{0: string, 1: string}
     */
    private function step(Randomizer $rng, mixed $lat, mixed $lng, float $baseLat, float $baseLng): array
    {
        $lat = (float) $lat + ($rng->nextFloat() - 0.5) * 0.0012;
        $lng = (float) $lng + ($rng->nextFloat() - 0.5) * 0.0012;
        $lat = max($baseLat - self::MAX_RADIUS, min($baseLat + self::MAX_RADIUS, $lat));
        $lng = max($baseLng - self::MAX_RADIUS, min($baseLng + self::MAX_RADIUS, $lng));

        return [number_format($lat, 6, '.', ''), number_format($lng, 6, '.', '')];
    }
}
