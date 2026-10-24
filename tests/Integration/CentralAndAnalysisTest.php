<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\Filters;
use Fiscaliza\Cli\Console;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\FirebirdTestCase;

final class CentralAndAnalysisTest extends FirebirdTestCase
{
    public function testMapListAndIndicatorsComeFromTheSameFilteredSet(): void
    {
        $this->createEquipment();
        $this->createEquipment(['status' => 'FALHA']);
        $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']);
        $this->createEquipment(['type_code' => 'TOTEM', 'specs' => ['services' => ['INFORMACAO']]]);
        $this->createAgent();

        $all = $this->services->central()->snapshot(new Filters());
        self::assertCount(5, $all['resources']);
        self::assertSame(4, $all['kpis']['equipment']['total']);
        self::assertSame(1, $all['kpis']['agents']['active']);

        $cameras = $this->services->central()->snapshot(Filters::fromQuery(['categoria' => ['CAMERA']]));
        self::assertCount(3, $cameras['resources']);
        self::assertSame(3, $cameras['kpis']['equipment']['total']);
        self::assertSame(0, $cameras['kpis']['agents']['total'], 'filtro de tipo de equipamento exclui agentes');
        self::assertSame(0.5, $cameras['kpis']['equipment']['availability']);
        self::assertSame(['CAMERA'], $cameras['filters']['categoria']);

        $failing = $this->services->central()->snapshot(Filters::fromQuery(['situacao' => ['FALHA']]));
        self::assertSame(['FALHA'], array_column($failing['resources'], 'status'));
        self::assertCount(1, $failing['alerts'], 'alertas seguem o mesmo filtro');
    }

    public function testSearchAndAttentionFilterTheWholeSnapshot(): void
    {
        $this->createEquipment(['street' => 'Avenida Sampaio Vidal', 'address_ref' => 'em frente à praça']);
        $this->createEquipment(['status' => 'FALHA', 'street' => 'Rua Paraná', 'address_ref' => null, 'serial_number' => 'B-1']);
        $this->createAgent();

        $found = $this->services->central()->snapshot(Filters::fromQuery(['q' => 'SAMPAIO vidal']));
        self::assertSame(['Câmera · Avenida Sampaio Vidal, em frente à praça'], array_column($found['resources'], 'headline'), 'tipo e endereço lideram o título');
        self::assertSame(1, $found['kpis']['equipment']['total'], 'indicadores seguem a busca');
        self::assertCount(1, $this->services->central()->snapshot(Filters::fromQuery(['q' => 'parana']))['resources'], 'busca ignora acentos');

        $attention = $this->services->central()->snapshot(Filters::fromQuery(['atencao' => '1']));
        $reasons = array_column($attention['resources'], 'attention', 'headline');
        self::assertSame(['Câmera · Rua Paraná', 'Agente de trânsito · Hermes 99'], array_keys($reasons));
        self::assertSame(2, $attention['needs_attention']);
        self::assertContains('Mau funcionamento', $reasons['Câmera · Rua Paraná']);
        self::assertSame(['Em turno sem posição recente'], $reasons['Agente de trânsito · Hermes 99'], 'falta de posição é dita como tal, não como falha');
        self::assertCount(2, $attention['alerts'], 'alertas seguem o mesmo conjunto');
    }

    public function testNoEquipmentInServiceShowsNotApplicable(): void
    {
        $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']);
        $snap = $this->services->central()->snapshot(new Filters());

        self::assertNull($snap['kpis']['equipment']['availability']);
        self::assertSame('PLANEJADO', $snap['resources'][0]['position']['kind']);
    }

    public function testResourceWithoutCoordinatesIsListedButNotPlaced(): void
    {
        // Cadastro antigo sem coordenadas (hoje o local é obrigatório): continua na lista, fora do mapa.
        $this->services->equipmentRepository()->updateState($this->createEquipment(), ['lat' => null, 'lng' => null]);
        $resource = $this->services->central()->snapshot(new Filters())['resources'][0];

        self::assertNull($resource['position']['lat']);
    }

    /** OP-04: alterar a periodicidade hoje não reescreve a característica de um corte passado. */
    public function testLaterIntervalChangeDoesNotRewriteAPastCutoff(): void
    {
        $older = $this->services->withClock(new Clock($this->clock->now()->modify('-200 days')));
        $id = $this->createEquipment(['maint_interval_days' => '90'], $older);
        $cutoff = $this->clock->now()->modify('-10 days');
        $analysis = $this->services->analysis();
        $analysis->load($this->clock->now());
        self::assertSame(90, $analysis->features($this->row($id), $cutoff)['interval']);

        $actor = $this->services->auth()->createUser('analista', 'Analista', 'OPERADOR', 'senha-de-teste-longa')['id'];
        $this->services->equipment()->update($id, $this->equipmentData(['maint_interval_days' => '7', 'version' => (string) $this->row($id)['version_no']]), $actor);
        $analysis->load($this->clock->now()->modify('+1 second'));

        self::assertSame(90, $analysis->features($this->row($id), $cutoff)['interval'], 'no corte valia a periodicidade de 90 dias');
        self::assertSame(7, $analysis->features($this->row($id), $this->clock->now()->modify('+1 second'))['interval'], 'depois da alteração, vale a nova');
    }

