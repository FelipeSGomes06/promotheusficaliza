<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\Filters;
use Fiscaliza\Cli\Console;
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

    public function testNoEquipmentInServiceShowsNotApplicable(): void
    {
        $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']);
        $snap = $this->services->central()->snapshot(new Filters());

        self::assertNull($snap['kpis']['equipment']['availability']);
        self::assertSame('PLANEJADO', $snap['resources'][0]['position']['kind']);
    }

    public function testResourceWithoutCoordinatesIsListedButNotPlaced(): void
    {
        $this->createEquipment(['lat' => '', 'lng' => '']);
        $resource = $this->services->central()->snapshot(new Filters())['resources'][0];

        self::assertNull($resource['position']['lat']);
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
}
