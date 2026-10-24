<?php

declare(strict_types=1);

namespace Fiscaliza;

use Fiscaliza\Application\AddressLocator;
use Fiscaliza\Application\AddressPoint;
use Fiscaliza\Application\AgentService;
use Fiscaliza\Application\AlertService;
use Fiscaliza\Application\AnalysisService;
use Fiscaliza\Application\AuthService;
use Fiscaliza\Application\CepLookup;
use Fiscaliza\Application\CentralQuery;
use Fiscaliza\Application\EquipmentService;
use Fiscaliza\Application\EventIngestor;
use Fiscaliza\Application\FineQuery;
use Fiscaliza\Application\FineService;
use Fiscaliza\Application\Geocoder;
use Fiscaliza\Application\History;
use Fiscaliza\Application\MaintenanceService;
use Fiscaliza\Application\PlanningService;
use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Application\UrbanUpdater;
use Fiscaliza\Demo\DemoScenario;
use Fiscaliza\Demo\DemoSeeder;
use Fiscaliza\Demo\Simulator;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Migrator;
use Fiscaliza\Infrastructure\NominatimGeocoder;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\AlertRepository;
use Fiscaliza\Infrastructure\Repository\AnalysisRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\EventRepository;
use Fiscaliza\Infrastructure\Repository\FineRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;
use Fiscaliza\Infrastructure\Repository\MetaRepository;
use Fiscaliza\Infrastructure\Repository\MissionRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;
use Fiscaliza\Infrastructure\Repository\UrbanRepository;
use Fiscaliza\Infrastructure\Repository\UserRepository;
use Fiscaliza\Infrastructure\ViaCepClient;

/**
 * Raiz de composição: cria cada objeto uma vez, sob demanda, com as dependências explícitas.
 * A web usa a identidade da aplicação (somente DML); migrações e carga de demonstração usam a identidade de migração.
 */
final class Services
{
    /** @var array<string, object> */
    private array $instances = [];

    public function __construct(
        public readonly Config $config,
        public readonly string $root,
        private ?Database $database = null,
        private ?Clock $clock = null,
        private ?CepLookup $cepLookup = null,
        private ?Geocoder $geocoder = null,
    ) {}

    /** Mesmos serviços, mas conectados como dono do esquema (migrações, carga e reinício da demonstração). */
    public function asMigrationIdentity(): self
    {
        return new self($this->config, $this->root, $this->migrationDatabase(), $this->clock, $this->cepLookup, $this->geocoder);
    }

    public function withClock(Clock $clock): self
    {
        return new self($this->config, $this->root, $this->database, $clock, $this->cepLookup, $this->geocoder);
    }

    /** Mesmos serviços com outra consulta de CEP (os testes usam um falso; nunca a rede). */
    public function withCepLookup(CepLookup $lookup): self
    {
        return new self($this->config, $this->root, $this->database, $this->clock, $lookup, $this->geocoder);
    }

    /** Mesmos serviços com outro provedor de localização (os testes usam um falso; nunca a rede). */
    public function withGeocoder(Geocoder $geocoder): self
    {
        return new self($this->config, $this->root, $this->database, $this->clock, $this->cepLookup, $geocoder);
    }

    /** Provedor de CEP (ADR-0021), criado só quando algo consulta um CEP. */
    public function cepLookup(): CepLookup
    {
        return $this->cepLookup ??= new ViaCepClient($this->config->cepLookupUrl());
    }

    /** Provedor de localização pelo endereço (ADR-0021), criado só quando algo localiza uma rua. */
    public function geocoder(): Geocoder
    {
        return $this->geocoder ??= new NominatimGeocoder($this->config->geocoderUrl());
    }

    public function addressPoint(): AddressPoint
    {
        return $this->shared(__FUNCTION__, fn() => new AddressPoint($this->addressLocator(...), $this->referenceRepository()));
    }

    public function addressLocator(): AddressLocator
    {
        return $this->shared(__FUNCTION__, fn() => new AddressLocator($this->cepLookup(), $this->geocoder()));
    }

    public function clock(): Clock
    {
        return $this->clock ??= new Clock();
    }

    public function database(): Database
    {
        return $this->database ??= new Database(
            $this->config->require('DB_DSN'),
            $this->config->require('DB_USER'),
            $this->config->require('DB_PASSWORD'),
            $this->config->int('DB_STATEMENT_TIMEOUT_SECONDS', 5),
        );
    }

    public function migrationDatabase(): Database
    {
        return $this->shared('migration-db', fn() => new Database(
            $this->config->require('DB_DSN'),
            $this->config->require('DB_MIGRATION_USER'),
            $this->config->require('DB_MIGRATION_PASSWORD'),
            60,
        ));
    }

    public function migrator(): Migrator
    {
        return new Migrator($this->migrationDatabase(), $this->root . '/database/migrations');
    }

    public function equipmentRepository(): EquipmentRepository
    {
        return $this->shared(__FUNCTION__, fn() => new EquipmentRepository($this->database()));
    }

    public function agentRepository(): AgentRepository
    {
        return $this->shared(__FUNCTION__, fn() => new AgentRepository($this->database()));
    }

    public function maintenanceRepository(): MaintenanceRepository
    {
        return $this->shared(__FUNCTION__, fn() => new MaintenanceRepository($this->database()));
    }

    public function eventRepository(): EventRepository
    {
        return $this->shared(__FUNCTION__, fn() => new EventRepository($this->database()));
    }

    public function alertRepository(): AlertRepository
    {
        return $this->shared(__FUNCTION__, fn() => new AlertRepository($this->database()));
    }

