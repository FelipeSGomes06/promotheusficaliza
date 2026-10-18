<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;

/**
 * Ciclo de vida das ordens de manutenção: ABERTA → EM_EXECUCAO → CONCLUIDA, ou ABERTA → CANCELADA.
 *
 * Regras: agendar não tira o equipamento de operação; iniciar a intervenção o coloca "em manutenção";
 * só uma intervenção fica em execução por equipamento (serviço + índice parcial UX_MAINT_ONE_RUNNING);
 * concluir exige declarar explicitamente a situação resultante (concluir não prova que voltou a funcionar);
 * concluir uma preventiva de equipamento com periodicidade agenda a próxima.
 */
final class MaintenanceService
{
    public function __construct(
        private readonly Database $db,
        private readonly MaintenanceRepository $orders,
        private readonly EquipmentRepository $equipment,
        private readonly EquipmentService $equipmentService,
        private readonly History $history,
        private readonly AlertService $alerts,
        private readonly Clock $clock,
    ) {}

    /** @param array<string, mixed> $data */
    public function open(int $equipmentId, array $data, ?int $actorId, string $source = 'MANUAL'): int
    {
        $input = new Input($data);
        $kind = $input->choice('kind', ['CORRETIVA', 'PREVENTIVA']);
        $priority = $input->choice('priority', ['IMEDIATA', 'PROGRAMADA']);
        $due = $input->date('due_date', $priority === 'PROGRAMADA');
        $reason = $input->text('reason', 300);
        $today = Time::localDate($this->clock->now());
        if ($due !== null && $due < $today && $source === 'MANUAL') {
            $input->fail('due_date', 'A data programada não pode estar no passado.');
        }
        $input->assertValid();

        return $this->db->transaction(function () use ($equipmentId, $kind, $priority, $due, $reason, $actorId, $source): int {
            $equipment = $this->equipment->lock($equipmentId) ?? throw new NotFound('Equipamento não encontrado.');
            if ($equipment['archived_at'] !== null) {
                throw new RuleViolation('Equipamento arquivado não recebe ordens de manutenção.');
            }
            $id = $this->orders->insert([
                'equipment_id' => $equipmentId, 'kind' => (string) $kind, 'priority' => (string) $priority, 'due_date' => $due,
                'reason' => (string) $reason, 'source' => $source, 'opened_at' => Time::toDb($this->clock->now()), 'opened_by' => $actorId,
            ]);
            $when = $priority === 'IMEDIATA' ? 'imediata' : "programada para {$due}";
            $this->history->record('EQUIPAMENTO', $equipmentId, 'MANUTENCAO_ABERTA', $actorId, "Ordem #{$id} " . strtolower((string) $kind) . " {$when}: {$reason}", ['ordem' => $id], $source);
            $this->alerts->reconcile();

            return $id;
        });
    }

    public function start(int $orderId, ?int $actorId, string $source = 'MANUAL'): void
    {
        $this->db->transaction(function () use ($orderId, $actorId, $source): void {
            [$order, $equipment] = $this->lockOrder($orderId);
            if ($order['state'] !== 'ABERTA') {
                throw new RuleViolation('Somente ordens abertas podem ser iniciadas (esta está ' . strtolower((string) $order['state']) . ').');
            }
            if ($equipment['archived_at'] !== null) {
                throw new RuleViolation('Equipamento arquivado não recebe intervenção.');
            }
            // Uma intervenção por vez: a conclusão declara a situação real do equipamento, e duas intervenções
            // simultâneas tornariam esse resultado ambíguo (a primeira a terminar liberaria o equipamento).
            foreach ($this->orders->openFor((int) $equipment['id']) as $other) {
                if ($other['state'] === 'EM_EXECUCAO') {
                    throw self::alreadyRunning((int) $other['id']);
                }
            }
            try {
                $this->orders->markStarted($orderId, Time::toDb($this->clock->now()));
            } catch (ConstraintViolation $e) {
                // Barreira do banco (UX_MAINT_ONE_RUNNING) para qualquer caminho que escape da verificação acima.
                throw $e->constraint === 'UX_MAINT_ONE_RUNNING' ? self::alreadyRunning(null) : $e;
            }
            $this->equipmentService->applyStatus($equipment, EquipmentStatus::Manutencao, "intervenção da ordem #{$orderId} iniciada", $actorId, $source);
            $this->history->record('EQUIPAMENTO', (int) $order['equipment_id'], 'MANUTENCAO_INICIADA', $actorId, "Ordem #{$orderId} em execução.", ['ordem' => $orderId], $source);
            $this->alerts->reconcile();
        });
    }

