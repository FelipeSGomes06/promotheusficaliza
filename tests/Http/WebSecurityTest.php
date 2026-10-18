<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Http\WebApp;
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
    private function request(string $method, string $path, array $body = [], array $headers = [], bool $withCookie = true): ResponseInterface
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
        $app = WebApp::create($this->services, $this->session);

        return $app->handle($request);
    }

    private function loginAs(string $role): void
    {
        $password = 'senha-de-teste-bem-longa';
        $this->services->auth()->createUser(strtolower($role) . '1', 'Pessoa ' . $role, $role, $password);
        $token = $this->session->csrfToken();
        $response = $this->request('POST', '/entrar', ['_csrf' => $token, 'username' => strtolower($role) . '1', 'password' => $password]);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testPublicReadHasSecurityHeadersAndNoCookie(): void
    {
        $this->createEquipment();
        $response = $this->request('GET', '/');

        self::assertSame(200, $response->getStatusCode());
        $csp = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        self::assertStringContainsString('img-src \'self\' data: https://tile.openstreetmap.org', $csp);
        self::assertStringNotContainsString('unsafe-inline', $csp);
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('', $response->getHeaderLine('Strict-Transport-Security'), 'HSTS é do domínio, não desta subpasta');
        self::assertFalse($this->session->isStarted(), 'visitante de leitura não recebe sessão');
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
}
