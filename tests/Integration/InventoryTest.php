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

    /** A05 da auditoria de 25/10/2026: a largura mínima do número não limita a contagem (999 → 1000 → 1001). */
    public function testCodesKeepCountingPastTheMinimumWidth(): void
    {
        $db = $this->services->database();
        $agent = $this->createAgent(['call_sign' => 'Fronteira 1']);
        $db->execute("UPDATE AGENT SET CODE = 'AGT-999' WHERE ID = ?", [$agent]);
        $codes = [];
        foreach (['Fronteira 2', 'Fronteira 3'] as $callSign) {
            $codes[] = $this->services->agentRepository()->find($this->createAgent(['call_sign' => $callSign]))['code'];
        }
        self::assertSame(['AGT-1000', 'AGT-1001'], $codes);

        $first = $this->createEquipment();
        $prefix = explode('-', (string) $this->services->equipmentRepository()->find($first)['code'])[0];
        $db->execute('UPDATE EQUIPMENT SET CODE = ? WHERE ID = ?', ["{$prefix}-9999", $first]);
        $codes = [];
        foreach (['S-2', 'S-3'] as $serial) {
            $codes[] = $this->services->equipmentRepository()->find($this->createEquipment(['serial_number' => $serial]))['code'];
        }
        self::assertSame(["{$prefix}-10000", "{$prefix}-10001"], $codes);
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

    /** OP-03: posição e telemetria não são edição de cadastro e não invalidam um formulário aberto. */
    public function testPositionAndTelemetryDoNotInvalidateAnOpenRegistrationForm(): void
    {
        $data = $this->equipmentData(['type_code' => 'VEICULO', 'location_kind' => 'BASE', 'specs' => ['vehicle_role' => 'EQUIPE', 'call_sign' => 'AUD-01']]);
        $id = $this->services->equipment()->create($data, null);
        $version = (string) $this->services->equipmentRepository()->find($id)['version_no'];

        $this->services->ingestor()->position('pos-1', 'EQUIPAMENTO', $id, '-22.211300', '-49.951600', $this->clock->now()->modify('+1 second'), false);
        $this->services->ingestor()->equipmentStatus('tel-1', $id, \Fiscaliza\Domain\EquipmentStatus::Falha, $this->clock->now()->modify('+2 seconds'), 'telemetria');
        $this->services->agents()->changeStatus($this->createAgent(), 'EM_PAUSA', null, 'SIMULADOR');

        $this->services->equipment()->update($id, ['model' => 'Modelo corrigido', 'version' => $version] + $data, $this->userId());
        $row = $this->services->equipmentRepository()->find($id);
        self::assertSame('Modelo corrigido', $row['model']);
        self::assertSame(-22.2113, $row['pos_lat'], 'a posição recebida durante a edição é preservada');
        self::assertSame('FALHA', $row['status']);
    }

    public function testAgentStatusChangeDoesNotInvalidateAnOpenAgentForm(): void
    {
        $id = $this->createAgent();
        $version = (string) $this->services->agentRepository()->find($id)['version_no'];
        $this->services->agents()->changeStatus($id, 'EM_PAUSA', null, 'SIMULADOR', 'pausa simulada');

        $this->services->agents()->update($id, ['call_sign' => 'Hermes 98', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId, 'base_name' => 'Base de teste',
            'base_lat' => '-22.2165', 'base_lng' => '-49.9490', 'version' => $version], $this->userId());
        self::assertSame('Hermes 98', $this->services->agentRepository()->find($id)['call_sign']);
    }

    public function testInstallationChangesTheRegistrationAndInvalidatesAnOlderForm(): void
    {
        $id = $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']);
        $version = (string) $this->services->equipmentRepository()->find($id)['version_no'];
        $this->services->equipment()->changeStatus($id, 'OPERACIONAL', 'instalado', $this->userId());

        $this->expectException(ConcurrencyConflict::class);
        $this->services->equipment()->update($id, $this->equipmentData(['location_kind' => 'PLANEJADO', 'version' => $version]), $this->userId());
    }

    public function testPendingChangesCompareTheTypedVersionWithTheSavedOne(): void
    {
        $id = $this->createEquipment();
        $changes = $this->services->equipment()->pendingChanges($id, $this->equipmentData(['model' => 'OT-2', 'lat' => '-22.211200']));

        self::assertSame(['model'], array_keys($changes), 'mesma coordenada escrita de outra forma não é diferença');
        self::assertSame(['antes' => 'OT-1', 'depois' => 'OT-2'], $changes['model']);
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

    public function testLocationKindFollowsTheStatusOnCreationAndInTransitions(): void
    {
        try {
            $this->createEquipment(['status' => 'OPERACIONAL', 'location_kind' => 'PLANEJADO']);
            self::fail('operacional já foi instalado');
        } catch (ValidationFailed $e) {
            self::assertSame(['location_kind'], array_keys($e->errors));
        }
        self::assertGreaterThan(0, $this->createEquipment(['status' => 'DESATIVADO', 'location_kind' => 'PLANEJADO', 'serial_number' => 'D-1']), 'desativado aceita os dois');

        // Reposição de um furtado: o ponto continua, como planejado; reinstalar registra a nova data.
        $id = $this->createEquipment(['status' => 'FURTADO', 'serial_number' => 'F-1']);
        $this->services->equipment()->changeStatus($id, 'NAO_INSTALADO', 'reposição', $this->userId());
        self::assertSame(['NAO_INSTALADO', 'PLANEJADO'], [$this->services->equipmentRepository()->find($id)['status'], $this->services->equipmentRepository()->find($id)['location_kind']]);
        $this->services->equipment()->changeStatus($id, 'OPERACIONAL', 'reinstalado', $this->userId());
        $row = $this->services->equipmentRepository()->find($id);
        self::assertSame(['INSTALADO', '2026-10-18'], [$row['location_kind'], $row['installed_on']]);

        // Recurso móvel: a base não muda e a data passa a ser a de entrada em operação.
        $mobile = $this->createEquipment(['type_code' => 'PATINETE', 'status' => 'NAO_INSTALADO', 'location_kind' => 'BASE', 'specs' => ['range_km' => '40']]);
        $this->services->equipment()->changeStatus($mobile, 'OPERACIONAL', 'entrou em operação', $this->userId());
        $row = $this->services->equipmentRepository()->find($mobile);
        self::assertSame(['BASE', '2026-10-18'], [$row['location_kind'], $row['installed_on']]);
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
