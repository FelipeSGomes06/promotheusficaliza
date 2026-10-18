<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use DateTimeImmutable;
use Fiscaliza\Domain\AlertRules;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\Indicators;
use Fiscaliza\Domain\MaintenanceUrgency;
use Fiscaliza\Domain\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DomainRulesTest extends TestCase
{
    public function testAvailabilityExcludesNotInstalledAndDeactivatedFromDenominator(): void
    {
        $rows = [
            ['status' => 'OPERACIONAL', 'freshness' => 'ATUAL'],
            ['status' => 'OPERACIONAL', 'freshness' => 'ATRASADA'],
            ['status' => 'FALHA', 'freshness' => 'ATUAL'],
            ['status' => 'FURTADO', 'freshness' => 'NAO_SE_APLICA'],
            ['status' => 'NAO_INSTALADO', 'freshness' => 'NAO_SE_APLICA'],
            ['status' => 'DESATIVADO', 'freshness' => 'NAO_SE_APLICA'],
        ];
        $k = Indicators::compute($rows, [])['equipment'];

        self::assertSame(6, $k['total']);
        self::assertSame(4, $k['in_service']);
        self::assertSame(2, $k['unavailable']);
        self::assertSame(0.5, $k['availability']);
        self::assertSame(0.25, $k['availability_confirmed'], 'operacional sem sinal atual não confirma disponibilidade');
        self::assertSame(1, $k['operational_unconfirmed']);
    }

    public function testZeroDenominatorIsNotApplicableInsteadOfZeroOrHundredPercent(): void
    {
        $k = Indicators::compute([['status' => 'NAO_INSTALADO', 'freshness' => 'NAO_SE_APLICA']], [])['equipment'];

        self::assertSame(0, $k['in_service']);
        self::assertNull($k['availability']);
        self::assertNull($k['availability_confirmed']);
        self::assertNull(Indicators::compute([], [])['equipment']['availability']);
    }

    public function testAgentsAreCountedSeparately(): void
    {
        $agents = [
            ['status' => 'EM_ATIVIDADE', 'freshness' => 'ATUAL'],
            ['status' => 'EM_ATIVIDADE', 'freshness' => 'SEM_INFORMACAO'],
            ['status' => 'EM_PAUSA', 'freshness' => 'ATUAL'],
            ['status' => 'FORA_DE_SERVICO', 'freshness' => 'NAO_SE_APLICA'],
        ];
        $k = Indicators::compute([], $agents);

        self::assertSame(0, $k['equipment']['total']);
        self::assertSame(2, $k['agents']['active']);
        self::assertSame(1, $k['agents']['position_not_current']);
    }

    /** @return iterable<string, array{0: EquipmentStatus, 1: EquipmentStatus, 2: bool}> */
    public static function transitions(): iterable
    {
        yield 'instalação' => [EquipmentStatus::NaoInstalado, EquipmentStatus::Operacional, true];
        yield 'não instalado não falha' => [EquipmentStatus::NaoInstalado, EquipmentStatus::Falha, false];
        yield 'furto vira reposição' => [EquipmentStatus::Furtado, EquipmentStatus::NaoInstalado, true];
        yield 'furtado não volta a operar direto' => [EquipmentStatus::Furtado, EquipmentStatus::Operacional, false];
        yield 'manutenção conclui em falha' => [EquipmentStatus::Manutencao, EquipmentStatus::Falha, true];
        yield 'desativado não entra em manutenção' => [EquipmentStatus::Desativado, EquipmentStatus::Manutencao, false];
    }

    #[DataProvider('transitions')]
    public function testStatusTransitions(EquipmentStatus $from, EquipmentStatus $to, bool $allowed): void
    {
        self::assertSame($allowed, $from->canTransitionTo($to));
    }

    public function testAdministrativeStatusesAreNotOverriddenByTelemetry(): void
    {
        self::assertTrue(EquipmentStatus::Manutencao->isAdministrative());
        self::assertTrue(EquipmentStatus::Furtado->isAdministrative());
        self::assertFalse(EquipmentStatus::Falha->isAdministrative());
        self::assertFalse(EquipmentStatus::Operacional->isAdministrative());
    }

    /** @return iterable<string, array{0: string, 1: ?string, 2: MaintenanceUrgency}> */
    public static function urgencies(): iterable
    {
        yield 'imediata sem data' => ['IMEDIATA', null, MaintenanceUrgency::Imediata];
        yield 'venceu ontem' => ['PROGRAMADA', '2026-10-17', MaintenanceUrgency::Vencida];
        yield 'vence hoje é próxima' => ['PROGRAMADA', '2026-10-18', MaintenanceUrgency::Proxima];
        yield 'limite da janela de 7 dias' => ['PROGRAMADA', '2026-10-25', MaintenanceUrgency::Proxima];
        yield 'um dia após a janela' => ['PROGRAMADA', '2026-10-26', MaintenanceUrgency::Futura];
    }

    #[DataProvider('urgencies')]
    public function testMaintenanceUrgencyUsesLocalDates(string $priority, ?string $due, MaintenanceUrgency $expected): void
    {
        self::assertSame($expected, MaintenanceUrgency::classify($priority, $due, '2026-10-18'));
    }

    public function testLocalDateCrossesMidnightInSaoPaulo(): void
    {
        // 02:30 UTC de 19/10 ainda é 18/10 em Marília (UTC−3).
        self::assertSame('2026-10-18', Time::localDate(new DateTimeImmutable('2026-10-19 02:30:00', Time::utc())));
    }

    public function testFreshnessThresholds(): void
    {
        $now = new DateTimeImmutable('2026-10-18 12:00:00', Time::utc());
        self::assertSame(Freshness::Atual, Freshness::of($now->modify('-600 seconds'), $now));
        self::assertSame(Freshness::Atrasada, Freshness::of($now->modify('-601 seconds'), $now));
        self::assertSame(Freshness::SemInformacao, Freshness::of($now->modify('-2 hours'), $now));
        self::assertSame(Freshness::SemInformacao, Freshness::of(null, $now));
        self::assertSame(Freshness::NaoSeAplica, Freshness::of(null, $now, false));
    }

    public function testAlertConditionsFromCurrentState(): void
    {
        $now = new DateTimeImmutable('2026-10-18 12:00:00', Time::utc());
        $equipment = [
            ['id' => 1, 'code' => 'RAD-0001', 'status' => 'FURTADO', 'last_seen_at' => null],
            ['id' => 2, 'code' => 'CAM-0001', 'status' => 'OPERACIONAL', 'last_seen_at' => '2026-10-18 09:00:00'],
            ['id' => 3, 'code' => 'CAM-0002', 'status' => 'OPERACIONAL', 'last_seen_at' => '2026-10-18 11:59:00'],
            ['id' => 4, 'code' => 'TOT-0001', 'status' => 'DESATIVADO', 'last_seen_at' => null],
        ];
        $orders = [['equipment_id' => 3, 'priority' => 'PROGRAMADA', 'due_date' => '2026-10-10']];
        $agents = [['id' => 9, 'call_sign' => 'Hermes 06', 'status' => 'EM_ATIVIDADE', 'pos_observed_at' => '2026-10-18 10:00:00']];

        $keys = array_keys(AlertRules::conditions($equipment, $agents, $orders, $now));
        sort($keys);

        self::assertSame(['AGENTE:9:SEM_POSICAO', 'EQUIPAMENTO:1:FURTO', 'EQUIPAMENTO:2:SEM_COMUNICACAO', 'EQUIPAMENTO:3:MANUTENCAO_VENCIDA'], $keys);
    }
}