    /** @param array<string, mixed> $data resolution + result_status */
    public function complete(int $orderId, array $data, ?int $actorId, string $source = 'MANUAL'): ?int
    {
        $input = new Input($data);
        $resolution = $input->text('resolution', 500);
        $result = $input->choice('result_status', ['OPERACIONAL', 'FALHA', 'DESATIVADO']);
        $input->assertValid();

        return $this->db->transaction(function () use ($orderId, $resolution, $result, $actorId, $source): ?int {
            [$order, $equipment] = $this->lockOrder($orderId);
            if ($order['state'] !== 'EM_EXECUCAO') {
                throw new RuleViolation('Inicie a intervenção antes de concluí-la (estado atual: ' . strtolower((string) $order['state']) . ').');
            }
            $equipmentId = (int) $order['equipment_id'];
            $now = $this->clock->now();
            $this->orders->markClosed($orderId, 'CONCLUIDA', Time::toDb($now), $actorId, (string) $resolution, $result);
            $this->equipmentService->applyStatus($equipment, EquipmentStatus::from((string) $result), "ordem #{$orderId} concluída", $actorId, $source);
            $this->history->record('EQUIPAMENTO', $equipmentId, 'MANUTENCAO_CONCLUIDA', $actorId, "Ordem #{$orderId} concluída: {$resolution}", ['ordem' => $orderId, 'resultado' => $result], $source);

            $next = null;
            $interval = (int) ($equipment['maint_interval_days'] ?? 0);
            if ($order['kind'] === 'PREVENTIVA' && $interval > 0 && $result !== 'DESATIVADO' && !$this->orders->hasOpenOfKind($equipmentId, 'PREVENTIVA')) {
                $due = (new \DateTimeImmutable(Time::localDate($now)))->modify("+{$interval} days")->format('Y-m-d');
                $next = $this->orders->insert([
                    'equipment_id' => $equipmentId, 'kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'due_date' => $due,
                    'reason' => "Preventiva periódica (a cada {$interval} dias).", 'source' => 'SISTEMA', 'opened_at' => Time::toDb($now), 'opened_by' => null,
                ]);
                $this->history->record('EQUIPAMENTO', $equipmentId, 'MANUTENCAO_ABERTA', null, "Ordem #{$next} preventiva programada para {$due} (periodicidade).", ['ordem' => $next], 'SISTEMA');
            }
            $this->alerts->reconcile();

            return $next;
        });
    }

    public function cancel(int $orderId, string $reason, int $actorId): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new \Fiscaliza\Domain\ValidationFailed(['reason' => 'Informe o motivo do cancelamento.']);
        }
        $this->db->transaction(function () use ($orderId, $reason, $actorId): void {
            [$order] = $this->lockOrder($orderId);
            if ($order['state'] !== 'ABERTA') {
                throw new RuleViolation('Somente ordens abertas podem ser canceladas; uma intervenção iniciada deve ser concluída com o resultado real.');
            }
            $this->orders->markClosed($orderId, 'CANCELADA', Time::toDb($this->clock->now()), $actorId, $reason, null);
            $this->history->record('EQUIPAMENTO', (int) $order['equipment_id'], 'MANUTENCAO_CANCELADA', $actorId, "Ordem #{$orderId} cancelada: {$reason}", ['ordem' => $orderId]);
            $this->alerts->reconcile();
        });
    }

    /**
     * Bloqueia o equipamento e depois a ordem — sempre nesta ordem, também no arquivamento e na abertura —, de modo
     * que operações concorrentes sobre o mesmo equipamento se enfileirem em vez de travarem umas às outras.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} ordem e equipamento bloqueados
     */
    private function lockOrder(int $orderId): array
    {
        $equipmentId = $this->orders->equipmentOf($orderId) ?? throw new NotFound('Ordem não encontrada.');
        $equipment = $this->equipment->lock($equipmentId) ?? throw new NotFound('Equipamento não encontrado.');
        $order = $this->orders->lock($orderId) ?? throw new NotFound('Ordem não encontrada.');

        return [$order, $equipment];
    }

    private static function alreadyRunning(?int $orderId): RuleViolation
    {
        $which = $orderId === null ? 'Outra ordem' : "A ordem #{$orderId}";

        return new RuleViolation("{$which} já está em execução neste equipamento. Conclua-a, informando o resultado, antes de iniciar outra intervenção.");
    }
}
