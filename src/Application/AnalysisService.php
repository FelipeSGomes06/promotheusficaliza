<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use DateTimeImmutable;
use Fiscaliza\Domain\AttentionModel;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AnalysisRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\EventRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;

/**
 * Executa a análise de atenção preventiva (7 dias) e a avaliação retrospectiva com separação temporal:
 *
 * - janela de AJUSTE: cortes semanais de 150 a 63 dias atrás — só aqui se escolhe o limiar de "ALTA";
 * - janela de AVALIAÇÃO: cortes semanais de 56 a 7 dias atrás — o limiar já fixado é medido e comparado com
 *   a referência simples "falhou nos últimos 30 dias";
 * - em cada corte as características usam só o que o sistema já sabia: eventos RECEBIDOS antes do corte (um evento
 *   que ocorreu antes, mas chegou depois, não entra), ordens registradas antes dele, a periodicidade preventiva
 *   vigente naquele instante (reconstruída pelo histórico de alterações) e os equipamentos que existiam e não
 *   estavam arquivados; o rótulo é "houve falha (pela ocorrência) nos 7 dias seguintes".
 *
 * Com dados sintéticos, as métricas mostram coerência do método no cenário, não acurácia real.
 */
final class AnalysisService
{
    private const FIT_FROM_DAYS = 150;
    private const FIT_TO_DAYS = 63;
    private const EVAL_FROM_DAYS = 56;
    private const EVAL_TO_DAYS = 7;

    private bool $loaded = false;
    /** @var array<int, list<array{ts: int, known: int, de: string, para: string}>> */
    private array $timeline = [];
    /** @var array<int, list<array{ts: int, known: int}>> */
    private array $failures = [];
    /** @var array<int, list<int>> */
    private array $preventiveDone = [];
    /** @var array<int, list<int>> */
    private array $maintenanceDone = [];
    /** @var array<int, list<array{opened: int, closed: ?int, due: string}>> */
    private array $preventiveOpen = [];
    /** @var array<int, list<array{ts: int, before: ?int}>> periodicidade anterior a cada alteração, em ordem */
    private array $intervalChanges = [];

    public function __construct(
        private readonly Database $db,
        private readonly AnalysisRepository $runs,
        private readonly EquipmentRepository $equipment,
        private readonly EventRepository $events,
        private readonly MaintenanceRepository $orders,
        private readonly Clock $clock,
    ) {}

    /** @return array{id: int, summary: array<string, int>} */
    public function run(?int $actorId): array
    {
        $now = $this->clock->now();
        $statusEvents = $this->load($now);
        $population = $this->equipment->allIncludingArchived();
        $equipment = array_values(array_filter($population, static fn(array $e): bool => $e['archived_at'] === null));

        $fitCutoffs = $this->cutoffs($now, self::FIT_FROM_DAYS, self::FIT_TO_DAYS);
        $evalCutoffs = $this->cutoffs($now, self::EVAL_FROM_DAYS, self::EVAL_TO_DAYS);

        // Ajuste: escolhe o limiar de ALTA que maximiza F1 na janela de ajuste (empate: o menor limiar).
        // F1 indefinido (null: nenhuma falha e nenhum alerta) nunca vence um valor definido.
        $fit = [];
        foreach (AttentionModel::THRESHOLD_GRID as $threshold) {
            $fit[$threshold] = AttentionModel::metrics($this->pairs($population, $fitCutoffs, $threshold));
        }
        $chosen = AttentionModel::THRESHOLD_GRID[0];
        foreach ($fit as $threshold => $m) {
            if (($m['f1'] ?? -1) > ($fit[$chosen]['f1'] ?? -1)) {
                $chosen = $threshold;
            }
        }

        $evaluation = [
            'note' => 'Avaliação retrospectiva em histórico SINTÉTICO: mede a coerência do método no cenário gerado, não acurácia real.',
            'fit_window' => ['from' => Time::iso(end($fitCutoffs) ?: null), 'to' => Time::iso($fitCutoffs[0] ?? null), 'cutoffs' => count($fitCutoffs)],
            'eval_window' => ['from' => Time::iso(end($evalCutoffs) ?: null), 'to' => Time::iso($evalCutoffs[0] ?? null), 'cutoffs' => count($evalCutoffs)],
            'fit_grid' => $fit,
            'chosen_threshold' => $chosen,
            'fit_note' => $fit[$chosen]['positives'] === 0 ? 'Nenhuma falha na janela de ajuste: o F1 não distingue os limiares e vale o menor da grade (regra de empate).' : null,
            'model' => AttentionModel::metrics($this->pairs($population, $evalCutoffs, $chosen)),
            'baseline_recent_failure' => AttentionModel::metrics($this->pairs($population, $evalCutoffs, $chosen, baseline: true)),
        ];

        $results = [];
        foreach ($equipment as $e) {
            $features = $this->features($e, $now);
            if ($features === null) {
                continue;
            }
            $results[] = ['equipment_id' => (int) $e['id'], 'history_days' => (int) $features['history_days']] + AttentionModel::assess($features, $chosen);
        }

        $first = $statusEvents[0]['occurred_at'] ?? null;
        $id = $this->db->transaction(function () use ($now, $first, $statusEvents, $chosen, $evaluation, $actorId, $results): int {
            $runId = $this->runs->insertRun([
                'method' => AttentionModel::METHOD, 'as_of' => Time::toDb($now), 'horizon' => AttentionModel::HORIZON_DAYS,
                'data_from' => $first, 'data_to' => Time::toDb($now), 'events_used' => count($statusEvents),
                'params' => ['high_threshold' => $chosen, 'moderate_threshold' => intdiv($chosen, 2), 'min_history_days' => AttentionModel::MIN_HISTORY_DAYS,
                    'weights' => AttentionModel::WEIGHTS, 'long_window_days' => AttentionModel::LONG_WINDOW_DAYS],
                'evaluation' => $evaluation, 'created_at' => Time::toDb($now), 'created_by' => $actorId,
            ]);
            foreach ($results as $r) {
                $this->runs->insertResult($runId, $r);
            }

            return $runId;
        });

        $summary = array_count_values(array_column($results, 'attention'));
        ksort($summary);

        return ['id' => $id, 'summary' => $summary];
    }

