<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Application\Filters;
use Fiscaliza\Application\Input;
use Fiscaliza\Config;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Http\Middleware\SecurityHeaders;
use Fiscaliza\Infrastructure\Migrator;
use PHPUnit\Framework\TestCase;

final class InputAndSpecsTest extends TestCase
{
    public function testRadarRequiresSpeedLimitOnlyWhenEnforcingSpeed(): void
    {
        $radar = EquipmentCatalog::get('RADAR');
        self::assertNotNull($radar);

        [, $errors] = $radar->validateSpecs(['functions' => ['VELOCIDADE']]);
        self::assertArrayHasKey('specs.speed_limit_kmh', $errors);

        [$specs, $errors] = $radar->validateSpecs(['functions' => ['AVANCO_SEMAFORO']]);
        self::assertSame([], $errors, 'fiscalização semafórica não exige limite');
        self::assertSame(['AVANCO_SEMAFORO'], $specs['functions']);

        [, $errors] = $radar->validateSpecs(['functions' => ['VELOCIDADE'], 'speed_limit_kmh' => '300']);
        self::assertArrayHasKey('specs.speed_limit_kmh', $errors);

        [, $errors] = $radar->validateSpecs(['functions' => ['TELETRANSPORTE']]);
        self::assertSame('Opção inválida.', $errors['specs.functions']);
    }

    public function testCameraCapabilitiesAreStructured(): void
    {
        $camera = EquipmentCatalog::get('CAMERA');
        self::assertNotNull($camera);
        [$specs, $errors] = $camera->validateSpecs(['resolution' => '4K', 'night_vision' => '1', 'unexpected' => 'x']);

        self::assertSame([], $errors);
        self::assertSame(['resolution' => '4K', 'night_vision' => true, 'color_image' => false, 'ptz' => false], $specs);
        self::assertSame(['label' => 'Visão noturna (IR)', 'value' => 'Sim'], $camera->describe($specs)[1]);
    }

    public function testBoundaryInputNormalization(): void
    {
        $input = new Input(['cep' => '17500012', 'lat' => '-22,2139', 'lng' => '-49.9458', 'far' => '-23.5', 'name' => "Rua\x07 São João ", 'bad' => '17500-01']);

        self::assertSame('17500-012', $input->cep('cep'));
        self::assertSame('-22.213900', $input->coordinate('lat', 'lat'));
        self::assertSame('-49.945800', $input->coordinate('lng', 'lng'));
        self::assertNull($input->coordinate('far', 'lat'));
        self::assertSame('Rua São João', $input->text('name', 40));
        self::assertNull($input->cep('bad'));
        self::assertArrayHasKey('far', $input->errors());
        self::assertArrayHasKey('bad', $input->errors());

        $this->expectException(ValidationFailed::class);
        $input->assertValid();
    }

    public function testFiltersDropUnknownValuesAndScopeResourceKinds(): void
    {
        $filters = Filters::fromQuery(['categoria' => ['RADAR', "'; DROP TABLE EQUIPMENT;--"], 'situacao' => 'FALHA,XYZ', 'bairro' => ['3', 'abc', '-1'], 'orgao' => [['nested']]]);

        self::assertSame(['RADAR'], $filters->categories);
        self::assertSame(['FALHA'], $filters->statuses);
        self::assertSame([3], $filters->regions);
        self::assertSame([], $filters->agencies);
        self::assertTrue($filters->includesEquipment());
        self::assertFalse($filters->includesAgents(), 'categoria só de equipamento exclui agentes');

        $agentsOnly = Filters::fromQuery(['situacao' => ['EM_ATIVIDADE']]);
        self::assertFalse($agentsOnly->includesEquipment());
        self::assertTrue($agentsOnly->includesAgents());
    }

    public function testDotEnvParsingAndBasePathValidation(): void
    {
        $values = Config::parseDotEnv("# comentário\nA=1\nB=\"com # dentro\"\nC=valor # comentário\n\nD='x'\n");
        self::assertSame(['A' => '1', 'B' => 'com # dentro', 'C' => 'valor', 'D' => 'x'], $values);

        self::assertSame('/promotheusfiscaliza', (new Config(['APP_BASE_PATH' => '/promotheusfiscaliza/']))->basePath());
        $this->expectException(\RuntimeException::class);
        (new Config(['APP_BASE_PATH' => '/x"><script>']))->basePath();
    }

    public function testTileOriginForCsp(): void
    {
        self::assertSame('https://tile.openstreetmap.org', SecurityHeaders::tileOrigin('https://tile.openstreetmap.org/{z}/{x}/{y}.png'));
        self::assertSame('https://*.tile.example.org', SecurityHeaders::tileOrigin('https://{s}.tile.example.org/{z}/{x}/{y}.png'));
        self::assertNull(SecurityHeaders::tileOrigin('javascript:alert(1)'));
    }

    public function testMigrationSplitterIgnoresCommentsAndSplitsAtLineEnd(): void
    {
        $sql = "-- comentário; com ponto e vírgula\nCREATE TABLE A (X INT);\n\nCREATE TABLE B (\n  Y VARCHAR(10) DEFAULT 'a;b'\n);\n";

        self::assertSame(['CREATE TABLE A (X INT)', "CREATE TABLE B (\n  Y VARCHAR(10) DEFAULT 'a;b'\n)"], Migrator::statements($sql));
    }
}
