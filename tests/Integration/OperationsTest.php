<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Eventos (duplicados, atrasados, estado administrativo), alertas e ciclo de manutenção. */
final class OperationsTest extends FirebirdTestCase
{
    private ?int $uid = null;

    public function testDuplicateEventIsIgnoredAndCounted(): void
    {
        $id = $this->createEquipment();
        $ingest = $this->services->ingestor();
        $now = $this->clock->now();

        self::assertSame('APLICADO', $ingest->equipmentStatus('dup-1', $id, EquipmentStatus::Falha, $now, 'teste'));
        self::assertSame('DUPLICADO', $ingest->equipmentStatus('dup-1', $id, EquipmentStatus::Falha, $now, 'teste'));
        self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM RESOURCE_EVENT WHERE EVENT_UID = 'dup-1'"));
        self::assertSame(1, $this->services->metaRepository()->number('duplicates_ignored'));
    }

    public function testLateEventGoesToHistoryWithoutRegressingCurrentState(): void
    {
        $id = $this->createEquipment();
        $ingest = $this->services->ingestor();
        $ingest->equipmentStatus('e-1', $id, EquipmentStatus::Falha, $this->clock->now(), 'falhou');
        $outcome = $ingest->equipmentStatus('e-0', $id, EquipmentStatus::Operacional, $this->clock->now()->modify('-20 minutes'), 'leitura antiga');

        self::assertSame('HISTORICO', $outcome);
        self::assertSame('FALHA', $this->equipmentStatus($id));
        self::assertSame(1, $this->services->metaRepository()->number('late_events'));
        $late = array_filter($this->services->eventRepository()->forResource('EQUIPAMENTO', $id), static fn($e) => ($e['payload']['motivo'] ?? '') === 'leitura antiga');
        $event = array_values($late)[0];
        self::assertSame('HISTORICO', $event['outcome']);
        self::assertStringContainsString('atrasado', $event['payload']['ignorado_porque']);
    }

    public function testAdministrativeStatusPrevailsOverTelemetry(): void
    {
        $id = $this->createEquipment();
        $this->services->equipment()->changeStatus($id, 'MANUTENCAO', 'técnico no local', $this->userId());
        $this->clock->advance('+1 minute');

        self::assertSame('HISTORICO', $this->services->ingestor()->equipmentStatus('t-1', $id, EquipmentStatus::Operacional, $this->clock->now(), 'sensor respondeu'));
        self::assertSame('MANUTENCAO', $this->equipmentStatus($id));
    }

    public function testOlderPositionDoesNotReplaceNewerOne(): void
    {
        $agent = $this->createAgent();
        $ingest = $this->services->ingestor();
        $ingest->position('p-2', 'AGENTE', $agent, '-22.200000', '-49.940000', $this->clock->now(), true);
        $outcome = $ingest->position('p-1', 'AGENTE', $agent, '-22.300000', '-49.900000', $this->clock->now()->modify('-1 minute'), true);

        self::assertSame('HISTORICO', $outcome);
        self::assertSame(-22.2, $this->services->agentRepository()->find($agent)['pos_lat']);
    }

    public function testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve(): void
    {
        $id = $this->createEquipment();
        $ingest = $this->services->ingestor();
        $alerts = $this->services->alerts();
        $ingest->equipmentStatus('a-1', $id, EquipmentStatus::Falha, $this->clock->now(), 'queda');
        $alerts->reconcile();
        $alerts->reconcile();
        $open = $this->services->alertRepository()->unresolved();

        self::assertCount(1, $open);
        self::assertSame('FALHA', $open[0]['reason']);

        $alerts->acknowledge((int) $open[0]['id'], $this->userId(), 'equipe acionada');
        self::assertSame('RECONHECIDO', $this->services->alertRepository()->unresolved()[0]['state'], 'reconhecer não resolve a causa');

        $this->clock->advance('+5 minutes');
        $ingest->equipmentStatus('a-2', $id, EquipmentStatus::Operacional, $this->clock->now(), 'voltou');
        $alerts->reconcile();
        self::assertSame([], $this->services->alertRepository()->unresolved());
        self::assertSame('RESOLVIDO', $this->services->alertRepository()->find((int) $open[0]['id'])['state']);

        $this->expectException(RuleViolation::class);
        $alerts->acknowledge((int) $open[0]['id'], $this->userId(), null);
    }