    /**
     * Lê o histórico recebido até $until e monta os índices usados por features(). Chamado por run(); testes e
     * diagnósticos chamam diretamente antes de consultar características.
     *
     * @return list<array<string, mixed>> eventos de situação usados
     */
    public function load(DateTimeImmutable $until): array
    {
        $statusEvents = $this->events->equipmentStatusHistory(Time::toDb($until));
        $this->index($statusEvents, $this->orders->all(), $this->events->equipmentIntervalChanges(Time::toDb($until)));
        $this->loaded = true;

        return $statusEvents;
    }

    /**
     * Características de um equipamento num instante de corte, usando somente o que o sistema sabia antes dele.
     *
     * @param array<string, mixed> $e
     * @return array<string, mixed>|null null se o equipamento ainda não existia ou já estava arquivado no corte
     */
    public function features(array $e, DateTimeImmutable $cutoff): ?array
    {
        if (!$this->loaded) {
            throw new \LogicException('Chame load() antes de features().');
        }
        $id = (int) $e['id'];
        $t = $cutoff->getTimestamp();
        $created = Time::fromDb($e['created_at'])?->getTimestamp() ?? $t;
        $archived = Time::fromDb($e['archived_at'] ?? null)?->getTimestamp();
        if ($created >= $t || ($archived !== null && $archived <= $t)) {
            return null;
        }
        $status = (string) $e['status'];
        $changes = $this->timeline[$id] ?? [];
        if ($changes !== []) {
            $status = $changes[0]['de'];
            foreach ($changes as $c) {
                if ($c['ts'] >= $t) {
                    break;
                }
                if ($c['known'] < $t) {
                    $status = $c['para'];
                }
            }
        }
        $last = static fn(array $list): ?int => ($before = array_filter($list, static fn(int $ts) => $ts < $t)) === [] ? null : max($before);
        $lastPreventive = $last($this->preventiveDone[$id] ?? []);
        $lastMaintenance = $last($this->maintenanceDone[$id] ?? []);
        $failures = array_column(array_filter($this->failures[$id] ?? [], static fn(array $f) => $f['ts'] < $t && $f['known'] < $t), 'ts');
        $today = Time::localDate($cutoff);
        $overdue = null;
        foreach ($this->preventiveOpen[$id] ?? [] as $o) {
            if ($o['opened'] < $t && ($o['closed'] === null || $o['closed'] >= $t) && $o['due'] < $today) {
                $overdue = $overdue === null ? $o['due'] : min($overdue, $o['due']);
            }
        }
        $days = static fn(?int $ts): ?float => $ts === null ? null : ($t - $ts) / 86400;
        // Sem preventiva registrada: conta desde o início do histórico (instalação ou cadastro).
        $sincePreventive = $days($lastPreventive) ?? $days($created);

        return [
            'history_days' => ($t - $created) / 86400,
            'status_at' => $status,
            'failures_30' => count(array_filter($failures, static fn(int $ts) => $ts >= $t - 30 * 86400)),
            'failures_31_180' => count(array_filter($failures, static fn(int $ts) => $ts < $t - 30 * 86400 && $ts >= $t - AttentionModel::LONG_WINDOW_DAYS * 86400)),
            'interval' => $this->intervalAt($e, $t),
            'days_since_preventive' => $sincePreventive,
            'days_since_maintenance' => $days($lastMaintenance) ?? $days($created),
            'overdue_preventive_since' => $overdue,
        ];
    }

