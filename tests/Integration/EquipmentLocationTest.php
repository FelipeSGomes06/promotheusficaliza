<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\CepAddress;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Tests\Support\FakeCepLookup;
use Fiscaliza\Tests\Support\FakeGeocoder;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Local do equipamento (ADR-0021): obrigatório, inclusive no planejado; mapa ou endereço; bairro pelo ponto. */
final class EquipmentLocationTest extends FirebirdTestCase
{
    private int $fragata;
    private FakeGeocoder $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fragata = $this->services->referenceRepository()->insertRegion('Fragata', 'SUL', '-22.226000', '-49.930000');
        $this->geo = new FakeGeocoder(['Rua Paraíba' => ['lat' => '-22.226000', 'lng' => '-49.930000', 'box' => [-22.228, -22.224, -49.935, -49.925]]]);
        $this->services = $this->services->withGeocoder($this->geo)
            ->withCepLookup(new FakeCepLookup(['17509-000' => new CepAddress('17509-000', 'Rua Paraíba', 'Fragata', 'Marília', 'SP')]));
    }

    /** @return array<string, mixed> equipamento planejado, sem ponto e sem endereço */
    private function planned(array $override = []): array
    {
        return $override + $this->equipmentData(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO', 'lat' => '', 'lng' => '', 'street' => '', 'address_ref' => '', 'cep' => '']);
    }

    public function testPlannedEquipmentNeedsALocation(): void
    {
        try {
            $this->createEquipment($this->planned());
            self::fail('esperava local obrigatório');
        } catch (ValidationFailed $e) {
            self::assertSame('Marque o local no mapa ou, sem o mapa, informe o CEP e a rua.', $e->errors['lat'] ?? null);
        }
    }

    public function testWithoutThePointTheLocationGoesToTheAddressAndTheRegionComesFromIt(): void
    {
        $id = $this->createEquipment($this->planned(['cep' => '17509000', 'region_id' => (string) $this->regionId]));
        $row = $this->services->equipmentRepository()->find($id);

        self::assertSame([-22.226, -49.93, 'Rua Paraíba', $this->fragata], [$row['lat'] ?? null, $row['lng'] ?? null, $row['street'] ?? null, (int) ($row['region_id'] ?? 0)], 'rua do CEP, ponto do endereço, bairro do ponto (o enviado é ignorado)');
    }

    public function testPointOutsideTheAddressGoesToTheAddress(): void
    {
        $id = $this->createEquipment($this->planned(['street' => 'Rua Paraíba', 'address_ref' => 'em frente à praça', 'lat' => '-22.2010', 'lng' => '-49.9690']));
        self::assertSame(-22.226, $this->services->equipmentRepository()->find($id)['lat'] ?? null);

        $near = $this->createEquipment($this->planned(['street' => 'Rua Paraíba', 'lat' => '-22.2275', 'lng' => '-49.9260']));
        self::assertSame(-22.2275, $this->services->equipmentRepository()->find($near)['lat'] ?? null, 'dentro do trecho da rua: o ajuste fica');
    }

    /** Cadastro antigo sem coordenadas: ao editar, precisa ganhar um local (pelo mapa ou pelo endereço). */
    public function testLegacyEquipmentWithoutCoordinatesGetsALocationOnEdit(): void
    {
        $id = $this->createEquipment();
        $this->services->equipmentRepository()->updateState($id, ['lat' => null, 'lng' => null]);
        $actor = (int) $this->services->auth()->createUser('op1', 'Operador', 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
        $version = fn(): string => (string) $this->services->equipmentRepository()->find($id)['version_no'];

        try {
            $this->services->equipment()->update($id, $this->equipmentData(['lat' => '', 'lng' => '', 'street' => '', 'address_ref' => '', 'cep' => '', 'version' => $version()]), $actor);
            self::fail('esperava local obrigatório');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('lat', $e->errors);
        }
        $this->services->equipment()->update($id, $this->equipmentData(['lat' => '', 'lng' => '', 'street' => 'Rua Paraíba', 'cep' => '', 'version' => $version()]), $actor);
        $row = $this->services->equipmentRepository()->find($id);
        self::assertSame([-22.226, $this->fragata], [$row['lat'] ?? null, (int) ($row['region_id'] ?? 0)]);
    }
}
