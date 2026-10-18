<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Fiscaliza\Tests\Support\Parallel;

/**
 * Regras entre manutenção, situação e arquivamento (achados OP-01 e OP-02 da auditoria de 18/10/2026):
 * uma intervenção em execução por equipamento, e nenhum arquivamento deixa uma ordem sem encerramento normal.
 * Os casos concorrentes usam processos e conexões separados (Parallel).
 */
final class MaintenanceInvariantsTest extends FirebirdTestCase
{
    private ?int $uid = null;

    public function testSecondInterventionWaitsForTheFirstAndCompletionReleasesOnlyThen(): void
    {
        $id = $this->createEquipment();
        $maintenance = $this->services->maintenance();
        $a = $this->openOrder($id);
        $b = $this->openOrder($id);
        $maintenance->start($a, $this->userId());

        try {
            $maintenance->start($b, $this->userId());
            self::fail('a segunda intervenção não pode começar com outra em execução');
        } catch (RuleViolation $e) {
            self::assertStringContainsString("#{$a}", $e->getMessage());
        }
        self::assertSame('ABERTA', $this->state($b));

        $maintenance->complete($a, ['resolution' => 'Primeira tarefa concluída.', 'result_status' => 'OPERACIONAL'], $this->userId());
        self::assertSame('OPERACIONAL', $this->equipmentStatus($id), 'sem intervenção em execução, o resultado declarado vale');

        $maintenance->start($b, $this->userId());
        self::assertSame('MANUTENCAO', $this->equipmentStatus($id));
    }

    public function testDatabaseRejectsTwoRunningInterventionsEvenOutsideTheService(): void
    {
        $id = $this->createEquipment();
        $db = $this->services->database();
        $at = '2026-10-18 15:00:00';
        $db->execute("UPDATE MAINTENANCE_ORDER SET STATE = 'EM_EXECUCAO', STARTED_AT = ? WHERE ID = ?", [$at, $this->openOrder($id)]);

        $this->expectException(ConstraintViolation::class);
        $db->execute("UPDATE MAINTENANCE_ORDER SET STATE = 'EM_EXECUCAO', STARTED_AT = ? WHERE ID = ?", [$at, $this->openOrder($id)]);
    }

    public function testManualStatusChangeCannotReleaseARunningIntervention(): void
    {
        $id = $this->createEquipment();
        $order = $this->openOrder($id);
        $this->services->maintenance()->start($order, $this->userId());

        try {
            $this->services->equipment()->changeStatus($id, 'OPERACIONAL', 'liberado sem concluir', $this->userId());
            self::fail('a situação é definida pela conclusão da intervenção');
        } catch (RuleViolation $e) {
            self::assertStringContainsString("#{$order}", $e->getMessage());
        }
        self::assertSame('MANUTENCAO', $this->equipmentStatus($id));
    }

    public function testArchiveIsRefusedWhileAnInterventionRunsAndTheOrderCanStillBeCompleted(): void
    {
        $id = $this->createEquipment();
        $order = $this->openOrder($id);
        $this->services->maintenance()->start($order, $this->userId());

        try {
            $this->services->equipment()->archive($id, 'Arquivamento durante intervenção.', $this->userId(), true);
            self::fail('arquivar com intervenção em execução deixaria a ordem sem saída');
        } catch (RuleViolation $e) {
            self::assertStringContainsString('Conclua a intervenção', $e->getMessage());
        }
        self::assertNull($this->services->equipmentRepository()->find($id)['archived_at']);

        $this->services->maintenance()->complete($order, ['resolution' => 'Serviço encerrado.', 'result_status' => 'DESATIVADO'], $this->userId());
        $this->services->equipment()->archive($id, 'Retirado depois da conclusão.', $this->userId());
        self::assertNotNull($this->services->equipmentRepository()->find($id)['archived_at']);
    }

    public function testScheduledOrdersAreCancelledOnlyWithExplicitConfirmationAndKeepAuthorship(): void
    {
        $id = $this->createEquipment();
        $order = $this->services->maintenance()->open($id, ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'due_date' => '2026-11-03', 'reason' => 'revisão'], $this->userId());

        try {
            $this->services->equipment()->archive($id, 'substituído', $this->userId());
            self::fail('ordens agendadas não são encerradas em silêncio');
        } catch (RuleViolation $e) {
            self::assertStringContainsString("#{$order}", $e->getMessage());
        }
        self::assertSame('ABERTA', $this->state($order));

        self::assertSame([$order], $this->services->equipment()->archive($id, 'substituído', $this->userId(), true));
        $closed = $this->services->maintenanceRepository()->find($order);
        self::assertSame('CANCELADA', $closed['state']);
        self::assertStringContainsString('substituído', $closed['resolution']);
        self::assertSame('Operadora de Teste', $closed['closed_by_name']);
        $types = array_column($this->services->eventRepository()->forResource('EQUIPAMENTO', $id), 'event_type');
        self::assertContains('MANUTENCAO_CANCELADA', $types);
        self::assertSame('ARQUIVAMENTO', $types[0]);
    }

    public function testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne(): void
    {
        $id = $this->createEquipment();
        $orders = [$this->openOrder($id), $this->openOrder($id), $this->openOrder($id)];
        $results = Parallel::run(array_map(fn(int $o) => ['start', (string) $o, (string) $this->userId()], $orders), $this->clock->now());

        self::assertCount(1, array_filter($results, static fn($r) => $r['ok']), json_encode($results, JSON_UNESCAPED_UNICODE) ?: '');
        foreach (array_filter($results, static fn($r) => !$r['ok']) as $refused) {
            self::assertContains($refused['class'], ['RuleViolation', 'ConcurrencyConflict'], $refused['message'] ?? '');
        }
        self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM MAINTENANCE_ORDER WHERE EQUIPMENT_ID = ? AND STATE = 'EM_EXECUCAO'", [$id]));
        self::assertSame('MANUTENCAO', $this->equipmentStatus($id));
    }

    public function testStartRacingArchiveNeverLeavesAnUnfinishableOrder(): void
    {
        foreach (range(1, 3) as $round) {
            $id = $this->createEquipment(['serial_number' => "CORRIDA-{$round}"]);
            $order = $this->openOrder($id);
            $results = Parallel::run([['start', (string) $order, (string) $this->userId()], ['archive', (string) $id, (string) $this->userId(), '1']], $this->clock->now());

            self::assertCount(1, array_filter($results, static fn($r) => $r['ok']), "rodada {$round}: " . (json_encode($results, JSON_UNESCAPED_UNICODE) ?: ''));
            $archived = $this->services->equipmentRepository()->find($id)['archived_at'] !== null;
            self::assertSame($archived ? 'CANCELADA' : 'EM_EXECUCAO', $this->state($order), "rodada {$round}");
        }
    }

    private function openOrder(int $equipmentId): int
    {
        return $this->services->maintenance()->open($equipmentId, ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'reason' => 'Inspeção de teste.'], $this->userId());
    }

    private function state(int $orderId): string
    {
        return (string) $this->services->maintenanceRepository()->find($orderId)['state'];
    }

    private function userId(): int
    {
        return $this->uid ??= $this->services->auth()->createUser('operadora', 'Operadora de Teste', 'OPERADOR', 'senha-de-teste-longa')['id'];
    }
}
