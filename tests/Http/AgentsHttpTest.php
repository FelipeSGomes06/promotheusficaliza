<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Application\CepAddress;
use Fiscaliza\Domain\Time;
use Fiscaliza\Http\WebApp;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FakeCepLookup;
use Fiscaliza\Tests\Support\FakeGeocoder;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/** Cadastro de agente pelo app Slim real: consulta de CEP (com provedor falso), formulário só com mapa + CEP e ficha. */
final class AgentsHttpTest extends FirebirdTestCase
{
    private const BASE = '/promotheusfiscaliza';
    private ArraySession $session;
    private FakeCepLookup $cep;
    private FakeGeocoder $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
        $this->cep = new FakeCepLookup([
            '17501-000' => new CepAddress('17501-000', 'Avenida Sampaio Vidal', 'Centro', 'Marília', 'SP'),
            '17500-000' => new CepAddress('17500-000', '', '', 'Marília', 'SP'),
            '01001-000' => new CepAddress('01001-000', 'Praça da Sé', 'Sé', 'São Paulo', 'SP'),
        ]);
        $this->geo = new FakeGeocoder(['Avenida Sampaio Vidal' => ['lat' => '-22.213900', 'lng' => '-49.945800']]);
    }

    /** @param array<string, mixed> $body */
    private function request(string $method, string $path, array $body = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . self::BASE . $path, ['REMOTE_ADDR' => '203.0.113.11']);
        if ($body !== []) {
            $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        if ($this->session->isStarted()) {
            $request = $request->withCookieParams(['PFSESSID' => 'teste']);
        }

        return WebApp::create($this->services->withCepLookup($this->cep)->withGeocoder($this->geo), $this->session)->handle($request);
    }

    private function loginAs(string $role): void
    {
        $this->services->auth()->createUser('pessoa1', 'Pessoa ' . $role, $role, 'senha-de-teste-bem-longa');
        self::assertSame(303, $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => 'pessoa1', 'password' => 'senha-de-teste-bem-longa'])->getStatusCode());
    }

    /** @return array<string, mixed> */
    private static function json(ResponseInterface $response): array
    {
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testCepLookupNeedsAnAccountAndNeverReachesTheProviderWithoutOne(): void
    {
        $response = $this->request('GET', '/api/cep/17501000');

        self::assertSame(401, $response->getStatusCode(), 'API sem conta: 401, como /api/central');
        self::assertSame(['error', 'reference'], array_keys(self::json($response)));
        self::assertSame([], $this->cep->calls);
        self::assertStringStartsWith(self::BASE . '/entrar?voltar=', $this->request('GET', '/agentes/novo')->getHeaderLine('Location'), 'o formulário leva ao login');
    }

    public function testInvalidCepIsRefusedBeforeAnyCallToTheProvider(): void
    {
        $this->loginAs('OPERADOR');
        foreach (['1750100', '175010000', 'abcdefgh', '17501-00', '17.501-000', '%20'] as $bad) {
            $response = $this->request('GET', '/api/cep/' . $bad);
            self::assertSame(422, $response->getStatusCode(), $bad);
            self::assertSame(['cep' => '', 'situacao' => 'INVALIDO', 'rua' => '', 'bairro' => '', 'mensagem' => 'Digite os 8 números do CEP, como 17500-000.'], self::json($response), $bad);
        }
        self::assertSame([], $this->cep->calls, 'nenhuma chamada externa com CEP inválido');
    }

    public function testCepLookupAnswersWithStreetOrAFriendlyReasonToTypeIt(): void
    {
        $this->loginAs('OPERADOR');

        $found = $this->request('GET', '/api/cep/17501000');
        self::assertSame(200, $found->getStatusCode());
        self::assertSame(['cep' => '17501-000', 'situacao' => 'ENCONTRADO', 'rua' => 'Avenida Sampaio Vidal', 'bairro' => 'Centro', 'mensagem' => 'Rua encontrada pelo CEP.'], self::json($found));
        self::assertSame('no-store', $found->getHeaderLine('Cache-Control'));

        $general = self::json($this->request('GET', '/api/cep/17500-000'));
        self::assertSame(['17500-000', 'SEM_RUA', ''], [$general['cep'], $general['situacao'], $general['rua']]);

        $other = self::json($this->request('GET', '/api/cep/01001000'));
        self::assertSame(['OUTRA_CIDADE', '', ''], [$other['situacao'], $other['rua'], $other['bairro']], 'CEP de outra cidade não preenche a rua');
        self::assertStringContainsString('São Paulo/SP', $other['mensagem']);

        $missing = $this->request('GET', '/api/cep/99999999');
        self::assertSame(404, $missing->getStatusCode());
        self::assertSame('NAO_ENCONTRADO', self::json($missing)['situacao']);

        $this->cep->down = true;
        $down = $this->request('GET', '/api/cep/17501000');
        self::assertSame(503, $down->getStatusCode());
        $body = self::json($down);
        self::assertSame(['17501-000', 'INDISPONIVEL', ''], [$body['cep'], $body['situacao'], $body['rua']]);
        self::assertStringContainsString('Digite a rua', $body['mensagem']);
        self::assertStringNotContainsString('falso', (string) $down->getBody(), 'o detalhe do provedor não vaza');

        self::assertSame(['17501-000', '17500-000', '01001-000', '99999-999', '17501-000'], $this->cep->calls, 'só o CEP normalizado sai');
    }

    public function testLocatingByAddressGivesAPointWithoutTypingCoordinates(): void
    {
        self::assertSame(401, $this->request('GET', '/api/localizar?rua=Avenida%20Sampaio%20Vidal')->getStatusCode());
        $this->loginAs('OPERADOR');

        foreach (['', '?rua=', '?cep=1750', '?cep=17501000&rua=' . str_repeat('x', 121)] as $bad) {
            $response = $this->request('GET', '/api/localizar' . $bad);
            self::assertSame(422, $response->getStatusCode(), $bad);
            self::assertSame('INVALIDO', self::json($response)['situacao']);
        }
        self::assertSame([[], []], [$this->cep->calls, $this->geo->calls], 'nada sai com pedido inválido');

        $byCep = $this->request('GET', '/api/localizar?cep=17501-000');
        self::assertSame(200, $byCep->getStatusCode());
        self::assertSame(['situacao' => 'ENCONTRADO', 'lat' => '-22.213900', 'lng' => '-49.945800', 'rua' => 'Avenida Sampaio Vidal'], array_diff_key(self::json($byCep), ['mensagem' => 1]));
        self::assertSame('ENCONTRADO', self::json($this->request('GET', '/api/localizar?rua=Avenida%20Sampaio%20Vidal'))['situacao']);
        self::assertSame('SEM_RUA', self::json($this->request('GET', '/api/localizar?cep=17500000'))['situacao']);
        $missing = $this->request('GET', '/api/localizar?rua=Rua%20Inexistente');
        self::assertSame([404, 'NAO_ENCONTRADO', null], [$missing->getStatusCode(), self::json($missing)['situacao'], self::json($missing)['lat']]);
        $this->geo->down = true;
        $down = $this->request('GET', '/api/localizar?rua=Avenida%20Sampaio%20Vidal');
        self::assertSame([503, 'INDISPONIVEL'], [$down->getStatusCode(), self::json($down)['situacao']]);
        self::assertStringNotContainsString('falso', (string) $down->getBody());
    }

    /** Sem JavaScript não há mapa: com só o CEP (ou a rua), o servidor põe a base no endereço ao salvar. */
    public function testWithoutTheMapTheBaseGoesToTheAddressOnSave(): void
    {
        $this->loginAs('OPERADOR');
        $form = ['_csrf' => $this->session->csrfToken(), 'call_sign' => 'Hermes 32', 'role' => 'TRANSITO', 'agency_id' => (string) $this->agencyId,
            'status' => 'FORA_DE_SERVICO', 'base_name' => 'Base pelo endereço', 'base_lat' => '', 'base_lng' => ''];

        $response = $this->request('POST', '/agentes', $form + ['base_cep' => '17501000']);
        self::assertSame(303, $response->getStatusCode());
        $agent = $this->services->agentRepository()->find((int) substr($response->getHeaderLine('Location'), strlen(self::BASE . '/agentes/')));
        self::assertSame([-22.2139, -49.9458, 'Avenida Sampaio Vidal', '17501-000'], [$agent['base_lat'] ?? null, $agent['base_lng'] ?? null, $agent['base_street'] ?? null, $agent['base_cep'] ?? null]);

        $none = $this->request('POST', '/agentes', $form);
        self::assertSame(422, $none->getStatusCode());
        self::assertStringContainsString('Marque a base no mapa ou, sem o mapa, informe o CEP e a rua.', (string) $none->getBody());

        $general = (string) $this->request('POST', '/agentes', $form + ['base_cep' => '17500-000'])->getBody();
        self::assertStringContainsString('Para localizar pelo endereço, informe a rua', $general);
        self::assertMatchesRegularExpression('#name="base_street"[^>]*aria-invalid="true"#', $general, 'o erro fica no campo Rua');
    }

    public function testNewAgentFormLeadsWithTheMapAndAsksOnlyForPointAndCep(): void
    {
        $this->loginAs('OPERADOR');
        $html = (string) $this->request('GET', '/agentes/novo')->getBody();

        self::assertStringContainsString('Clique no mapa para marcar ou mudar a base.', $html);
        self::assertStringContainsString('data-empty="Clique no mapa para marcar a base."', $html);
        foreach (['base_name', 'base_lat', 'base_lng', 'base_cep', 'base_street'] as $field) {
            self::assertStringContainsString('name="' . $field . '"', $html, $field);
        }
        self::assertStringNotContainsString('name="base_ref"', $html, 'número ou referência saiu');
        self::assertStringNotContainsString('name="region_id"', $html, 'bairro não é escolhido');
        self::assertStringNotContainsString('class="advanced"', $html, 'latitude e longitude não ficam recolhidas');
        self::assertStringNotContainsString('Logradouro', $html);
        self::assertMatchesRegularExpression('#<label for="f-base_lat">Latitude <span class="req"#', $html);
        self::assertMatchesRegularExpression('#name="base_lat"[^>]*readonly#', $html, 'latitude e longitude nunca são digitadas, nem sem JavaScript');
        self::assertMatchesRegularExpression('#name="base_lng"[^>]*readonly#', $html);
        self::assertStringContainsString('data-mark-center', $html, 'pelo teclado, Enter marca o centro do mapa');
        self::assertStringContainsString('data-locate-url="' . self::BASE . '/api/localizar"', $html);
        self::assertStringContainsString('Não achou o lugar no mapa? Use o CEP e a rua logo abaixo.', $html);
        self::assertMatchesRegularExpression('#<div class="picker" id="picker" hidden data-picker#', $html, 'sem JavaScript não há mapa: a caixa vazia não aparece');
        self::assertStringContainsString('<p class="hint" data-without-map="picker">Sem o mapa (JavaScript desligado', $html);
        self::assertStringContainsString('data-url="' . self::BASE . '/api/cep/"', $html);
        self::assertStringContainsString('Marília / SP', $html);
        self::assertDoesNotMatchRegularExpression('#data-cep-manual[^>]*hidden#', $html, 'sem JavaScript, o campo Rua fica visível');
    }

    public function testRegisteringThroughTheFormNeedsOnlyNameAndPoint(): void
    {
        $this->loginAs('OPERADOR');
        $response = $this->request('POST', '/agentes', ['_csrf' => $this->session->csrfToken(), 'call_sign' => 'Hermes 31', 'role' => 'GUARDA',
            'agency_id' => (string) $this->agencyId, 'status' => 'FORA_DE_SERVICO', 'base_name' => 'Base Centro (fictícia)', 'base_lat' => '-22,2139', 'base_lng' => '-49,9458']);

        self::assertSame(303, $response->getStatusCode());
        $id = (int) substr($response->getHeaderLine('Location'), strlen(self::BASE . '/agentes/'));
        $agent = $this->services->agentRepository()->find($id);
        self::assertSame('Centro', $agent['region_name'] ?? null);
        $show = (string) $this->request('GET', "/agentes/{$id}")->getBody();
        self::assertStringContainsString('Rua não informada · Bairro: Centro · Marília/SP', $show);
        self::assertStringContainsString('Ponto fixo marcado no mapa.', $show);
    }

    public function testLegacyAgentOpensWithItsStreetAndSavesWithClearHistory(): void
    {
        $now = Time::toDb($this->clock->now());
        $id = $this->services->agentRepository()->insert([
            'code' => 'AGT-901', 'call_sign' => 'Hermes 91', 'role' => 'TRANSITO', 'agency_id' => $this->agencyId, 'status' => 'FORA_DE_SERVICO',
            'status_changed_at' => $now, 'base_name' => 'Base antiga', 'base_street' => 'Rua Antiga', 'base_ref' => 'Endereço de base ilustrativo',
            'base_cep' => '17501-000', 'region_id' => $this->regionId, 'base_lat' => '-22.214000', 'base_lng' => '-49.950500', 'created_at' => $now, 'updated_at' => $now,
        ]);
        // Alteração gravada antes desta mudança, com o nome técnico dos campos.
        $this->services->eventRepository()->append(['uid' => 'teste-alteracao-antiga', 'kind' => 'AGENTE', 'resource_id' => $id, 'type' => 'ALTERACAO', 'source' => 'MANUAL',
            'actor' => null, 'occurred_at' => $now, 'received_at' => $now, 'outcome' => 'APLICADO', 'summary' => 'Cadastro alterado: base_street, region_id, base_lat.',
            'payload' => ['alteracoes' => ['base_street' => ['antes' => 'X', 'depois' => 'Rua Antiga'], 'region_id' => ['antes' => null, 'depois' => $this->regionId], 'base_lat' => ['antes' => '-22.2', 'depois' => '-22.214']]]]);
        $this->loginAs('OPERADOR');

        $show = (string) $this->request('GET', "/agentes/{$id}")->getBody();
        self::assertStringContainsString('Rua: Rua Antiga, Endereço de base ilustrativo · Bairro: Centro · CEP 17501-000', $show);
        self::assertStringContainsString('Cadastro alterado: rua, bairro, ponto no mapa.', $show, 'alteração antiga com rótulos');
        self::assertStringNotContainsString('base_street', $show);

        $edit = $this->request('GET', "/agentes/{$id}/editar");
        self::assertSame(200, $edit->getStatusCode());
        $html = (string) $edit->getBody();
        self::assertStringContainsString('name="base_street" type="text" value="Rua Antiga"', $html, 'a edição traz a rua gravada');
        self::assertStringContainsString('value="17501-000"', $html);
        self::assertStringContainsString('Bairro: <strong>Centro</strong>', $html);
        self::assertStringContainsString('Número ou referência gravado antes: Endereço de base ilustrativo.', $html);
        preg_match('/name="version" value="(\d+)"/', $html, $v);

        $saved = $this->request('POST', "/agentes/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $v[1] ?? '', 'call_sign' => 'Hermes 91', 'role' => 'TRANSITO',
            'agency_id' => (string) $this->agencyId, 'base_name' => 'Base antiga', 'base_cep' => '17501-000', 'base_street' => 'Avenida Sampaio Vidal',
            'base_lat' => '-22.214000', 'base_lng' => '-49.950500']);
        self::assertSame(303, $saved->getStatusCode());
        $show = (string) $this->request('GET', "/agentes/{$id}")->getBody();
        self::assertStringContainsString('Rua: Avenida Sampaio Vidal · Bairro: Centro', $show, 'rua nova; a referência antiga saiu com ela');
        self::assertStringContainsString('Cadastro alterado: rua, número ou referência.', $show);
    }
}
