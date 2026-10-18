<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

use DateTimeImmutable;
use Fiscaliza\Config;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Migrator;
use Fiscaliza\Services;
use PHPUnit\Framework\TestCase;

/**
 * Testes contra o Firebird real (banco de testes DB_TEST_DSN). Na primeira classe da execução o esquema é apagado
 * e recriado pelas migrações (prova de criação em banco vazio); cada teste começa com as tabelas vazias.
 * Os casos de uso rodam com o usuário da aplicação (somente DML), como em produção.
 */
abstract class FirebirdTestCase extends TestCase
{
    protected const ROOT = __DIR__ . '/../..';
    private static bool $schemaReady = false;

    protected Clock $clock;
    protected Services $services;
    protected int $agencyId;
    protected int $regionId;

    public static function setUpBeforeClass(): void
    {
        if (self::$schemaReady) {
            return;
        }
        $owner = self::ownerDatabase();
        self::assertSeparateFromMainDatabase($owner);
        $migrator = new Migrator($owner, self::ROOT . '/database/migrations');
        $migrator->dropEverything();
        $migrator->migrate();
        $migrator->grantApplicationAccess(self::config()->require('DB_USER'));
        $owner->disconnect();
        self::$schemaReady = true;
    }

    protected function setUp(): void
    {
        $owner = self::ownerDatabase();
        $owner->transaction(static function (Database $db): void {
            foreach (['ANALYSIS_RESULT', 'ANALYSIS_RUN', 'ALERT', 'RESOURCE_EVENT', 'MAINTENANCE_ORDER', 'EQUIPMENT', 'AGENT', 'REGION', 'AGENCY', 'APP_META', 'LOGIN_ATTEMPT', 'APP_USER'] as $table) {
                $db->execute("DELETE FROM {$table}");
            }
        });
        $owner->disconnect();
        $this->clock = new Clock(new DateTimeImmutable('2026-10-18 15:00:00', Time::utc()));
        $this->services = $this->newServices();
        $refs = $this->services->referenceRepository();
        $this->agencyId = $refs->insertAgency('DMT', 'Departamento de Trânsito (teste)', 'TRANSITO');
        $this->regionId = $refs->insertRegion('Centro', 'CENTRO', '-22.214000', '-49.950500');
    }

    /** Configuração dos testes (também usada pelos processos de tests/Support/parallel-worker.php). */
    public static function config(): Config
    {
        $config = Config::load(self::ROOT);

        return $config->with(['DB_DSN' => $config->require('DB_TEST_DSN'), 'DB_STATEMENT_TIMEOUT_SECONDS' => '2', 'DEMO_ALLOW_RESET' => 'true']);
    }

    /**
     * Recusa apagar o esquema sem evidência de que DB_TEST_DSN não é o banco principal (DB_DSN). Se o principal
     * não estiver acessível (CI sem banco principal), vale a comparação dos DSNs.
     */
    private static function assertSeparateFromMainDatabase(Database $testOwner): void
    {
        $raw = Config::load(self::ROOT);
        $main = $raw->get('DB_DSN');
        $mainFile = null;
        if ($main !== '' && DatabaseSeparation::problem($main, $raw->get('DB_TEST_DSN')) === null) {
            try {
                $mainOwner = new Database($main, $raw->require('DB_MIGRATION_USER'), $raw->require('DB_MIGRATION_PASSWORD'), 5);
                $mainFile = (string) $mainOwner->value('SELECT MON$DATABASE_NAME FROM MON$DATABASE');
                $mainOwner->disconnect();
            } catch (\RuntimeException) {
                // Principal inexistente ou inacessível: não há arquivo para comparar.
            }
        }
        $testFile = $mainFile === null ? null : (string) $testOwner->value('SELECT MON$DATABASE_NAME FROM MON$DATABASE');
        $problem = DatabaseSeparation::problem($main, $raw->get('DB_TEST_DSN'), $mainFile, $testFile);
        if ($problem !== null) {
            throw new \RuntimeException("Testes destrutivos recusados: {$problem} Configure um banco de testes dedicado.");
        }
    }

    protected static function ownerDatabase(): Database
    {
        $c = self::config();

        return new Database($c->require('DB_TEST_DSN'), $c->require('DB_MIGRATION_USER'), $c->require('DB_MIGRATION_PASSWORD'), 10);
    }

    /** Nova conexão com a identidade da aplicação (para simular duas sessões concorrentes). */
    protected function newServices(): Services
    {
        return new Services(self::config(), self::ROOT, null, $this->clock);
    }

    /** @param array<string, mixed> $override */
    protected function equipmentData(array $override = []): array
    {
        return $override + [
            'type_code' => 'CAMERA', 'status' => 'OPERACIONAL', 'manufacturer' => 'Óptica Teste', 'model' => 'OT-1', 'serial_number' => null,
            'agency_id' => (string) $this->agencyId, 'street' => 'Avenida Sampaio Vidal', 'address_ref' => 'em frente à praça', 'cep' => '17500-200',
            'region_id' => (string) $this->regionId, 'lat' => '-22.2112', 'lng' => '-49.9515', 'location_kind' => 'INSTALADO',
            'specs' => ['resolution' => '1080P', 'night_vision' => '1'],
        ];
    }

    /** @param array<string, mixed> $override */
    protected function createEquipment(array $override = [], ?Services $services = null): int
    {
        return ($services ?? $this->services)->equipment()->create($this->equipmentData($override), null);
    }

    /** @param array<string, mixed> $override */
    protected function createAgent(array $override = []): int
    {
        return $this->services->agents()->create($override + [
            'call_sign' => 'Hermes 99', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId, 'status' => 'EM_ATIVIDADE',
            'base_name' => 'Base de teste', 'base_lat' => '-22.2165', 'base_lng' => '-49.9490', 'region_id' => (string) $this->regionId,
        ], null);
    }

    protected function equipmentStatus(int $id): string
    {
        return (string) $this->services->equipmentRepository()->find($id)['status'];
    }
}
