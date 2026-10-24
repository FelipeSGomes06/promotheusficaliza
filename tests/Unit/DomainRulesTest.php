<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use DateTimeImmutable;
use Fiscaliza\Domain\AlertFollowUp;
use Fiscaliza\Domain\AlertRules;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\Indicators;
use Fiscaliza\Domain\MaintenanceUrgency;
use Fiscaliza\Domain\NearestRegion;
use Fiscaliza\Domain\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DomainRulesTest extends TestCase
{
    /** Mesma régua do "perto de …" do Planejar: centro mais próximo; sem bairro cadastrado, nenhum. */
    public function testNearestRegionIsTheClosestCenter(): void
    {
        $regions = [
            ['id' => 1, 'name' => 'Centro', 'center_lat' => '-22.214000', 'center_lng' => '-49.950500'],
            ['id' => 2, 'name' => 'Fragata', 'center_lat' => '-22.226000', 'center_lng' => '-49.930000'],
            ['id' => 3, 'name' => 'Palmital', 'center_lat' => '-22.200000', 'center_lng' => '-49.970000'],
        ];

        self::assertSame('Fragata', NearestRegion::of(-22.2250, -49.9330, $regions)['name'] ?? null);
        self::assertSame('Centro', NearestRegion::of(-22.2139, -49.9458, $regions)['name'] ?? null);
        self::assertSame(['Palmital', 'Centro', 'Fragata'], array_column(NearestRegion::sorted(-22.2010, -49.9690, $regions), 'name'));
        self::assertNull(NearestRegion::of(-22.2139, -49.9458, []));
    }

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

    public function testStartBlockerFollowsTheTransitionTable(): void
    {
        // A ficha e o quadro mostram o impedimento; o serviço recusa: os dois precisam concordar com a tabela.
        foreach (EquipmentStatus::cases() as $status) {
            $startable = $status === EquipmentStatus::Manutencao || $status->canTransitionTo(EquipmentStatus::Manutencao);
            self::assertSame($startable, $status->maintenanceStartBlocker() === null, $status->value);
        }
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

    public function testNextActionFollowsTheAlertFromOpenToFollowUp(): void
    {
        $now = new DateTimeImmutable('2026-10-18 12:00:00', Time::utc());
        $agent = ['status' => 'EM_ATIVIDADE', 'status_changed_at' => '2026-10-18 08:00:00', 'archived_at' => null, 'pos_observed_at' => '2026-10-18 10:00:00'];
        $next = static fn(?array $alert, ?array $last = null, array $a = []) => AlertFollowUp::nextAction($a + $agent, $alert, $last, $now);

        self::assertSame(['urgent', 'Registrar ciência e conferir a comunicação com a equipe'], array_values(array_slice($next(['state' => 'ABERTO', 'next_check_at' => null]), 0, 2)));
        self::assertStringContainsString('há 2 h (18/10 às 07:00)', $next(['state' => 'ABERTO', 'next_check_at' => null])['detail']);
        self::assertSame('Conferir a comunicação com a equipe e registrar a providência', $next(['state' => 'RECONHECIDO', 'next_check_at' => null])['what']);
        self::assertSame('Aguardar a próxima conferência, 18/10 às 09:30', $next(['state' => 'RECONHECIDO', 'next_check_at' => '2026-10-18 12:30:00'], ['action_code' => 'CONTATO_TENTADO'])['what']);
        self::assertSame('urgent', $next(['state' => 'RECONHECIDO', 'next_check_at' => '2026-10-18 11:30:00'], ['action_code' => 'CONTATO_TENTADO'])['tone'], 'conferência vencida');
        self::assertSame('Nada pendente agora', $next(null)['what']);
        self::assertSame('Registrar o retorno quando ele acontecer', $next(null, null, ['status' => 'INDISPONIVEL'])['what']);
        self::assertSame('POSICAO_RECEBIDA', AlertRules::resolutionReason('AGENTE', 'SEM_POSICAO', ['status' => 'EM_PAUSA']));
        self::assertSame('FORA_DE_TURNO', AlertRules::resolutionReason('AGENTE', 'SEM_POSICAO', ['status' => 'INDISPONIVEL']));
        self::assertSame('ARQUIVADO', AlertRules::resolutionReason('AGENTE', 'SEM_POSICAO', null));
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
        // Em pausa continua em turno (mesma regra da ficha e da central); fora de serviço, a posição não se aplica.
        $agents = [['id' => 9, 'call_sign' => 'Hermes 06', 'status' => 'EM_ATIVIDADE', 'pos_observed_at' => '2026-10-18 10:00:00'],
            ['id' => 10, 'call_sign' => 'Hermes 07', 'status' => 'EM_PAUSA', 'pos_observed_at' => '2026-10-18 10:00:00'],
            ['id' => 11, 'call_sign' => 'Hermes 08', 'status' => 'FORA_DE_SERVICO', 'pos_observed_at' => null]];

        $keys = array_keys(AlertRules::conditions($equipment, $agents, $orders, $now));
        sort($keys);

        self::assertSame(['AGENTE:10:SEM_POSICAO', 'AGENTE:9:SEM_POSICAO', 'EQUIPAMENTO:1:FURTO', 'EQUIPAMENTO:2:SEM_COMUNICACAO', 'EQUIPAMENTO:3:MANUTENCAO_VENCIDA'], $keys);
    }
}
