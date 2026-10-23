<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Application\CepAddress;
use Fiscaliza\Tests\Support\FakeCepLookup;
use Fiscaliza\Tests\Support\FakeGeocoder;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Base do agente: ponto no mapa obrigatório, rua opcional (vinda do CEP), bairro derivado do ponto, dados antigos. */
final class AgentBaseTest extends FirebirdTestCase
{
    private int $fragata;
    private int $palmital;

    protected function setUp(): void
    {
        parent::setUp();
        $refs = $this->services->referenceRepository();
        $this->fragata = $refs->insertRegion('Fragata', 'SUL', '-22.226000', '-49.930000');
        $this->palmital = $refs->insertRegion('Palmital', 'NORTE', '-22.200000', '-49.970000');
    }

    /** @return array<string, string> só o que o formulário exige */
    private function minimal(array $override = []): array
    {
        return $override + ['call_sign' => 'Hermes 21', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId, 'status' => 'EM_ATIVIDADE',
            'base_name' => 'Base Sul (fictícia)', 'base_lat' => '-22.2250', 'base_lng' => '-49.9330'];
    }

    private function userId(): int
    {
        return (int) $this->services->auth()->createUser('operador' . random_int(1, 99999), 'Operador', 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
    }

    public function testRegistersWithOnlyNameRoleAgencyStatusBaseNameAndPoint(): void
    {
        $agent = $this->services->agentRepository()->find($this->services->agents()->create($this->minimal(), null));

        self::assertNotNull($agent);
        self::assertSame(-22.225, $agent['base_lat']);
        self::assertNull($agent['base_street'], 'rua opcional');
        self::assertNull($agent['base_cep']);
        self::assertNull($agent['base_ref'], 'número opcional');
        self::assertSame($this->fragata, (int) $agent['region_id'], 'bairro do centro mais próximo do ponto');
        self::assertSame('Fragata', $agent['region_name']);
    }

    public function testOnlyNameRoleAgencyBaseNameAndPointAreRequired(): void
    {
        try {
            $this->services->agents()->create(['status' => 'EM_ATIVIDADE'], null);
            self::fail('esperava erros de validação');
        } catch (ValidationFailed $e) {
            self::assertEqualsCanonicalizing(['call_sign', 'role', 'agency_id', 'base_name', 'base_lat'], array_keys($e->errors));
            self::assertSame('Marque a base no mapa ou, sem o mapa, informe o CEP e a rua.', $e->errors['base_lat']);
        }
    }

    public function testStreetComesFromTheFormAndRegionOrReferenceSentByTheClientAreIgnored(): void
    {
        $id = $this->services->agents()->create($this->minimal([
            'base_cep' => '17501000', 'base_street' => 'Avenida Sampaio Vidal', 'region_id' => (string) $this->palmital, 'base_ref' => 'nº 10',
        ]), null);
        $agent = $this->services->agentRepository()->find($id);

        self::assertSame('17501-000', $agent['base_cep'] ?? null);
        self::assertSame('Avenida Sampaio Vidal', $agent['base_street'] ?? null);
        self::assertSame($this->fragata, (int) ($agent['region_id'] ?? 0), 'o bairro vem do ponto, não do que o cliente enviou');
        self::assertSame('nº 10', $agent['base_ref'] ?? null, 'número informado é gravado');

        $this->expectException(ValidationFailed::class);
        $this->services->agents()->create($this->minimal(['base_cep' => '1750-100']), null);
    }

    /** Sem o ponto do mapa, a base vai para o endereço (rua informada ou a do CEP); latitude e longitude não são digitadas. */
    public function testWithoutThePointTheBaseGoesToTheAddress(): void
    {
        $geo = new FakeGeocoder(['Rua Paraíba' => ['lat' => '-22.226000', 'lng' => '-49.930000'], 'Avenida Sampaio Vidal' => ['lat' => '-22.213900', 'lng' => '-49.945800']]);
        $cep = new FakeCepLookup(['17501-000' => new CepAddress('17501-000', 'Avenida Sampaio Vidal', 'Centro', 'Marília', 'SP')]);
        $agents = $this->services->withCepLookup($cep)->withGeocoder($geo)->agents();
        $noPoint = $this->minimal(['base_lat' => '', 'base_lng' => '']);

        $agent = $this->services->agentRepository()->find($agents->create($noPoint + ['base_street' => 'Rua Paraíba'], null));
        self::assertSame([-22.226, -49.93, 'Rua Paraíba', $this->fragata], [$agent['base_lat'] ?? null, $agent['base_lng'] ?? null, $agent['base_street'] ?? null, (int) ($agent['region_id'] ?? 0)]);

        $agent = $this->services->agentRepository()->find($agents->create($noPoint + ['base_cep' => '17501000'], null));
        self::assertSame([-22.2139, 'Avenida Sampaio Vidal', $this->regionId], [$agent['base_lat'] ?? null, $agent['base_street'] ?? null, (int) ($agent['region_id'] ?? 0)], 'a rua do CEP passa a ser a rua gravada');

        $agents->create($this->minimal(['base_street' => 'Rua Paraíba']), null);
        self::assertSame(['Rua Paraíba', 'Avenida Sampaio Vidal'], $geo->calls, 'com o ponto do mapa, nada é localizado');

        foreach ([[['base_street' => 'Rua Inexistente'], 'base_street', 'Não achamos esta rua'], [['call_sign' => '', 'base_street' => 'Rua Paraíba'], 'call_sign', null]] as [$data, $field, $text]) {
            try {
                $agents->create($data + $noPoint, null);
                self::fail('esperava erro em ' . $field);
            } catch (ValidationFailed $e) {
                self::assertArrayHasKey($field, $e->errors);
                if ($text !== null) {
                    self::assertStringContainsString($text, $e->errors[$field]);
                }
            }
        }
        self::assertSame(['Rua Paraíba', 'Avenida Sampaio Vidal', 'Rua Inexistente'], $geo->calls, 'com outro campo inválido, não há consulta externa');

        $geo->down = true;
        try {
            $agents->create($noPoint + ['base_street' => 'Rua Paraíba'], null);
            self::fail('esperava indisponibilidade');
        } catch (ValidationFailed $e) {
            self::assertStringContainsString('não respondeu agora', $e->errors['base_lat'] ?? '');
        }
    }

    /** Cadastro antigo (bairro escolhido à mão, logradouro e número digitados): abre, salva sem mudar e só muda o que mudou. */
    public function testLegacyAgentKeepsChosenRegionUntilThePointChanges(): void
    {
        $now = Time::toDb($this->clock->now());
        $id = $this->services->agentRepository()->insert([
            'code' => 'AGT-900', 'call_sign' => 'Hermes 90', 'role' => 'TRANSITO', 'agency_id' => $this->agencyId, 'status' => 'FORA_DE_SERVICO',
            'status_changed_at' => $now, 'base_name' => 'Base antiga', 'base_street' => 'Rua Antiga', 'base_ref' => 'nº 10', 'base_cep' => '17500-000',
            'region_id' => $this->palmital, 'base_lat' => '-22.225000', 'base_lng' => '-49.933000', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $actor = $this->userId();
        $form = fn(array $override = []): array => $override + ['call_sign' => 'Hermes 90', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId,
            'base_name' => 'Base antiga', 'base_street' => 'Rua Antiga', 'base_ref' => 'nº 10', 'base_cep' => '17500-000', 'base_lat' => '-22.2250', 'base_lng' => '-49.9330',
            'version' => (string) $this->services->agentRepository()->find($id)['version_no']];
        $changes = fn(): array => array_values(array_filter($this->services->eventRepository()->forResource('AGENTE', $id), static fn(array $e): bool => $e['event_type'] === 'ALTERACAO'));

        $this->services->agents()->update($id, $form(), $actor);
        $agent = $this->services->agentRepository()->find($id);
        self::assertSame($this->palmital, (int) $agent['region_id'], 'ponto igual: fica o bairro escolhido antes, mesmo não sendo o mais próximo');
        self::assertSame('nº 10', $agent['base_ref']);
        self::assertSame([], $changes(), 'salvar sem mudar nada não cria alteração');

        $this->services->agents()->update($id, $form(['base_street' => 'Rua Nova']), $actor);
        $agent = $this->services->agentRepository()->find($id);
        self::assertSame('nº 10', $agent['base_ref'], 'o número fica enquanto a pessoa não o muda');
        self::assertSame($this->palmital, (int) $agent['region_id']);
        self::assertSame('Cadastro alterado: rua.', $changes()[0]['summary']);

        $this->services->agents()->update($id, $form(['base_street' => 'Rua Nova', 'base_lat' => '-22.2010', 'base_lng' => '-49.9690']), $actor);
        self::assertSame($this->palmital, (int) $this->services->agentRepository()->find($id)['region_id'], 'novo ponto perto do Palmital');
        self::assertSame('Cadastro alterado: ponto no mapa.', $changes()[0]['summary'], 'bairro igual não entra');

        $this->services->agents()->update($id, $form(['base_street' => 'Rua Nova', 'base_lat' => '-22.2139', 'base_lng' => '-49.9458']), $actor);
        self::assertSame($this->regionId, (int) $this->services->agentRepository()->find($id)['region_id'], 'ponto no Centro: bairro Centro');
        self::assertSame('Cadastro alterado: ponto no mapa, bairro.', $changes()[0]['summary']);
    }

    public function testLegacyAgentWithoutRegionGetsOneOnTheNextSave(): void
    {
        $id = $this->createAgent();
        $this->services->agentRepository()->updateState($id, ['region_id' => null]);
        $version = (string) $this->services->agentRepository()->find($id)['version_no'];

        $this->services->agents()->update($id, ['call_sign' => 'Hermes 99', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId, 'base_name' => 'Base de teste',
            'base_lat' => '-22.2165', 'base_lng' => '-49.9490', 'version' => $version], $this->userId());

        self::assertSame($this->regionId, (int) $this->services->agentRepository()->find($id)['region_id']);
    }
}
