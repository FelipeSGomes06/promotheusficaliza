<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\Filters;
use Fiscaliza\Demo\DemoScenario;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\MaintenanceUrgency;
use Fiscaliza\Domain\Time;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Perfis da carga de demonstração: o compacto (padrão) cobre tudo com contagens verdadeiras; o completo segue disponível. */
final class DemoProfileTest extends FirebirdTestCase
{
    private function seedProfile(string $profile): void
    {
        $config = self::config()->with([
            'DB_USER' => self::config()->require('DB_MIGRATION_USER'),
            'DB_PASSWORD' => self::config()->require('DB_MIGRATION_PASSWORD'),
            'DEMO_PROFILE' => $profile,
        ]);
        $owner = new Services($config, self::ROOT, null, $this->clock);
        $owner->database()->execute('DELETE FROM REGION');
        $owner->database()->execute('DELETE FROM AGENCY');
        $owner->seeder()->run(false);
    }

    public function testCompactProfileCoversEveryCategoryStatusAndMaintenanceExampleWithTrueCounts(): void
    {
        $this->seedProfile(DemoScenario::COMPACT);
        $snap = $this->services->central()->snapshot(Filters::fromQuery([]));
        $equipment = array_values(array_filter($snap['resources'], static fn($r) => $r['kind'] === 'EQUIPAMENTO'));
        $agents = array_values(array_filter($snap['resources'], static fn($r) => $r['kind'] === 'AGENTE'));

        self::assertCount(16, $equipment);
        self::assertCount(7, $agents);
        self::assertSame(16, $snap['kpis']['equipment']['total'], 'indicadores contam o perfil carregado, não o cenário cheio');
        self::assertSame(7, $snap['kpis']['agents']['total']);
        self::assertSame(DemoScenario::COMPACT, $this->services->metaRepository()->text('profile'));

        $count = static fn(array $rows, string $key) => array_count_values(array_column($rows, $key));
        $categories = $count($equipment, 'category') + $count($agents, 'category');
        ksort($categories);
        self::assertSame(['AG_GUARDA' => 2, 'AG_POLICIA' => 2, 'AG_TRANSITO' => 3, 'BALANCA' => 1, 'CAMERA' => 4, 'CANCELA' => 1, 'PATINETE' => 2, 'RADAR' => 4, 'TOTEM' => 1, 'VEICULO' => 3], $categories);
        self::assertEqualsCanonicalizing(array_map(static fn($s) => $s->value, EquipmentStatus::cases()), array_keys($count($equipment, 'status')), 'todas as situações de equipamento');
        self::assertEqualsCanonicalizing(array_map(static fn($s) => $s->value, AgentStatus::cases()), array_keys($count($agents, 'status')), 'todas as situações de agente');

        $freshness = array_keys($count($equipment, 'freshness'));
        foreach (['ATUAL', 'ATRASADA', 'SEM_INFORMACAO'] as $f) {
            self::assertContains($f, $freshness, "sinal {$f}");
        }
        $positions = array_unique(array_map(static fn($r) => $r['position']['kind'], $snap['resources']));
        foreach (['INSTALADO', 'PLANEJADO', 'BASE', 'ULTIMA_POSICAO'] as $kind) {
            self::assertContains($kind, $positions, "posição {$kind} (base × última posição)");
        }
        self::assertContains('Em atividade sem posição recente', array_merge(...array_column($agents, 'attention')));

        // Manutenção: em execução, imediata, vencida, próxima e futura — com datas fixas, em qualquer semente.
        $today = Time::localDate($this->clock->now());
        $orders = $this->services->maintenanceRepository()->open(array_column($equipment, 'id'));
        $urgencies = array_map(static fn($o) => MaintenanceUrgency::classify((string) $o['priority'], $o['due_date'], $today)->value, array_filter($orders, static fn($o) => $o['state'] === 'ABERTA'));
        foreach (['IMEDIATA', 'VENCIDA', 'PROXIMA', 'FUTURA'] as $u) {
            self::assertContains($u, $urgencies, "manutenção {$u}");
        }
        self::assertContains('EM_EXECUCAO', array_column($orders, 'state'));

        // Relações íntegras: toda ordem e todo evento apontam para um equipamento do perfil.
        $ids = array_map('intval', array_column($equipment, 'id'));
        foreach ($orders as $o) {
            self::assertContains((int) $o['equipment_id'], $ids);
        }
        $late = (int) $this->services->database()->value("SELECT COUNT(*) FROM RESOURCE_EVENT WHERE OUTCOME = 'HISTORICO'");
        self::assertGreaterThanOrEqual(1, $late, 'exemplo de evento atrasado que não altera a situação');
        $orphans = (int) $this->services->database()->value("SELECT COUNT(*) FROM RESOURCE_EVENT ev WHERE ev.RESOURCE_KIND = 'EQUIPAMENTO' AND NOT EXISTS (SELECT 1 FROM EQUIPMENT e WHERE e.ID = ev.RESOURCE_ID)");
        self::assertSame(0, $orphans);

        // Multas: 12 casos, sem mudar os recursos da central nem entrar no histórico deles.
        $fines = $this->services->fineQuery()->list(\Fiscaliza\Application\FineFilters::fromQuery(['cadastro' => 'todos']), 1);
        self::assertSame(12, $fines['total']);
        self::assertSame(1, $fines['paid']);
        self::assertSame(0, (int) $this->services->database()->value("SELECT COUNT(*) FROM RESOURCE_EVENT WHERE SUMMARY CONTAINING 'DEMO-MUL'"));
    }

    public function testFullProfileRemainsAvailableForVolumeAndMethodology(): void
    {
        $this->seedProfile(DemoScenario::FULL);
        $snap = $this->services->central()->snapshot(Filters::fromQuery([]));

        self::assertSame(48, $snap['kpis']['equipment']['total']);
        self::assertSame(21, $snap['kpis']['agents']['total']);
        self::assertSame(DemoScenario::FULL, $this->services->metaRepository()->text('profile'));
        self::assertSame(40, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE'), 'cenário de multas do perfil completo');
    }

    public function testCompactSelectionIsASubsetAndUnknownProfilesAreRejected(): void
    {
        $full = array_column(DemoScenario::equipment(DemoScenario::FULL), 'serial');
        foreach (DemoScenario::equipment(DemoScenario::COMPACT) as $item) {
            self::assertContains($item['serial'], $full);
        }
        self::assertSame(DemoScenario::COMPACT, DemoScenario::profile(''));
        self::assertSame(DemoScenario::FULL, DemoScenario::profile(' Completo '));
        $this->expectException(\InvalidArgumentException::class);
        DemoScenario::profile('enorme');
    }
}
