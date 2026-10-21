<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Domain\Time;
use Fiscaliza\Http\WebApp;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/** Planejar reforço pelo app Slim real: estado vazio, previsão, troca de método, confirmação com CSRF e retrospecto. */
final class PlanningHttpTest extends FirebirdTestCase
{
    private const BASE = '/promotheusfiscaliza';
    private ArraySession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
    }

    public function testWithoutAnImportedRunThePageSaysSoAndShowsNoNumbers(): void
    {
        $this->loginAs('OPERADOR');
        $html = (string) $this->request('GET', '/planejar')->getBody();

        self::assertStringContainsString('Ainda não há previsão', $html);
        self::assertStringContainsString('php bin/fiscaliza urban:import', $html);
        self::assertStringNotContainsString('esperados na semana', $html);
        self::assertStringContainsString('Nenhuma execução com retrospecto', (string) $this->request('GET', '/planejar/retrospecto')->getBody());
    }

    public function testPageShowsPeriodReferenceMethodOriginsAndOnlyTheJsonDataInline(): void
    {
        $this->import();
        $this->loginAs('OPERADOR');
        $html = (string) $this->request('GET', '/planejar')->getBody();

        self::assertStringContainsString('20/10/2026 a 26/10/2026', $html);
        self::assertStringContainsString('31/07/2026', $html, 'data de referência dos dados');
        self::assertStringContainsString('média do último ano', $html);
        self::assertStringNotContainsString('Poisson', $html, 'a tela mostra só a média simples');
        self::assertStringContainsString('Real agregado · Infosiga-SP', $html);
        self::assertStringContainsString('Fictício', $html);
        self::assertStringContainsString('Não é a chance de um acidente', $html);
        self::assertStringContainsString('Reforce primeiro: <strong>Setor sudoeste</strong>', $html, 'sem quadrados, o alvo é o setor de maior estimativa');
        self::assertStringContainsString('(297 + 1) ÷ 53 = 5,6', $html, 'estimativa arredondada a uma casa, com a conta');
        self::assertStringContainsString('href="' . self::BASE . '/planejar"', $html, 'item "Planejar reforço" no menu');
        preg_match_all('#<script\b(?![^>]*\bsrc=)([^>]*)>#', $html, $inline);
        self::assertSame([' type="application/json" id="plan-data"'], $inline[1]);
        self::assertDoesNotMatchRegularExpression('#\sstyle="#', $html);
        self::assertStringNotContainsString('Tudo aqui é fictício', $html, 'o aviso global reconhece a camada real');

        self::assertStringContainsString('Os quadrados de 2 km ainda não foram calculados', $html);
        $this->services->urbanImporter()->import(
            self::ROOT . '/' . UrbanImporter::DEFAULT_RUN,
            self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS,
            self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST,
            self::ROOT . '/' . UrbanImporter::DEFAULT_GRID,
        );
        $four = (string) $this->request('GET', '/planejar')->getBody();
        $two = (string) $this->request('GET', '/planejar?quadrados=2')->getBody();
        self::assertSame(4, substr_count($four, '<a class="sector-card cell-card'), 'padrão: 4 quadrados');
        self::assertSame(2, substr_count($two, '<a class="sector-card cell-card'));
        self::assertStringContainsString('Setor sudoeste · 26% dos registros do último ano', $two, 'o 1º quadrado é o do centro, no setor sudoeste');
        self::assertSame(4, substr_count((string) $this->request('GET', '/planejar?quadrados=9')->getBody(), '<a class="sector-card cell-card'), 'fora de 1–4 volta ao padrão');

        self::assertStringContainsString('Reforce primeiro: <strong>Centro</strong>', $four, 'o resumo diz onde, em palavras');
        $second = (string) $this->request('GET', '/planejar?quadrado=2')->getBody();
        self::assertStringContainsString('Quem enviar ao quadrado 2', $second, 'escolher um quadrado leva ao passo 2 dele');
        self::assertStringContainsString('Quadrado 2, escolhido.', $second);
        self::assertStringContainsString('<strong>setor noroeste</strong>', $second, 'a explicação é do setor do quadrado escolhido');
    }

    public function testConfirmingAMissionNeedsCsrfAnAccountAndAnEligibleAgent(): void
    {
        $runId = $this->import();
        $agent = $this->createAgent(['call_sign' => 'Hermes 10']);
        $at = Time::toDb($this->clock->now());
        $this->services->agentRepository()->movePosition($agent, '-22.250000', '-49.980000', $at, $at);
        $guard = $this->createAgent(['call_sign' => 'Atena 10', 'role' => 'GUARDA']);
        $form = ['run_id' => (string) $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => (string) $agent, 'confirm' => '1'];

        self::assertSame(403, $this->request('POST', '/planejar/missoes', $form)->getStatusCode(), 'sem token CSRF');
        self::assertSame(401, $this->request('POST', '/planejar/missoes', ['_csrf' => $this->session->csrfToken()] + $form)->getStatusCode(), 'visitante');
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_MISSION'));

        $user = $this->loginAs('OPERADOR');
        $forged = $this->request('POST', '/planejar/missoes', ['_csrf' => $this->session->csrfToken(), 'agent_id' => (string) $guard] + $form);
        self::assertSame(303, $forged->getStatusCode());
        self::assertStringContainsString('Só agentes de trânsito', (string) $this->request('GET', '/planejar')->getBody());
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_MISSION'), 'agente de outro papel recusado no servidor');

        $ok = $this->request('POST', '/planejar/missoes', ['_csrf' => $this->session->csrfToken(), 'note' => '<b>reforço</b> na saída da escola'] + $form);
        self::assertSame(self::BASE . '/planejar#missoes', $ok->getHeaderLine('Location'));
        $mission = $this->services->database()->one('SELECT CONFIRMED_BY, STATE, AGENT_ID FROM URBAN_MISSION');
        self::assertSame([$user, 'CONFIRMADA', $agent], [(int) $mission['confirmed_by'], rtrim($mission['state']), (int) $mission['agent_id']], 'autoria vem da sessão');
        $page = (string) $this->request('GET', '/planejar')->getBody();
        self::assertStringContainsString('Missão fictícia #', $page);
        self::assertStringContainsString('Hermes 10 → Setor sudoeste', $page, 'missão sem quadrado mostra o setor');
        self::assertStringContainsString('&lt;b&gt;reforço&lt;/b&gt;', $page, 'observação escapada');

        $id = (int) $this->services->database()->value('SELECT ID FROM URBAN_MISSION');
        self::assertSame(303, $this->request('POST', "/planejar/missoes/{$id}/concluir", ['_csrf' => $this->session->csrfToken(), 'reason' => 'ronda feita'])->getStatusCode());
        self::assertSame('CONCLUIDA', rtrim((string) $this->services->database()->value('SELECT STATE FROM URBAN_MISSION WHERE ID = ?', [$id])));
    }

    public function testReplayKeepsObservedCountsHiddenUntilRevealed(): void
    {
        $this->import();
        $this->loginAs('OPERADOR');
        $hidden = (string) $this->request('GET', '/planejar/retrospecto?semana=2025-01-06')->getBody();
        $revealed = (string) $this->request('GET', '/planejar/retrospecto?semana=2025-01-06&revelar=1')->getBody();

        self::assertStringContainsString('Histórico · teste de 2025 · não é a previsão atual', $hidden);
        self::assertStringContainsString('Revelar o que foi registrado', $hidden);
        self::assertSame(4, substr_count($hidden, '<span class="hint">oculto</span>'));
        self::assertStringContainsString('registros elegíveis</strong> na área de estudo', $revealed);
        self::assertStringNotContainsString('<span class="hint">oculto</span>', $revealed);
        self::assertStringNotContainsString('Poisson', $revealed);
        self::assertStringContainsString('233 de 626 · 37,2%', $revealed);
        self::assertStringContainsString('<option value="2025-12-22"', $hidden, 'todas as semanas do teste navegáveis');
    }

    private function import(): int
    {
        return $this->services->urbanImporter()->import(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST)['id'];
    }

    /** @param array<string, mixed> $body */
    private function request(string $method, string $path, array $body = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . self::BASE . $path, ['REMOTE_ADDR' => '203.0.113.9']);
        $query = parse_url($path, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            $request = $request->withQueryParams($params);
        }
        if ($body !== []) {
            $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        if ($this->session->isStarted()) {
            $request = $request->withCookieParams(['PFSESSID' => 'teste']);
        }

        return WebApp::create($this->services, $this->session)->handle($request);
    }

    private function loginAs(string $role): int
    {
        $password = 'senha-de-teste-bem-longa';
        $id = $this->services->auth()->createUser(strtolower($role) . '1', 'Pessoa ' . $role, $role, $password)['id'];
        self::assertSame(303, $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => strtolower($role) . '1', 'password' => $password])->getStatusCode());

        return $id;
    }
}
