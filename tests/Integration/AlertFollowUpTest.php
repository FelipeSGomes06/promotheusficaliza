<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Domain\AlertFollowUp;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Acompanhamento dos alertas de agente: turno, ciência, responsável (conta pelo ID), providências e motivo de encerramento. */
final class AlertFollowUpTest extends FirebirdTestCase
{
    private function account(string $name = 'Operadora'): int
    {
        return (int) $this->services->auth()->createUser('conta' . random_int(1, 999999), $name, 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
    }

    /** Agente em atividade sem posição: o alerta SEM_POSICAO abre. @return array{0: int, 1: int} agente, alerta */
    private function agentWithAlert(): array
    {
        $agent = $this->createAgent();
        $this->services->database()->transaction(fn() => $this->services->alerts()->reconcile());
        $pending = $this->services->alertRepository()->unresolvedFor('AGENTE', $agent);
        self::assertCount(1, $pending);

        return [$agent, (int) $pending[0]['id']];
    }

    private function version(int $alert): string
    {
        return (string) $this->services->alertRepository()->find($alert)['followup_no'];
    }

    private function localIn(string $interval): string
    {
        return $this->clock->now()->modify($interval)->setTimezone(Time::local())->format('Y-m-d\TH:i');
    }

    public function testPauseKeepsTheSameAlertInsteadOfResolvingAndReopening(): void
    {
        [$agent, $alert] = $this->agentWithAlert();
        $agents = $this->services->agents();

        $agents->changeStatus($agent, 'EM_PAUSA', null, 'SIMULADOR', 'pausa simulada');
        self::assertSame([$alert], array_map(static fn($a) => (int) $a['id'], $this->services->alertRepository()->unresolvedFor('AGENTE', $agent)), 'pausa continua em turno');
        $agents->changeStatus($agent, 'EM_ATIVIDADE', null, 'SIMULADOR', 'retorno simulado');

        self::assertSame([$alert], array_map(static fn($a) => (int) $a['id'], $this->services->alertRepository()->unresolvedFor('AGENTE', $agent)));
        self::assertSame(0, $this->services->alertRepository()->countResolvedFor('AGENTE', $agent), 'sem falsa recuperação no histórico');
        self::assertStringContainsString('em turno sem posição recente', (string) $this->services->alertRepository()->find($alert)['message']);
    }

    public function testAcknowledgeKeepsTheAlertPendingAndDoesNotAssign(): void
    {
        [, $alert] = $this->agentWithAlert();
        $user = $this->account();

        $this->services->alerts()->acknowledge($alert, $user, 'equipe chamada no rádio');
        $row = $this->services->alertRepository()->find($alert);

        self::assertSame(['RECONHECIDO', $user, 'equipe chamada no rádio', null], [$row['state'], (int) $row['ack_by'], $row['ack_note'], $row['assignee_id']]);
        self::assertNotNull($row['ack_at']);
    }

    public function testFollowUpsComeAfterAcknowledgeAndAreAppendOnly(): void
    {
        [$agent, $alert] = $this->agentWithAlert();
        $user = $this->account();
        $alerts = $this->services->alerts();
        try {
            $alerts->recordFollowUp($alert, ['action' => 'CONTATO_TENTADO', 'version' => $this->version($alert)], $user);
            self::fail('providência antes da ciência');
        } catch (RuleViolation) {
        }
        $alerts->acknowledge($alert, $user, 'nota original');

        $alerts->recordFollowUp($alert, ['action' => 'CONTATO_TENTADO', 'next_check' => $this->localIn('+30 minutes'), 'version' => $this->version($alert)], $user);
        self::assertSame(Time::toDb($this->clock->now()->modify('+30 minutes')), substr((string) $this->services->alertRepository()->find($alert)['next_check_at'], 0, 16) . ':00');
        $alerts->recordFollowUp($alert, ['action' => 'CONTATO_CONFIRMADO', 'note' => 'equipe sem bateria no rádio', 'version' => $this->version($alert)], $user);

        $row = $this->services->alertRepository()->find($alert);
        $log = $this->services->alertRepository()->followUps([$alert])[$alert];
        self::assertSame(['RECONHECIDO', 'nota original', null], [$row['state'], $row['ack_note'], $row['next_check_at']], 'a ciência é preservada; sem nova conferência, fica não definida');
        self::assertSame(['CONTATO_TENTADO', 'CONTATO_CONFIRMADO'], array_column($log, 'action_code'));
        self::assertSame('Aguardar posição recente', AlertFollowUp::nextAction($this->services->agentRepository()->find($agent), $row, AlertFollowUp::lastAction($log), $this->clock->now())['what'], 'contato confirmado não encerra');
    }

    public function testFollowUpValidationKeepsNothingAndExplainsEachField(): void
    {
        [, $alert] = $this->agentWithAlert();
        $user = $this->account();
        $this->services->alerts()->acknowledge($alert, $user, null);
        try {
            $this->services->alerts()->recordFollowUp($alert, ['action' => 'OUTRA', 'note' => '', 'next_check' => $this->localIn('-5 minutes'), 'version' => $this->version($alert)], $user);
            self::fail('dados inválidos');
        } catch (ValidationFailed $e) {
            self::assertSame(['note', 'next_check'], array_keys($e->errors));
        }
        try {
            $this->services->alerts()->recordFollowUp($alert, ['action' => 'CONTATO_TENTADO', 'next_check' => $this->localIn('+8 days'), 'version' => $this->version($alert)], $user);
            self::fail('conferência além de 7 dias');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('next_check', $e->errors);
        }
        self::assertSame([], $this->services->alertRepository()->followUps([$alert]));
    }

    public function testNewAccountIsAssignedAndReassignedWithHistory(): void
    {
        [, $alert] = $this->agentWithAlert();
        $actor = $this->account('Coordenação');
        $first = $this->account('Ana Teste');
        $second = $this->account('Bruno Teste');
        $alerts = $this->services->alerts();

        self::assertContains($second, array_map(static fn($u) => (int) $u['id'], $this->services->userRepository()->assignable()), 'conta nova entra na seleção sem mudar código');
        $alerts->assign($alert, ['assignee' => (string) $first, 'version' => $this->version($alert)], $actor);
        $alerts->assign($alert, ['assignee' => (string) $second, 'version' => $this->version($alert)], $actor);

        $log = $this->services->alertRepository()->followUps([$alert])[$alert];
        self::assertSame([[null, $first], [$first, $second]], array_map(static fn($f) => [$f['assignee_from'] === null ? null : (int) $f['assignee_from'], (int) $f['assignee_to']], $log));
        self::assertSame(['Coordenação', 'Coordenação'], array_column($log, 'created_by_name'));
        self::assertSame('Bruno Teste', $this->services->alertRepository()->find($alert)['assignee_name']);
        self::assertSame('ABERTO', $this->services->alertRepository()->find($alert)['state'], 'atribuir não registra ciência');
    }

    public function testDeactivatedAccountKeepsTheLinkButReceivesNoNewAssignment(): void
    {
        [, $alert] = $this->agentWithAlert();
        $admin = (int) $this->services->auth()->createUser('admin' . random_int(1, 99999), 'Admin', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $gone = $this->account('Carla Teste');
        $other = $this->account('Davi Teste');
        $this->services->alerts()->assign($alert, ['assignee' => (string) $gone, 'version' => $this->version($alert)], $admin);
        $this->services->auth()->setActive($gone, false, $admin);

        $row = $this->services->alertRepository()->find($alert);
        self::assertSame(['Carla Teste', false], [$row['assignee_name'], (bool) $row['assignee_active']], 'vínculo histórico preservado e marcado');
        self::assertNotContains($gone, array_map(static fn($u) => (int) $u['id'], $this->services->userRepository()->assignable()));
        $this->services->alerts()->assign($alert, ['assignee' => (string) $other, 'version' => $this->version($alert)], $admin);
        $this->expectException(ValidationFailed::class);
        $this->services->alerts()->assign($alert, ['assignee' => (string) $gone, 'version' => $this->version($alert)], $admin);
    }

    public function testFormReadBeforeAnotherPersonActedIsRefused(): void
    {
        [, $alert] = $this->agentWithAlert();
        $user = $this->account();
        $other = $this->account('Outra pessoa');
        $stale = $this->version($alert);
        $this->services->alerts()->assign($alert, ['assignee' => (string) $other, 'version' => $stale], $other);

        $this->expectException(ConcurrencyConflict::class);
        $this->services->alerts()->assign($alert, ['assignee' => (string) $user, 'version' => $stale], $user);
    }

    public function testResolutionReasonSeparatesNewPositionFromLeavingShift(): void
    {
        $repo = $this->services->alertRepository();
        $ingest = $this->services->ingestor();
        $reconcile = fn() => $this->services->database()->transaction(fn() => $this->services->alerts()->reconcile());

        [$agent, $alert] = $this->agentWithAlert();
        $ingest->position('old', 'AGENTE', $agent, '-22.21', '-49.94', $this->clock->now()->modify('-2 hours'), true);
        $reconcile();
        self::assertSame('ABERTO', $repo->find($alert)['state'], 'posição antiga não encerra');
        $ingest->position('new', 'AGENTE', $agent, '-22.22', '-49.95', $this->clock->now(), true);
        $ingest->position('older', 'AGENTE', $agent, '-22.23', '-49.96', $this->clock->now()->modify('-1 hour'), true);
        $reconcile();
        self::assertSame(['RESOLVIDO', 'POSICAO_RECEBIDA'], [$repo->find($alert)['state'], $repo->find($alert)['resolved_reason']]);
        self::assertSame(-22.22, $this->services->agentRepository()->find($agent)['pos_lat'], 'posição fora de ordem não substitui a mais nova');

        $this->clock->advance('+31 minutes');
        $reconcile();
        $second = (int) $repo->unresolvedFor('AGENTE', $agent)[0]['id'];
        $this->services->agents()->changeStatus($agent, 'FORA_DE_SERVICO', $this->account(), 'MANUAL', 'fim do turno');
        self::assertSame('FORA_DE_TURNO', $repo->find($second)['resolved_reason']);

        [$archived, $third] = $this->agentWithAlert();
        $this->services->agents()->archive($archived, 'teste', $this->account());
        self::assertSame('ARQUIVADO', $repo->find($third)['resolved_reason']);
    }

    public function testOldPendingAlertIsListedEvenWithManyRecentClosedOnes(): void
    {
        [$agent, $pending] = $this->agentWithAlert();
        $repo = $this->services->alertRepository();
        for ($i = 0; $i < 25; $i++) {
            $this->clock->advance('+1 minute');
            $at = Time::toDb($this->clock->now());
            $repo->open('AGENTE', $agent, 'TESTE', 'MEDIA', 'alerta encerrado de teste', $at);
            $repo->resolve($repo->openKeys()["AGENTE:{$agent}:TESTE"], $at, 'CAUSA_ENCERRADA');
        }

        self::assertSame($pending, (int) $repo->forResource('AGENTE', $agent)[0]['id']);
        self::assertCount(21, $repo->forResource('AGENTE', $agent), '20 encerrados + o pendente');
        self::assertSame(25, $repo->countResolvedFor('AGENTE', $agent));
    }

    public function testManualStatusChangeNeedsAReason(): void
    {
        $agent = $this->createAgent();
        $this->expectException(ValidationFailed::class);
        $this->services->agents()->changeStatus($agent, 'EM_PAUSA', $this->account(), 'MANUAL', '  ');
    }
}
