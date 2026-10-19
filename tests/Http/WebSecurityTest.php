<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Http\WebApp;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/** Fluxos HTTP completos pelo app Slim real (rotas, middlewares, templates) com o Firebird de testes. */
final class WebSecurityTest extends FirebirdTestCase
{
    private const BASE = '/promotheusfiscaliza';
    private ArraySession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    private function request(string $method, string $path, array $body = [], array $headers = [], bool $withCookie = true, ?Services $services = null): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . self::BASE . $path, ['REMOTE_ADDR' => '203.0.113.7']);
        if ($body !== []) {
            $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($withCookie && $this->session->isStarted()) {
            $request = $request->withCookieParams(['PFSESSID' => 'teste']);
        }
        $app = WebApp::create($services ?? $this->services, $this->session);

        return $app->handle($request);
    }

    private function loginAs(string $role): void
    {
        $password = 'senha-de-teste-bem-longa';
        $this->services->auth()->createUser(strtolower($role) . '1', 'Pessoa ' . $role, $role, $password);
        $this->login(strtolower($role) . '1', $password);
    }

    private function login(string $username, string $password): void
    {
        $response = $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => $username, 'password' => $password]);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testVisitorIsSentToLoginWithoutCookieAndWithSecurityHeaders(): void
    {
        $this->createEquipment();
        $response = $this->request('GET', '/');

        self::assertSame(302, $response->getStatusCode(), 'sem conta não há leitura pública');
        self::assertSame(self::BASE . '/entrar?voltar=' . rawurlencode(self::BASE . '/'), $response->getHeaderLine('Location'));
        self::assertSame('', (string) $response->getBody());
        $csp = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        self::assertStringContainsString('img-src \'self\' data: https://tile.openstreetmap.org', $csp);
        self::assertStringNotContainsString('unsafe-inline', $csp);
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('', $response->getHeaderLine('Strict-Transport-Security'), 'HSTS é do domínio, não desta subpasta');
        self::assertFalse($this->session->isStarted(), 'visitante levado ao login não recebe sessão');
    }

    /** Nenhuma tela nem dado sem conta: só login, logout e a verificação de saúde (sem dados) ficam abertos. */
    public function testEveryScreenAndTheApiRequireAnAccount(): void
    {
        $equipment = $this->createEquipment();
        $agent = $this->createAgent();
        foreach (['/', '/?visao=lista', '/equipamentos', "/equipamentos/{$equipment}", '/agentes', "/agentes/{$agent}", '/manutencao', '/antecipacao', '/sobre', '/contas'] as $path) {
            $response = $this->request('GET', $path);
            self::assertSame(302, $response->getStatusCode(), $path);
            self::assertStringStartsWith(self::BASE . '/entrar?voltar=', $response->getHeaderLine('Location'), $path);
            self::assertSame('', (string) $response->getBody(), $path);
        }
        $api = $this->request('GET', '/api/central');
        self::assertSame(401, $api->getStatusCode());
        self::assertSame(['error', 'reference'], array_keys(json_decode((string) $api->getBody(), true)), 'a API não devolve dados a visitante');

        self::assertSame(200, $this->request('GET', '/saude')->getStatusCode());
        $login = $this->request('GET', '/entrar');
        self::assertSame(200, $login->getStatusCode());
        self::assertStringContainsString('cadastrado pelo administrador', (string) $login->getBody());
        self::assertStringNotContainsString('href="' . self::BASE . '/equipamentos"', (string) $login->getBody(), 'visitante não vê o menu do sistema');
    }

    /** O administrador cadastra a pessoa; só então ela acessa; desativada pelo administrador, perde o acesso na hora. */
    public function testOnlyAccountsRegisteredByTheAdminGetIn(): void
    {
        $this->createEquipment();
        $this->loginAs('ADMIN');
        $admin = $this->session;
        $created = $this->request('POST', '/contas', ['_csrf' => $admin->csrfToken(), 'username' => 'carla', 'display_name' => 'Carla', 'role' => 'OPERADOR']);
        self::assertSame(201, $created->getStatusCode());
        self::assertSame(1, preg_match('#<code>([A-Za-z0-9_-]+)</code>#', (string) $created->getBody(), $m), 'senha gerada mostrada ao administrador');

        $this->session = new ArraySession();
        self::assertSame(302, $this->request('GET', '/')->getStatusCode(), 'antes do login, nada');
        $this->login('carla', $m[1]);
        $page = $this->request('GET', '/');
        self::assertSame(200, $page->getStatusCode());
        self::assertStringContainsString("script-src 'self'", $page->getHeaderLine('Content-Security-Policy'));
        self::assertStringContainsString('href="' . self::BASE . '/equipamentos"', (string) $page->getBody(), 'com conta, o menu aparece');
        self::assertSame(200, $this->request('GET', '/api/central')->getStatusCode());

        $carla = $this->session;
        $this->session = $admin;
        $id = (int) $this->services->userRepository()->findByUsername('carla')['id'];
        self::assertSame(303, $this->request('POST', "/contas/{$id}/situacao", ['_csrf' => $admin->csrfToken(), 'active' => '0'])->getStatusCode());

        $this->session = $carla;
        self::assertSame(401, $this->request('GET', '/api/central')->getStatusCode(), 'a atualização da visão geral para de receber dados');
        self::assertSame(302, $this->request('GET', '/equipamentos')->getStatusCode());
    }

    public function testLogoutReturnsToTheLoginPage(): void
    {
        $this->loginAs('OPERADOR');
        $response = $this->request('POST', '/sair', ['_csrf' => $this->session->csrfToken()]);

        self::assertSame(self::BASE . '/entrar', $response->getHeaderLine('Location'));
        self::assertSame(302, $this->request('GET', '/')->getStatusCode());
    }

    /** Banco fora com sessão aberta: mostra a indisponibilidade, em vez de mandar a pessoa ao login sem motivo. */
    public function testSignedInUserSeesUnavailabilityWhenTheAccountCannotBeChecked(): void
    {
        $this->loginAs('OPERADOR');
        $broken = new Services(self::config()->with(['DB_PASSWORD' => 'senha-errada-de-proposito']), self::ROOT, null, $this->clock);

        self::assertSame(503, $this->request('GET', '/equipamentos', services: $broken)->getStatusCode());
        self::assertSame(503, $this->request('GET', '/api/central', services: $broken)->getStatusCode());
    }

    public function testWritesRequireCsrfAndAuthentication(): void
    {
        self::assertSame(403, $this->request('POST', '/equipamentos', ['manufacturer' => 'x'])->getStatusCode(), 'sem token CSRF');

        $token = $this->session->csrfToken();
        self::assertSame(403, $this->request('POST', '/equipamentos', ['_csrf' => 'errado'])->getStatusCode());
        self::assertSame(401, $this->request('POST', '/equipamentos', ['_csrf' => $token] + $this->equipmentData())->getStatusCode(), 'token válido, mas visitante');
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM EQUIPMENT'));

        $redirect = $this->request('GET', '/equipamentos/novo');
        self::assertSame(302, $redirect->getStatusCode());
        self::assertSame(self::BASE . '/entrar?voltar=' . rawurlencode(self::BASE . '/equipamentos/novo'), $redirect->getHeaderLine('Location'));
    }

    public function testCrossOriginPostIsRejectedEvenWithToken(): void
    {
        $this->loginAs('OPERADOR');
        $response = $this->request('POST', '/equipamentos', ['_csrf' => $this->session->csrfToken()] + $this->equipmentData(), ['Origin' => 'https://atacante.example']);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testOperatorCreatesEquipmentAndCannotManageAccounts(): void
    {
        $this->loginAs('OPERADOR');
        self::assertSame(1, $this->session->regenerations, 'login gera novo identificador de sessão');

        $response = $this->request('POST', '/equipamentos', ['_csrf' => $this->session->csrfToken()] + $this->equipmentData(['model' => '<img src=x onerror=alert(1)>']));
        self::assertSame(303, $response->getStatusCode());
        self::assertStringStartsWith(self::BASE . '/equipamentos/', $response->getHeaderLine('Location'));

        self::assertSame(403, $this->request('GET', '/contas')->getStatusCode());
        self::assertSame(403, $this->request('POST', '/contas', ['_csrf' => $this->session->csrfToken(), 'username' => 'intruso', 'display_name' => 'x', 'role' => 'ADMIN'])->getStatusCode());
        self::assertNull($this->services->userRepository()->findByUsername('intruso'));
    }

    public function testUntrustedTextIsEscapedInHtmlAndJson(): void
    {
        $id = $this->createEquipment(['model' => '<img src=x onerror=alert(1)>', 'address_ref' => '"><script>alert(2)</script>']);
        $this->loginAs('OPERADOR');

        $html = (string) $this->request('GET', "/equipamentos/{$id}")->getBody();
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringNotContainsString('<script>alert(2)', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);

        $central = (string) $this->request('GET', '/')->getBody();
        self::assertStringNotContainsString('<img src=x', $central, 'JSON embutido na página usa JSON_HEX_TAG');

        $json = (string) $this->request('GET', '/api/central')->getBody();
        self::assertStringNotContainsString('<img', $json);
        self::assertStringEndsWith('<img src=x onerror=alert(1)>', json_decode($json, true)['resources'][0]['subtitle'], 'o dado volta íntegro após decodificar');
    }

    public function testLoginFailuresAreGenericThrottledAndRedirectStaysInternal(): void
    {
        $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', 'senha-de-teste-bem-longa');
        $token = $this->session->csrfToken();
        $unknown = (string) $this->request('POST', '/entrar', ['_csrf' => $token, 'username' => 'nao-existe', 'password' => 'x'])->getBody();
        $wrong = (string) $this->request('POST', '/entrar', ['_csrf' => $token, 'username' => 'ana', 'password' => 'x'])->getBody();
        self::assertStringContainsString('Usuário ou senha inválidos.', $unknown);
        self::assertStringContainsString('Usuário ou senha inválidos.', $wrong);

        foreach (range(1, 4) as $_) {
            $this->request('POST', '/entrar', ['_csrf' => $token, 'username' => 'ana', 'password' => 'errada']);
        }
        $blocked = $this->request('POST', '/entrar', ['_csrf' => $token, 'username' => 'ana', 'password' => 'senha-de-teste-bem-longa']);
        self::assertSame(401, $blocked->getStatusCode());
        self::assertStringContainsString('Muitas tentativas', (string) $blocked->getBody());

        $this->services->auth()->createUser('bia', 'Bia', 'OPERADOR', 'senha-de-teste-bem-longa');
        $ok = $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => 'bia', 'password' => 'senha-de-teste-bem-longa', 'voltar' => '//atacante.example/x']);
        self::assertSame(self::BASE . '/', $ok->getHeaderLine('Location'), 'destino externo é descartado');
    }

    public function testMalformedQueryParametersDegradeSafely(): void
    {
        $this->createEquipment();
        $this->loginAs('OPERADOR');
        self::assertSame(200, $this->request('GET', '/equipamentos?ordem=CODE;DROP%20TABLE%20X&pagina=-5&categoria[]=%27OR%201=1')->getStatusCode());
        $api = $this->request('GET', '/api/central?bairro=abc&situacao[]=<x>');
        self::assertSame(200, $api->getStatusCode());
        self::assertSame(['categoria' => [], 'situacao' => [], 'bairro' => [], 'orgao' => [], 'q' => '', 'atencao' => ''], json_decode((string) $api->getBody(), true)['filters']);

        $missing = $this->request('GET', '/equipamentos/999999');
        self::assertSame(404, $missing->getStatusCode());
        self::assertStringNotContainsString('SQLSTATE', (string) $missing->getBody());
        self::assertSame(404, $this->request('GET', '/equipamentos/abc')->getStatusCode());
    }

    /** OP-03: o conflito mantém o que a pessoa digitou, mostra o que mudou e permite salvar conscientemente. */
    public function testEditConflictKeepsTypingShowsDifferencesAndCarriesTheCurrentVersion(): void
    {
        $id = $this->createEquipment();
        $old = (string) $this->services->equipmentRepository()->find($id)['version_no'];
        $ana = $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
        $this->services->equipment()->update($id, $this->equipmentData(['model' => 'Versão de Ana', 'version' => $old]), $ana);
        $this->services->ingestor()->position('conflito-pos', 'EQUIPAMENTO', $id, '-22.2', '-49.9', $this->clock->now()->modify('+1 minute'), false);
        $current = (string) $this->services->equipmentRepository()->find($id)['version_no'];
        $this->loginAs('OPERADOR');

        $conflict = $this->request('POST', "/equipamentos/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $old] + $this->equipmentData(['model' => 'Minha versão']));
        $html = (string) $conflict->getBody();
        self::assertSame(409, $conflict->getStatusCode());
        self::assertStringContainsString('value="Minha versão"', $html, 'a digitação é mantida');
        self::assertStringContainsString('Versão de Ana', $html, 'o valor salvo agora aparece para comparação');
        self::assertStringContainsString('name="version" value="' . $current . '"', $html, 'o formulário passa a carregar a versão atual');

        $saved = $this->request('POST', "/equipamentos/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $current] + $this->equipmentData(['model' => 'Minha versão']));
        self::assertSame(303, $saved->getStatusCode());
        self::assertSame('Minha versão', $this->services->equipmentRepository()->find($id)['model']);
    }

    public function testAcknowledgeAlertViaJsonUsesHeaderToken(): void
    {
        $this->createEquipment(['status' => 'FURTADO']);
        $alert = $this->services->alertRepository()->unresolved()[0];
        $this->loginAs('OPERADOR');
        $response = $this->request('POST', "/alertas/{$alert['id']}/reconhecer", [], ['X-CSRF-Token' => $this->session->csrfToken(), 'Accept' => 'application/json']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('RECONHECIDO', $this->services->alertRepository()->find((int) $alert['id'])['state']);
    }

    public function testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode(): void
    {
        $this->createEquipment();
        // O login (única tela sem conta) também usa só o build local, sem código inline.
        $login = (string) $this->request('GET', '/entrar')->getBody();
        self::assertMatchesRegularExpression('#<script type="module" src="/promotheusfiscaliza/assets/build/app-[A-Za-z0-9_-]+\.js"></script>#', $login);
        self::assertDoesNotMatchRegularExpression('#<script\b(?![^>]*\bsrc=)#', $login);
        self::assertDoesNotMatchRegularExpression('#\sstyle="#', $login);

        $this->loginAs('OPERADOR');
        $html = (string) $this->request('GET', '/')->getBody();

        self::assertMatchesRegularExpression('#<link rel="stylesheet" href="/promotheusfiscaliza/assets/build/styles-[A-Za-z0-9_-]+\.css">#', $html);
        self::assertMatchesRegularExpression('#<script type="module" src="/promotheusfiscaliza/assets/build/app-[A-Za-z0-9_-]+\.js"></script>#', $html);
        // A CSP não tem unsafe-inline: nenhum script inline executável e nenhum atributo style no HTML.
        preg_match_all('#<script\b(?![^>]*\bsrc=)([^>]*)>#', $html, $inline);
        self::assertSame([' type="application/json" id="snapshot-data"'], $inline[1], 'só o instantâneo JSON (não executável) fica inline');
        self::assertDoesNotMatchRegularExpression('#\sstyle="#', $html);
        self::assertDoesNotMatchRegularExpression('#\son[a-z]+="#', $html, 'sem manipuladores de evento inline');
        self::assertStringNotContainsString('unsafe-eval', $this->request('GET', '/')->getHeaderLine('Content-Security-Policy'));

        // O cartão de autoria da página Sobre é um link comum (funciona sem JavaScript); o movimento fica no módulo.
        $about = (string) $this->request('GET', '/sobre')->getBody();
        self::assertStringContainsString('<a class="author-card" href="https://devfelipegomes.com.br/" rel="author"', $about);
        self::assertDoesNotMatchRegularExpression('#\sstyle="#', $about);
        self::assertDoesNotMatchRegularExpression('#\son[a-z]+="#', $about);
    }

    public function testSimulateFailureViaJsonReportsTheServerOutcome(): void
    {
        $id = $this->createEquipment();
        $this->loginAs('OPERADOR');
        $headers = ['X-CSRF-Token' => $this->session->csrfToken(), 'Accept' => 'application/json'];

        $first = $this->request('POST', "/equipamentos/{$id}/simular-falha", [], $headers);
        self::assertSame(200, $first->getStatusCode());
        self::assertTrue(json_decode((string) $first->getBody(), true)['ok']);
        self::assertSame('FALHA', $this->services->equipmentRepository()->find($id)['status']);

        // Já em falha: o evento vai só para o histórico e a resposta diz que nada mudou (sem "sucesso" falso).
        $second = $this->request('POST', "/equipamentos/{$id}/simular-falha", [], $headers);
        self::assertSame(409, $second->getStatusCode());
        self::assertFalse(json_decode((string) $second->getBody(), true)['ok']);
    }
}
