<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;

/**
 * Ciclo de vida das ordens de manutenção: ABERTA → EM_EXECUCAO → CONCLUIDA, ou ABERTA → CANCELADA.
 *
 * Regras: agendar não tira o equipamento de operação; iniciar a intervenção o coloca "em manutenção";
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
            $order = $this->orders->lock($orderId) ?? throw new NotFound('Ordem não encontrada.');
            if ($order['state'] !== 'ABERTA') {
                throw new RuleViolation('Somente ordens abertas podem ser iniciadas (esta está ' . strtolower((string) $order['state']) . ').');
            }
            $equipment = $this->equipment->lock((int) $order['equipment_id']) ?? throw new NotFound('Equipamento não encontrado.');
            $this->orders->markStarted($orderId, Time::toDb($this->clock->now()));
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
            $order = $this->orders->lock($orderId) ?? throw new NotFound('Ordem não encontrada.');
            if ($order['state'] !== 'EM_EXECUCAO') {
                throw new RuleViolation('Inicie a intervenção antes de concluí-la (estado atual: ' . strtolower((string) $order['state']) . ').');
            }
            $equipmentId = (int) $order['equipment_id'];
            $equipment = $this->equipment->lock($equipmentId) ?? throw new NotFound('Equipamento não encontrado.');
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
            $order = $this->orders->lock($orderId) ?? throw new NotFound('Ordem não encontrada.');
            if ($order['state'] !== 'ABERTA') {
                throw new RuleViolation('Somente ordens abertas podem ser canceladas; uma intervenção iniciada deve ser concluída com o resultado real.');
            }
            $this->orders->markClosed($orderId, 'CANCELADA', Time::toDb($this->clock->now()), $actorId, $reason, null);
            $this->history->record('EQUIPAMENTO', (int) $order['equipment_id'], 'MANUTENCAO_CANCELADA', $actorId, "Ordem #{$orderId} cancelada: {$reason}", ['ordem' => $orderId]);
            $this->alerts->reconcile();
        });
    }
}
