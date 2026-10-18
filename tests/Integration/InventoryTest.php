<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\Filters;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Tests\Support\FirebirdTestCase;

final class InventoryTest extends FirebirdTestCase
{
    public function testCreatePersistsTypeSpecificSpecsAndHistory(): void
    {
        $id = $this->createEquipment(['type_code' => 'RADAR', 'specs' => ['functions' => ['VELOCIDADE', 'AVANCO_SEMAFORO'], 'speed_limit_kmh' => '50', 'lanes' => '2']]);
        $row = $this->services->equipmentRepository()->find($id);
        $events = $this->services->eventRepository()->forResource('EQUIPAMENTO', $id);

        self::assertSame('RAD-0001', $row['code']);
        self::assertSame(['functions' => ['VELOCIDADE', 'AVANCO_SEMAFORO'], 'speed_limit_kmh' => 50, 'lanes' => 2], $row['specs']);
        self::assertSame('CADASTRO', $events[0]['event_type']);
        self::assertSame('Marília', $row['city']);
    }

    public function testSerialIsUniquePerManufacturerAndUnknownSerialsDoNotCollide(): void
    {
        $this->createEquipment(['serial_number' => 'ABC-1']);
        $this->createEquipment(['serial_number' => 'abc-1', 'manufacturer' => 'Outra Marca']);
        $this->createEquipment(['serial_number' => null]);
        $this->createEquipment(['serial_number' => null]);

        try {
            $this->createEquipment(['serial_number' => ' abc-1 ', 'manufacturer' => 'óptica teste']);
            self::fail('série duplicada no mesmo fabricante deveria ser recusada pelo banco');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('serial_number', $e->errors);
        }
    }

    public function testConcurrentDuplicateSerialIsResolvedByTheDatabase(): void
    {
        $a = $this->newServices();
        $b = $this->newServices();
        $a->database()->pdo()->beginTransaction();
        $a->equipmentRepository()->insert($this->rawRow('CORRIDA-1', 'CAM-9000'));

        try {
            $this->createEquipment(['serial_number' => 'CORRIDA-1'], $b);
            self::fail('B deveria esperar a transação de A e desistir pelo timeout');
        } catch (ConcurrencyConflict) {
            // B não grava enquanto A não decide.
        }
        $a->database()->pdo()->commit();

        $this->expectException(ValidationFailed::class);
        $this->createEquipment(['serial_number' => 'CORRIDA-1'], $b);
    }

    public function testStaleFormVersionIsRejectedWithoutLosingTheOtherEdit(): void
    {
        $id = $this->createEquipment();
        $version = (int) $this->services->equipmentRepository()->find($id)['version_no'];
        $this->services->equipment()->update($id, $this->equipmentData(['version' => (string) $version, 'model' => 'Edição de Ana']), $this->userId());

        try {
            $this->services->equipment()->update($id, $this->equipmentData(['version' => (string) $version, 'model' => 'Edição de Bia']), $this->userId());
            self::fail('versão antiga deveria gerar conflito');
        } catch (ConcurrencyConflict) {
        }
        self::assertSame('Edição de Ana', $this->services->equipmentRepository()->find($id)['model']);
        $changes = array_filter($this->services->eventRepository()->forResource('EQUIPAMENTO', $id), static fn($e) => $e['event_type'] === 'ALTERACAO');
        self::assertCount(1, $changes);
    }

    public function testStatusTransitionsAreEnforcedAndRecorded(): void
    {
        $id = $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']);

        try {
            $this->services->equipment()->changeStatus($id, 'FALHA', 'teste', $this->userId());
            self::fail('não instalado não pode falhar');
        } catch (RuleViolation) {
        }
        $this->services->equipment()->changeStatus($id, 'OPERACIONAL', 'instalado', $this->userId());
        $row = $this->services->equipmentRepository()->find($id);

        self::assertSame('OPERACIONAL', $row['status']);
        self::assertSame('INSTALADO', $row['location_kind'], 'local planejado vira local de instalação');
        self::assertSame('2026-10-18', $row['installed_on']);
        $last = $this->services->eventRepository()->forResource('EQUIPAMENTO', $id)[0];
        self::assertSame(['de' => 'NAO_INSTALADO', 'para' => 'OPERACIONAL', 'motivo' => 'instalado'], $last['payload']);
        self::assertNotNull($last['actor_name']);
    }

    public function testArchivingRemovesFromOperationButKeepsHistory(): void
    {
        $id = $this->createEquipment();
        $this->services->equipment()->archive($id, 'substituído', $this->userId());

        self::assertSame([], $this->services->equipmentRepository()->forCentral(new Filters()));
        self::assertCount(2, $this->services->eventRepository()->forResource('EQUIPAMENTO', $id));
        $this->expectException(RuleViolation::class);
        $this->services->equipment()->update($id, $this->equipmentData(['version' => '99']), $this->userId());
    }

    public function testMobileResourceKeepsBaseSeparateFromObservedPosition(): void
    {
        try {
            $this->createEquipment(['type_code' => 'PATINETE', 'location_kind' => 'INSTALADO', 'specs' => ['range_km' => '40']]);
            self::fail('recurso móvel exige base');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('location_kind', $e->errors);
        }
        $id = $this->createAgent();
        $outcome = $this->services->ingestor()->position('t-pos-1', 'AGENTE', $id, '-22.210000', '-49.940000', $this->clock->now(), true);
        $agent = $this->services->agentRepository()->find($id);

        self::assertSame('APLICADO', $outcome);
        self::assertSame(-22.2165, $agent['base_lat']);
        self::assertSame(-22.21, $agent['pos_lat']);
    }

    private ?int $uid = null;

    private function userId(): int
    {
        return $this->uid ??= $this->services->auth()->createUser('operadora', 'Operadora de Teste', 'OPERADOR', 'senha-de-teste-longa')['id'];
    }

    /** @return array<string, mixed> */
    private function rawRow(string $serial, string $code): array
    {
        return ['code' => $code, 'type_code' => 'CAMERA', 'manufacturer' => 'Óptica Teste', 'model' => 'OT-1', 'serial_number' => $serial, 'agency_id' => $this->agencyId,
            'status' => 'OPERACIONAL', 'status_changed_at' => '2026-10-18 15:00:00', 'location_kind' => 'INSTALADO', 'created_at' => '2026-10-18 15:00:00', 'updated_at' => '2026-10-18 15:00:00'];
    }
}
