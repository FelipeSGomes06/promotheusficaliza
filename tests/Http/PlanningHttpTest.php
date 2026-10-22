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

    public function testHeatMapCarriesTheWholeGridWhileTheListKeepsOnlyThePriorities(): void
    {
        $this->services->urbanImporter()->import(
            self::ROOT . '/' . UrbanImporter::DEFAULT_RUN,
            self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS,
            self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST,
            self::ROOT . '/' . UrbanImporter::DEFAULT_GRID,
        );
        $this->loginAs('OPERADOR');
        $html = (string) $this->request('GET', '/planejar?quadrados=2&quadrado=2')->getBody();
        self::assertSame(1, preg_match('#<script type="application/json" id="plan-data">(.*?)</script>#s', $html, $m));
        $grid = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR)['grid'];

        $rows = $this->services->database()->all('SELECT RANK_NO, SECTOR_ID, EXPECTED FROM URBAN_CELL ORDER BY RANK_NO');
        self::assertCount(64, $rows);
        self::assertSame(
            array_map(static fn(array $r): array => [(int) $r['rank_no'], trim((string) $r['sector_id'])], $rows),
            array_map(static fn(array $c): array => [$c['rank'], $c['sector_id']], $grid['all']),
            'os 64 quadrados, um a um, na ordem de prioridade',
        );
        foreach ($rows as $i => $r) {
            self::assertEqualsWithDelta((float) $r['expected'], $grid['all'][$i]['expected'], 1e-12, "quadrado {$r['rank_no']}: mesmo valor de URBAN_CELL");
        }
        self::assertSame('Centro · Setor sudoeste: 3,8 sinistros esperados na semana', $grid['all'][0]['label'], 'tooltip no formato da lista');
        self::assertSame([1, 2], array_column($grid['cells'], 'rank'), 'clicáveis: só os prioritários pedidos');
        self::assertSame([false, true], array_column($grid['cells'], 'selected'));
        self::assertSame(2, substr_count($html, '<a class="sector-card cell-card'), 'a lista continua só com os prioritários');
        self::assertStringContainsString('Cor = sinistros esperados na semana, por quadrado de 2 km', $html);
        self::assertStringContainsString('aria-label="Mapa de calor da área em quadrados de 2 km', $html);
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

    public function testAManualChoiceWithoutJustificationShowsTheErrorAtTheFieldAndKeepsWhatWasTyped(): void
    {
        $runId = $this->import();
        $at = Time::toDb($this->clock->now());
        $near = $this->createAgent(['call_sign' => 'Hermes 10']);
        $this->services->agentRepository()->movePosition($near, '-22.250000', '-49.980000', $at, $at);
        $far = $this->createAgent(['call_sign' => 'Hermes 11']);
        $this->services->agentRepository()->movePosition($far, '-22.150000', '-49.870000', $at, $at);
        $this->loginAs('OPERADOR');

        $page = (string) $this->request('GET', '/planejar')->getBody();
        self::assertStringContainsString('name="suggested_agent_id" value="' . $near . '"', $page, 'a tela envia o sugerido que mostrou');
        self::assertStringContainsString('Por que escolheu este agente e não o sugerido?', $page);
        self::assertStringContainsString('Obrigatório só se você escolher outro agente.', $page);
        self::assertStringNotContainsString('Só é obrigatório nesse caso', $page, 'o texto não se contradiz');
        self::assertDoesNotMatchRegularExpression('#não o sugerido\?\s*<span class="optional">#', $page);

        $form = ['_csrf' => $this->session->csrfToken(), 'run_id' => (string) $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'suggested_agent_id' => (string) $near,
            'agent_id' => (string) $far, 'justification' => '', 'support_point' => 'Posto da Av. Sampaio Vidal', 'note' => 'saída da escola', 'confirm' => '1'];
        $refused = $this->request('POST', '/planejar/missoes', $form);
        $html = (string) $refused->getBody();
        self::assertSame(422, $refused->getStatusCode());
        self::assertStringContainsString('<span class="error" id="err-justification">Explique por que escolheu um agente diferente do sugerido.</span>', $html, 'erro junto do campo');
        self::assertStringContainsString('data-error-summary', $html);
        self::assertStringContainsString('value="Posto da Av. Sampaio Vidal"', $html, 'o que foi digitado volta');
        self::assertStringContainsString('>saída da escola</textarea>', $html);
        self::assertMatchesRegularExpression('#value="' . $far . '" checked#', $html, 'a escolha do agente volta marcada');
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_MISSION'));

        $ok = $this->request('POST', '/planejar/missoes', ['justification' => 'conhece o trecho'] + $form);
        self::assertSame(self::BASE . '/planejar#missoes', $ok->getHeaderLine('Location'));
        self::assertSame('MANUAL', rtrim((string) $this->services->database()->value('SELECT SELECTION FROM URBAN_MISSION')));
    }

    public function testReplayKeepsObservedCountsHiddenUntilRevealed(): void
    {
        $this->import();
        $this->loginAs('OPERADOR');
        $hidden = (string) $this->request('GET', '/planejar/retrospecto?semana=2025-01-06')->getBody();
        $revealed = (string) $this->request('GET', '/planejar/retrospecto?semana=2025-01-06&revelar=1')->getBody();

        self::assertStringContainsString('Histórico · teste de 2025 · não é a previsão atual', $hidden);
        self::assertStringContainsString('Revelar o que aconteceu', $hidden);
        self::assertSame(4, substr_count($hidden, '<b class="hint">Registrado: ?</b>'));
        self::assertStringContainsString('registros elegíveis</strong> na área de estudo', $revealed);
        self::assertStringNotContainsString('Registrado: ?', $revealed);
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