    /** OP-04: ocorrência e disponibilidade são instantes diferentes; o corte só conhece o que já tinha sido recebido. */
    public function testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff(): void
    {
        $older = $this->services->withClock(new Clock($this->clock->now()->modify('-200 days')));
        $id = $this->createEquipment([], $older);
        // Falha ocorrida há 12 dias, mas recebida só agora (concentrador atrasado).
        self::assertSame('APLICADO', $this->services->ingestor()->equipmentStatus('atrasada-1', $id, EquipmentStatus::Falha, $this->clock->now()->modify('-12 days'), 'leitura atrasada'));
        $analysis = $this->services->analysis();
        $analysis->load($this->clock->now()->modify('+1 second'));

        $atCutoff = $analysis->features($this->row($id), $this->clock->now()->modify('-10 days'));
        self::assertSame('OPERACIONAL', $atCutoff['status_at']);
        self::assertSame(0, $atCutoff['failures_30']);
        $afterReceipt = $analysis->features($this->row($id), $this->clock->now()->modify('+1 second'));
        self::assertSame('FALHA', $afterReceipt['status_at']);
        self::assertSame(1, $afterReceipt['failures_30']);
    }

    /** OP-04: um arquivamento posterior não apaga o equipamento dos cortes em que ele existia. */
    public function testEquipmentArchivedLaterStillExistedAtEarlierCutoffs(): void
    {
        $older = $this->services->withClock(new Clock($this->clock->now()->modify('-200 days')));
        $id = $this->createEquipment([], $older);
        $actor = $this->services->auth()->createUser('analista', 'Analista', 'OPERADOR', 'senha-de-teste-longa')['id'];
        $this->services->equipment()->archive($id, 'substituído', $actor);
        $analysis = $this->services->analysis();
        $analysis->load($this->clock->now()->modify('+1 second'));

        self::assertNotNull($analysis->features($this->row($id), $this->clock->now()->modify('-10 days')));
        self::assertNull($analysis->features($this->row($id), $this->clock->now()->modify('+1 second')));
    }

    public function testDemoSeedAnalysisIsReproducibleAndTemporallySeparated(): void
    {
        $owner = (new Services(self::config()->with(['DB_USER' => self::config()->require('DB_MIGRATION_USER'), 'DB_PASSWORD' => self::config()->require('DB_MIGRATION_PASSWORD')]), self::ROOT, null, $this->clock));
        $owner->database()->execute('DELETE FROM REGION');
        $owner->database()->execute('DELETE FROM AGENCY');
        $owner->seeder()->run(false);

        $first = $this->services->analysisRepository()->latestRun();
        $second = $this->services->analysis()->run(null);
        $attention = fn(int $run) => array_column($this->services->analysisRepository()->results($run), 'attention', 'code');

        self::assertSame($attention((int) $first['id']), $attention($second['id']), 'mesmos dados e instante => mesmo resultado');
        $ev = $first['evaluation'];
        self::assertLessThan(strtotime($ev['eval_window']['from']), strtotime($ev['fit_window']['to']), 'ajuste termina antes da avaliação começar');
        self::assertContains($ev['chosen_threshold'], [25, 35, 45, 55]);
        self::assertContains('INSUFICIENTE', $attention($second['id']), 'equipamento recente não recebe classificação');
        self::assertContains('CORRETIVA', $attention($second['id']));

        // Recarregar exige DEMO_ALLOW_RESET e o marcador do conjunto sintético.
        $console = new Console(new Services(self::config()->with(['DB_USER' => self::config()->require('DB_MIGRATION_USER'), 'DB_PASSWORD' => self::config()->require('DB_MIGRATION_PASSWORD'), 'DEMO_ALLOW_RESET' => 'false']), self::ROOT, null, $this->clock));
        ob_start();
        $code = @$console->run(['demo:seed', '--reset']);
        ob_end_clean();
        self::assertSame(1, $code);
    }

    /** @return array<string, mixed> */
    private function row(int $id): array
    {
        return $this->services->equipmentRepository()->find($id) ?? throw new \LogicException('equipamento do teste');
    }
}