    public function referenceRepository(): ReferenceRepository
    {
        return $this->shared(__FUNCTION__, fn() => new ReferenceRepository($this->database()));
    }

    public function userRepository(): UserRepository
    {
        return $this->shared(__FUNCTION__, fn() => new UserRepository($this->database()));
    }

    public function metaRepository(): MetaRepository
    {
        return $this->shared(__FUNCTION__, fn() => new MetaRepository($this->database()));
    }

    public function analysisRepository(): AnalysisRepository
    {
        return $this->shared(__FUNCTION__, fn() => new AnalysisRepository($this->database()));
    }

    public function urbanRepository(): UrbanRepository
    {
        return $this->shared(__FUNCTION__, fn() => new UrbanRepository($this->database()));
    }

    public function missionRepository(): MissionRepository
    {
        return $this->shared(__FUNCTION__, fn() => new MissionRepository($this->database()));
    }

    public function fineRepository(): FineRepository
    {
        return $this->shared(__FUNCTION__, fn() => new FineRepository($this->database()));
    }

    public function fines(): FineService
    {
        return $this->shared(__FUNCTION__, fn() => new FineService(
            $this->database(),
            $this->fineRepository(),
            $this->referenceRepository(),
            $this->equipmentRepository(),
            $this->agentRepository(),
            $this->clock(),
        ));
    }

    public function fineQuery(): FineQuery
    {
        return $this->shared(__FUNCTION__, fn() => new FineQuery(
            $this->database(),
            $this->fineRepository(),
            $this->equipmentRepository(),
            $this->agentRepository(),
            $this->clock(),
        ));
    }

    public function history(): History
    {
        return $this->shared(__FUNCTION__, fn() => new History($this->eventRepository(), $this->clock()));
    }

    public function alerts(): AlertService
    {
        return $this->shared(__FUNCTION__, fn() => new AlertService(
            $this->database(),
            $this->alertRepository(),
            $this->equipmentRepository(),
            $this->agentRepository(),
            $this->maintenanceRepository(),
            $this->userRepository(),
            $this->clock(),
        ));
    }

    public function equipment(): EquipmentService
    {
        return $this->shared(__FUNCTION__, fn() => new EquipmentService(
            $this->database(),
            $this->equipmentRepository(),
            $this->referenceRepository(),
            $this->maintenanceRepository(),
            $this->history(),
            $this->alerts(),
            $this->clock(),
            $this->addressPoint(),
        ));
    }

    public function agents(): AgentService
    {
        return $this->shared(__FUNCTION__, fn() => new AgentService(
            $this->database(),
            $this->agentRepository(),
            $this->referenceRepository(),
            $this->history(),
            $this->alerts(),
            $this->clock(),
            $this->addressPoint(),
        ));
    }

    public function maintenance(): MaintenanceService
    {
        return $this->shared(__FUNCTION__, fn() => new MaintenanceService(
            $this->database(),
            $this->maintenanceRepository(),
            $this->equipmentRepository(),
            $this->equipment(),
            $this->history(),
            $this->alerts(),
            $this->clock(),
        ));
    }

    public function ingestor(): EventIngestor
    {
        return $this->shared(__FUNCTION__, fn() => new EventIngestor(
            $this->database(),
            $this->equipmentRepository(),
            $this->agentRepository(),
            $this->eventRepository(),
            $this->metaRepository(),
            $this->clock(),
        ));
    }

    public function central(): CentralQuery
    {
        return $this->shared(__FUNCTION__, fn() => new CentralQuery(
            $this->database(),
            $this->equipmentRepository(),
            $this->agentRepository(),
            $this->maintenanceRepository(),
            $this->alertRepository(),
            $this->missionRepository(),
            $this->eventRepository(),
            $this->metaRepository(),
            $this->referenceRepository(),
            $this->clock(),
            $this->config->int('SIM_INTERVAL_SECONDS', 5),
        ));
    }

    public function auth(): AuthService
    {
        return $this->shared(__FUNCTION__, fn() => new AuthService($this->database(), $this->userRepository(), $this->clock()));
    }

    public function analysis(): AnalysisService
    {
        return $this->shared(__FUNCTION__, fn() => new AnalysisService(
            $this->database(),
            $this->analysisRepository(),
            $this->equipmentRepository(),
            $this->eventRepository(),
            $this->maintenanceRepository(),
            $this->clock(),
        ));
    }

    public function urbanImporter(): UrbanImporter
    {
        return new UrbanImporter($this->database(), $this->urbanRepository(), $this->clock());
    }

    public function urbanUpdater(): UrbanUpdater
    {
        return new UrbanUpdater($this->database(), $this->urbanRepository(), $this->clock());
    }

    public function planning(): PlanningService
    {
        return $this->shared(__FUNCTION__, fn() => new PlanningService(
            $this->database(),
            $this->urbanRepository(),
            $this->missionRepository(),
            $this->agentRepository(),
            $this->referenceRepository(),
            $this->clock(),
        ));
    }

    public function seeder(): DemoSeeder
    {
        return new DemoSeeder($this, (int) $this->config->int('SIM_SEED', 2026), DemoScenario::profile($this->config->get('DEMO_PROFILE', DemoScenario::COMPACT)));
    }

    public function simulator(): Simulator
    {
        return new Simulator($this, (int) $this->config->int('SIM_SEED', 2026));
    }

    /**
     * @template T of object
     * @param callable(): T $factory
     * @return T
     */
    private function shared(string $key, callable $factory): object
    {
        /** @var T */
        return $this->instances[$key] ??= $factory();
    }
}