    public function testConcurrentReconcileNeverDuplicatesAnOpenAlert(): void
    {
        $id = $this->createEquipment(['status' => 'FURTADO']);
        $other = $this->newServices();
        $this->services->database()->transaction(fn() => $this->services->alerts()->reconcile());
        $other->database()->transaction(fn() => $other->alerts()->reconcile());

        self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM ALERT WHERE RESOURCE_ID = ? AND STATE <> 'RESOLVIDO'", [$id]));
    }

    public function testMaintenanceLifecycle(): void
    {
        $id = $this->createEquipment(['maint_interval_days' => '90']);
        $maintenance = $this->services->maintenance();

        try {
            $maintenance->open($id, ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'reason' => 'revisão'], $this->userId());
            self::fail('programada exige data');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('due_date', $e->errors);
        }
        $order = $maintenance->open($id, ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'due_date' => '2026-10-20', 'reason' => 'revisão'], $this->userId());
        self::assertSame('OPERACIONAL', $this->equipmentStatus($id), 'agendar não tira de operação');

        $maintenance->start($order, $this->userId());
        self::assertSame('MANUTENCAO', $this->equipmentStatus($id));

        try {
            $maintenance->complete($order, ['resolution' => 'feito'], $this->userId());
            self::fail('concluir exige declarar a situação resultante');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('result_status', $e->errors);
        }
        $next = $maintenance->complete($order, ['resolution' => 'limpeza e testes', 'result_status' => 'OPERACIONAL'], $this->userId());

        self::assertSame('OPERACIONAL', $this->equipmentStatus($id));
        self::assertNotNull($next);
        $scheduled = $this->services->maintenanceRepository()->find($next);
        self::assertSame('2027-01-16', $scheduled['due_date'], 'próxima preventiva = hoje (local) + 90 dias');
        self::assertSame('SISTEMA', $scheduled['source']);

        $maintenance->start($next, $this->userId());
        $this->expectException(RuleViolation::class);
        $maintenance->cancel($next, 'desisti', $this->userId());
    }

    public function testCompletionCanReportThatEquipmentStillFails(): void
    {
        $id = $this->createEquipment();
        $maintenance = $this->services->maintenance();
        $order = $maintenance->open($id, ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'reason' => 'sem vídeo'], $this->userId());
        $maintenance->start($order, $this->userId());
        $maintenance->complete($order, ['resolution' => 'peça em falta', 'result_status' => 'FALHA'], $this->userId());

        self::assertSame('FALHA', $this->equipmentStatus($id));
        self::assertSame('CONCLUIDA', $this->services->maintenanceRepository()->find($order)['state']);
    }

    public function testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock(): void
    {
        $console = new \Fiscaliza\Cli\Console($this->services);
        $lock = fopen(self::ROOT . '/var/simulador.lock', 'c');
        self::assertNotFalse($lock);
        self::assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        try {
            ob_start();
            $console->run(['sim:tick']);
            self::assertStringContainsString('Ciclo ignorado', (string) ob_get_clean());
            self::assertSame(0, $this->services->metaRepository()->number('sim_tick'), 'nenhum ciclo concorrente foi executado');
        } finally {
            fclose($lock);
        }
        ob_start();
        $console->run(['sim:tick']);
        ob_end_clean();
        self::assertSame(1, $this->services->metaRepository()->number('sim_tick'));
    }

    private function userId(): int
    {
        return $this->uid ??= $this->services->auth()->createUser('operadora', 'Operadora de Teste', 'OPERADOR', 'senha-de-teste-longa')['id'];
    }
}