    /**
     * Periodicidade vigente no instante $t: o valor "antes" da primeira alteração registrada a partir de $t;
     * sem alteração posterior, o valor atual do cadastro. Toda mudança dessa coluna passa por EquipmentService::update,
     * que registra antes/depois no histórico.
     *
     * @param array<string, mixed> $e
     */
    private function intervalAt(array $e, int $t): ?int
    {
        foreach ($this->intervalChanges[(int) $e['id']] ?? [] as $change) {
            if ($change['ts'] >= $t) {
                return $change['before'];
            }
        }

        return $e['maint_interval_days'] === null ? null : (int) $e['maint_interval_days'];
    }

    /**
     * @param list<array<string, mixed>> $equipment todos, inclusive arquivados (features() exclui quem não existia no corte)
     * @param list<DateTimeImmutable> $cutoffs
     * @return list<array{flag: bool, failed: bool}>
     */
    private function pairs(array $equipment, array $cutoffs, int $threshold, bool $baseline = false): array
    {
        $pairs = [];
        foreach ($cutoffs as $cutoff) {
            $t = $cutoff->getTimestamp();
            foreach ($equipment as $e) {
                $f = $this->features($e, $cutoff);
                if ($f === null) {
                    continue;
                }
                $assessment = AttentionModel::assess($f, $threshold);
                // Só entram pares classificáveis: operacional no corte e com histórico mínimo.
                if (!in_array($assessment['attention'], ['ALTA', 'MODERADA', 'BAIXA'], true) || $assessment['score'] === null) {
                    continue;
                }
                $failed = count(array_filter($this->failures[(int) $e['id']] ?? [], static fn(array $f) => $f['ts'] >= $t && $f['ts'] < $t + AttentionModel::HORIZON_DAYS * 86400)) > 0;
                $pairs[] = ['flag' => $baseline ? $f['failures_30'] > 0 : $assessment['attention'] === 'ALTA', 'failed' => $failed];
            }
        }

        return $pairs;
    }

    /** @return list<DateTimeImmutable> do mais recente para o mais antigo */
    private function cutoffs(DateTimeImmutable $now, int $fromDays, int $toDays): array
    {
        $cutoffs = [];
        for ($d = $toDays; $d <= $fromDays; $d += 7) {
            $cutoffs[] = $now->modify("-{$d} days");
        }

        return $cutoffs;
    }

    /**
     * @param list<array<string, mixed>> $statusEvents
     * @param list<array<string, mixed>> $orders
     * @param list<array<string, mixed>> $intervalChanges
     */
    private function index(array $statusEvents, array $orders, array $intervalChanges): void
    {
        $this->timeline = $this->failures = $this->preventiveDone = $this->maintenanceDone = $this->preventiveOpen = $this->intervalChanges = [];
        foreach ($statusEvents as $ev) {
            $id = (int) $ev['resource_id'];
            $ts = Time::fromDb($ev['occurred_at'])?->getTimestamp() ?? 0;
            $known = Time::fromDb($ev['received_at'])?->getTimestamp() ?? $ts;
            $de = (string) ($ev['payload']['de'] ?? '');
            $para = (string) ($ev['payload']['para'] ?? '');
            // Eventos HISTORICO não mudaram o estado e por isso não entram na linha do tempo.
            if ($para === '' || ($ev['outcome'] ?? 'APLICADO') !== 'APLICADO') {
                continue;
            }
            $this->timeline[$id][] = ['ts' => $ts, 'known' => $known, 'de' => $de, 'para' => $para];
            if ($para === 'FALHA') {
                $this->failures[$id][] = ['ts' => $ts, 'known' => $known];
            }
        }
        foreach ($orders as $o) {
            $id = (int) $o['equipment_id'];
            $closed = Time::fromDb($o['closed_at'])?->getTimestamp();
            if ($o['state'] === 'CONCLUIDA' && $closed !== null) {
                $this->maintenanceDone[$id][] = $closed;
                if ($o['kind'] === 'PREVENTIVA') {
                    $this->preventiveDone[$id][] = $closed;
                }
            }
            if ($o['kind'] === 'PREVENTIVA' && $o['due_date'] !== null) {
                $this->preventiveOpen[$id][] = ['opened' => Time::fromDb($o['opened_at'])?->getTimestamp() ?? 0, 'closed' => $closed, 'due' => (string) $o['due_date']];
            }
        }
        foreach ($intervalChanges as $ev) {
            $change = $ev['payload']['alteracoes']['maint_interval_days'] ?? null;
            if (!is_array($change)) {
                continue;
            }
            $before = $change['antes'] ?? null;
            $this->intervalChanges[(int) $ev['resource_id']][] = [
                'ts' => Time::fromDb($ev['received_at'])?->getTimestamp() ?? 0,
                'before' => $before === null || $before === '' ? null : (int) $before,
            ];
        }
    }
}
