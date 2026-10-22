<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Application\FineService;
use Fiscaliza\Http\WebApp;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/** Aba Multas pelo app Slim real: acesso, CSRF, formulários sem JavaScript, erros, escape e prefixo de URL. */
final class FinesHttpTest extends FirebirdTestCase
{
    private const BASE = '/promotheusfiscaliza';
    private ArraySession $session;
    private int $radar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
        $this->radar = $this->createEquipment(['type_code' => 'RADAR', 'specs' => ['functions' => ['VELOCIDADE', 'AVANCO_SEMAFORO'], 'speed_limit_kmh' => '50', 'lanes' => '2']]);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    private function request(string $method, string $path, array $body = [], array $headers = [], string $base = self::BASE): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . $base . $path, ['REMOTE_ADDR' => '203.0.113.9']);
        if ($body !== []) {
            $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($this->session->isStarted()) {
            $request = $request->withCookieParams(['PFSESSID' => 'teste']);
        }
        $services = $base === self::BASE ? $this->services : new Services(self::config()->with(['APP_BASE_PATH' => $base]), self::ROOT, null, $this->clock);

        return WebApp::create($services, $this->session)->handle($request);
    }

    private function loginAs(string $role, string $username = 'pessoa1'): int
    {
        $created = $this->services->auth()->createUser($username, 'Pessoa ' . $role, $role, 'senha-de-teste-bem-longa');
        $response = $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => $username, 'password' => 'senha-de-teste-bem-longa']);
        self::assertSame(303, $response->getStatusCode());

        return (int) $created['id'];
    }

    /** @return array{0: string, 1: string} versão e chave de comando do formulário indicado pela ação */
    private static function formKeys(string $html, string $action): array
    {
        $at = strpos($html, 'action="' . $action . '"');
        self::assertNotFalse($at, "formulário {$action}");
        $form = substr($html, $at, 1200);
        preg_match('/name="version" value="(\d+)"/', $form, $v);
        preg_match('/name="command_uid" value="([a-f0-9]{32})"/', $form, $u);

        return [$v[1] ?? '', $u[1] ?? ''];
    }

    /** @return array<string, string> */
    private function complete(array $override = []): array
    {
        return $override + [
            'description' => 'Avanço de sinal no cruzamento de teste', 'occurred_at' => '2026-10-18T09:10', 'infraction_kind' => 'AVANCO_SEMAFORO',
            'legal_reference' => 'Ref. fictícia', 'agency_id' => (string) $this->agencyId, 'external_ait' => 'DEMO-AIT-H1', 'street' => 'Avenida Sampaio Vidal',
            'source_kind' => 'RADAR', 'equipment_id' => (string) $this->radar, 'vehicle_plate' => 'DMO1A23', 'vehicle_uf' => 'SP', 'vehicle_manufacturer' => 'Movix',
            'vehicle_model' => 'Urbano', 'vehicle_species' => 'Passageiro', 'vehicle_renavam' => '00123456789',
        ];
    }

    private function fines(): int
    {
        return (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE');
    }

    public function testEveryFineRouteRequiresAnAccountAndDeniedWritesStoreNothing(): void
    {
        $id = $this->services->fines()->create(['command_uid' => str_repeat('a', 32), 'description' => 'Registro existente'], (int) $this->services->auth()->createUser('dona', 'Dona', 'OPERADOR', 'senha-de-teste-bem-longa')['id']);
        foreach (['/multas', "/multas/{$id}", '/multas/novo', "/multas/{$id}/editar"] as $path) {
            $response = $this->request('GET', $path);
            self::assertSame(302, $response->getStatusCode(), $path);
            self::assertSame('', (string) $response->getBody(), "{$path}: visitante não recebe dados");
        }
        $token = $this->session->csrfToken();
        foreach (['/multas', "/multas/{$id}", "/multas/{$id}/registrar", "/multas/{$id}/conferencias", "/multas/{$id}/andamento", "/multas/{$id}/cancelar"] as $path) {
            self::assertSame(403, $this->request('POST', $path, ['reason' => 'x'])->getStatusCode(), "{$path} sem CSRF");
            self::assertSame(403, $this->request('POST', $path, ['_csrf' => 'errado'])->getStatusCode(), "{$path} CSRF incorreto");
            self::assertSame(401, $this->request('POST', $path, ['_csrf' => $token, 'reason' => 'x', 'description' => 'Tentativa sem conta'])->getStatusCode(), "{$path} sem conta");
        }
        self::assertSame(1, $this->fines());
        self::assertSame(1, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE_EVENT'));

        $this->loginAs('OPERADOR');
        $external = $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('b', 32), 'description' => 'Vindo de outro site'], ['Origin' => 'https://atacante.example']);
        self::assertSame(403, $external->getStatusCode());
        self::assertSame(1, $this->fines());
    }

    public function testDeactivatedAccountLosesAccessOnTheNextRequest(): void
    {
        $admin = (int) $this->services->auth()->createUser('admin1', 'Admin', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $operator = $this->loginAs('OPERADOR');
        self::assertSame(200, $this->request('GET', '/multas')->getStatusCode());
        $this->services->auth()->setActive($operator, false, $admin);
        self::assertSame(302, $this->request('GET', '/multas')->getStatusCode());
    }

    public function testWholeFlowWorksWithPlainFormsForOperatorAndAdmin(): void
    {
        $this->loginAs('OPERADOR');
        $list = (string) $this->request('GET', '/multas')->getBody();
        self::assertStringContainsString('href="' . self::BASE . '/multas" aria-current="page"', $list, 'item ativo no menu');
        self::assertStringContainsString('Nenhuma multa cadastrada ainda', $list);

        $new = (string) $this->request('GET', '/multas/novo?radar=' . $this->radar)->getBody();
        [, $uid] = self::formKeys($new, self::BASE . '/multas');
        self::assertMatchesRegularExpression('/value="RADAR"[^>]*checked/', $new, 'radar sugerido pela query, sem gravar nada');
        self::assertSame(0, $this->fines());

        $created = $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => $uid, 'description' => 'Rascunho pelo formulário', 'source_kind' => 'RADAR', 'equipment_id' => (string) $this->radar, 'vehicle_plate' => 'DMO1A23',
            'created_source' => 'SEMENTE', 'record_state' => 'REGISTRADO']);
        self::assertSame(303, $created->getStatusCode());
        self::assertMatchesRegularExpression('#^' . self::BASE . '/multas/(\d+)$#', $created->getHeaderLine('Location'));
        $id = (int) basename($created->getHeaderLine('Location'));
        $again = $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => $uid, 'description' => 'Rascunho pelo formulário', 'source_kind' => 'RADAR', 'equipment_id' => (string) $this->radar, 'vehicle_plate' => 'DMO1A23']);
        self::assertSame($created->getHeaderLine('Location'), $again->getHeaderLine('Location'), 'duplo envio do mesmo formulário');
        self::assertSame(1, $this->fines());
        self::assertSame(['RASCUNHO', 'MANUAL'], array_values(array_map('rtrim', $this->services->database()->one('SELECT RECORD_STATE, CREATED_SOURCE FROM FINE')))); // campos do servidor ignorados

        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString(sprintf('DEMO-MUL-%06d', $id), $show);
        self::assertStringContainsString('Completar cadastro', $show);
        self::assertStringContainsString('AIT não informado', $show);

        $edit = (string) $this->request('GET', "/multas/{$id}/editar")->getBody();
        [$version, $uid] = self::formKeys($edit, self::BASE . "/multas/{$id}");
        self::assertSame(303, $this->request('POST', "/multas/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid] + $this->complete())->getStatusCode());

        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString('Registrar após conferência', $show);
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/registrar");
        self::assertSame(303, $this->request('POST', "/multas/{$id}/registrar", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid])->getStatusCode());

        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString('Conferir os documentos do carro', $show);
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/conferencias");
        $check = $this->request('POST', "/multas/{$id}/conferencias", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'scope' => 'OCORRENCIA',
            'checked_at' => '2026-10-18T10:00', 'document_status' => 'PENDENCIA_INFORMADA', 'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV fictício', 'pending_items' => '']);
        self::assertSame(422, $check->getStatusCode(), 'pendência sem descrição');
        $body = (string) $check->getBody();
        self::assertStringContainsString('href="#f-pending_items"', $body, 'resumo leva ao campo');
        self::assertStringContainsString('value="CRLV fictício"', $body, 'digitação mantida');
        self::assertSame(303, $this->request('POST', "/multas/{$id}/conferencias", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'scope' => 'OCORRENCIA',
            'checked_at' => '2026-10-18T10:00', 'document_status' => 'PENDENCIA_INFORMADA', 'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV fictício', 'pending_items' => 'Licenciamento não comprovado'])->getStatusCode());

        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/andamento");
        $progress = $this->request('POST', "/multas/{$id}/andamento", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'process_stage' => 'AUTUACAO',
            'payment_status' => 'PAGAMENTO_INFORMADO', 'paid_on' => '2026-10-18', 'paid_amount' => '130,16', 'payment_reference' => 'DEMO-PAG', 'contest_status' => 'NAO_INFORMADA']);
        self::assertSame(303, $progress->getStatusCode());

        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString('Documentos com pendência', $show);
        self::assertStringContainsString('R$ 130,16', $show);
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/cancelar");
        self::assertSame(303, $this->request('POST', "/multas/{$id}/cancelar", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'reason' => 'Duplicado'])->getStatusCode());
        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString('registro cancelado internamente', $show);
        self::assertStringNotContainsString('Anular', $show);
        self::assertSame(409, $this->request('GET', "/multas/{$id}/editar")->getStatusCode());
        self::assertSame(0, (int) $this->services->database()->value("SELECT COUNT(*) FROM RESOURCE_EVENT WHERE EVENT_TYPE NOT IN ('CADASTRO')"), 'multas não entram no histórico nem nos alertas da central');

        // Administrador também usa o módulo (não há permissão nova).
        $this->session = new ArraySession();
        $this->loginAs('ADMIN', 'admin1');
        $new = (string) $this->request('GET', '/multas/novo')->getBody();
        [, $uid] = self::formKeys($new, self::BASE . '/multas');
        self::assertSame(303, $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => $uid, 'description' => 'Rascunho do administrador'])->getStatusCode());
    }

    public function testErrorsKeepTypingAndStaleFormsShowTheCurrentVersion(): void
    {
        $this->loginAs('OPERADOR');
        $invalid = $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('c', 32)] + $this->complete(['vehicle_plate' => 'XX-9', 'vehicle_renavam' => '123']));
        self::assertSame(422, $invalid->getStatusCode());
        $body = (string) $invalid->getBody();
        self::assertStringContainsString('href="#f-vehicle_plate"', $body);
        self::assertStringContainsString('href="#f-vehicle_renavam"', $body);
        self::assertStringContainsString('value="XX-9"', $body);
        self::assertStringContainsString('value="Avanço de sinal no cruzamento de teste"', $body);
        self::assertSame(0, $this->fines());

        self::assertSame(303, $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('d', 32)] + $this->complete())->getStatusCode());
        $id = (int) $this->services->database()->value('SELECT MAX(ID) FROM FINE');
        $this->request('POST', "/multas/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => '1', 'command_uid' => str_repeat('e', 32)] + $this->complete(['notes' => 'outra aba']));
        $stale = $this->request('POST', "/multas/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => '1', 'command_uid' => str_repeat('f', 32)] + $this->complete(['notes' => 'minha versão']));
        self::assertSame(409, $stale->getStatusCode());
        $body = (string) $stale->getBody();
        self::assertStringContainsString('O registro mudou enquanto você editava', $body);
        self::assertStringContainsString('minha versão', $body, 'digitação mantida');
        self::assertStringContainsString('name="version" value="2"', $body, 'o formulário passa a carregar a versão atual');
        self::assertSame('outra aba', $this->services->fineRepository()->find($id)['notes']);
    }

    public function testUntrustedTextIsEscapedAndPagesHaveNoInlineCode(): void
    {
        $this->loginAs('OPERADOR');
        $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('1', 32)] + $this->complete([
            'description' => '<img src=x onerror=alert(1)>', 'notes' => '"><script>alert(2)</script>', 'legal_reference' => '<b>ref</b>']));
        $id = (int) $this->services->database()->value('SELECT MAX(ID) FROM FINE');
        foreach (['/multas', "/multas/{$id}", "/multas/{$id}/editar", '/multas/novo'] as $path) {
            $html = (string) $this->request('GET', $path)->getBody();
            self::assertStringNotContainsString('<img src=x', $html, $path);
            self::assertStringNotContainsString('<script>alert', $html, $path);
            self::assertDoesNotMatchRegularExpression('#<script\b(?![^>]*\bsrc=)#', $html, "{$path}: sem script inline");
            self::assertDoesNotMatchRegularExpression('#\sstyle="#', $html, "{$path}: sem estilo inline");
            self::assertDoesNotMatchRegularExpression('#\son[a-z]+="#', $html, "{$path}: sem manipulador inline");
            self::assertDoesNotMatchRegularExpression('#(src|href)="https?://(?!tile\.openstreetmap|www\.openstreetmap|dadosabertos\.sp|leafletjs)#', $html, "{$path}: sem CDN");
        }
        $html = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        self::assertMatchesRegularExpression('#<script type="module" src="/promotheusfiscaliza/assets/build/app-[A-Za-z0-9_-]+\.js"></script>#', $html);
    }

    public function testGetNeverWritesMissingIdIs404AndBackLinkStaysInside(): void
    {
        $this->loginAs('OPERADOR');
        self::assertSame(303, $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('2', 32)] + $this->complete())->getStatusCode());
        $id = (int) $this->services->database()->value('SELECT MAX(ID) FROM FINE');
        $events = (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE_EVENT');

        $missing = $this->request('GET', '/multas/987654');
        self::assertSame(404, $missing->getStatusCode());
        self::assertStringNotContainsString('SQL', (string) $missing->getBody());
        self::assertStringNotContainsString('firebird', (string) $missing->getBody());

        $html = (string) $this->request('GET', "/multas/{$id}?voltar=" . rawurlencode('//atacante.example/x'))->getBody();
        self::assertStringContainsString('<a href="' . self::BASE . '/multas">Multas</a>', $html);
        self::assertStringNotContainsString('atacante', $html);
        $html = (string) $this->request('GET', "/multas/{$id}?voltar=" . rawurlencode('origem=RADAR&q=DMO&x=<b>'))->getBody();
        self::assertStringContainsString('href="' . self::BASE . '/multas?q=DMO&amp;origem=RADAR"', $html, 'filtros validados e reconstruídos');

        foreach (['/multas', '/multas?de=2026-13-40', "/multas/{$id}", "/multas/{$id}/editar", "/multas/novo?agente=1"] as $path) {
            $this->request('GET', $path);
        }
        self::assertSame([1, $events], [$this->fines(), (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE_EVENT')]);
        self::assertSame(422, $this->request('GET', '/multas?de=2026-10-20&ate=2026-10-01')->getStatusCode(), 'intervalo invertido é erro legível');
    }

    /**
     * Com JavaScript, o campo fica à vista quando cada bloco [data-show-if] que o contém vale para $state (a mesma leitura
     * de multas.js: "campo=A B;outro=C", vazio depois do "=" é em branco). Um bloco com erro aparece sempre.
     *
     * @param array<string, string> $state
     */
    private static function shownFor(\Dom\Element $field, array $state): bool
    {
        for ($el = $field->closest('[data-show-if]'); $el !== null; $el = $el->parentElement?->closest('[data-show-if]')) {
            $holds = false;
            foreach (array_filter(explode(';', (string) $el->getAttribute('data-show-if'))) as $rule) {
                [$name, $values] = explode('=', $rule, 2) + [1 => ''];
                $holds = $holds || in_array(trim($state[$name] ?? ''), explode(' ', $values), true);
            }
            if (!$holds) {
                return false;
            }
        }

        return true;
    }

    private static function dom(ResponseInterface|string $html): \Dom\HTMLDocument
    {
        return \Dom\HTMLDocument::createFromString(is_string($html) ? $html : (string) $html->getBody(), LIBXML_NOERROR);
    }

    /** @return list<string> campos citados no resumo de erros */
    private static function errorFields(\Dom\HTMLDocument $doc): array
    {
        return array_values(array_map(static fn(\Dom\Element $a): string => substr((string) $a->getAttribute('href'), 3), iterator_to_array($doc->querySelectorAll('.error-summary a[href^="#f-"]'))));
    }

    /** @param array<string, string> $override */
    private function registeredFine(int $user, array $override = []): int
    {
        $id = $this->services->fines()->create(['command_uid' => FineService::newCommandUid()] + $this->complete($override), $user);
        $this->services->fines()->register($id, ['command_uid' => FineService::newCommandUid(), 'version' => '1'], $user);

        return $id;
    }

    public function testFieldsShownOnlyInSomeSituationsAppearExactlyWhenTheServerDemandsThem(): void
    {
        $id = $this->registeredFine($this->loginAs('OPERADOR'));
        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        $doc = self::dom($show);
        // Sem JavaScript, nada fica escondido pelo servidor: todo campo dos dois formulários da ficha está na página.
        $names = [...array_map(static fn(string $k): string => str_replace('_cents', '', $k), \Fiscaliza\Application\FineService::PROGRESS), 'progress_reason',
            'scope', 'effective_on', 'document_status', 'checked_at', 'source_type', 'source_reference', 'crlv_exercise', 'pending_items', 'check_reason', 'review_choice', 'review_on'];
        foreach ($names as $name) {
            $field = $doc->querySelector("[name=\"{$name}\"]");
            self::assertNotNull($field, $name);
            self::assertNull($field->closest('[hidden]'), "{$name} visível sem JavaScript");
        }
        $progressBase = ['process_stage' => 'NAO_INFORMADA', 'payment_status' => 'NAO_INFORMADO', 'contest_status' => 'NAO_INFORMADA'];
        self::assertFalse(self::shownFor($doc->getElementById('f-progress_reason'), $progressBase), 'nada informado ainda: não há o que corrigir');

        // Cada situação que torna campos obrigatórios: o 422 aponta exatamente os campos do bloco que essa situação mostra,
        // marcados com erro (o que também os mantém à vista), e esses campos ficam fora da vista na situação inicial.
        $cases = [
            ['andamento', $progressBase, ['payment_status' => 'PAGAMENTO_INFORMADO'], ['paid_on', 'paid_amount', 'payment_reference']],
            ['andamento', $progressBase, ['contest_status' => 'EM_ANALISE'], ['contest_kind', 'contest_submitted_on', 'contest_reference']],
            ['andamento', $progressBase, ['contest_status' => 'DEFERIDA'], ['contest_kind', 'contest_submitted_on', 'contest_reference', 'contest_decided_on', 'contest_result_note']],
            ['andamento', $progressBase, ['process_stage' => 'ENCERRAMENTO_INFORMADO'], ['closed_on', 'closure_reference', 'closure_note']],
            ['conferencias', ['document_status' => '', 'source_type' => 'NAO_INFORMADA'], ['document_status' => 'REGULARIDADE_INFORMADA'], ['source_type', 'source_reference']],
            ['conferencias', ['document_status' => '', 'source_type' => 'NAO_INFORMADA'], ['document_status' => 'PENDENCIA_INFORMADA', 'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV fictício'], ['pending_items']],
            ['conferencias', ['document_status' => '', 'source_type' => 'NAO_INFORMADA'], ['document_status' => 'NAO_VERIFICADA'], ['check_reason']],
        ];
        foreach ($cases as [$action, $base, $trigger, $expected]) {
            $state = $trigger + $base;
            [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/{$action}");
            $extra = $action === 'conferencias' ? ['scope' => 'OCORRENCIA', 'checked_at' => '2026-10-18T10:00'] : [];
            $response = $this->request('POST', "/multas/{$id}/{$action}", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid] + $extra + $state);
            self::assertSame(422, $response->getStatusCode(), json_encode($trigger));
            $errorDoc = self::dom($response);
            self::assertEqualsCanonicalizing($expected, self::errorFields($errorDoc), json_encode($trigger));
            foreach ($expected as $name) {
                $field = $errorDoc->getElementById("f-{$name}");
                self::assertSame('true', $field->getAttribute('aria-invalid'), $name);
                self::assertTrue(self::shownFor($field, $state), "{$name} à vista com " . json_encode($trigger));
                self::assertFalse(self::shownFor($doc->getElementById("f-{$name}"), $base), "{$name} fora da vista antes de " . json_encode($trigger));
            }
        }

        // Depois de algo informado, o motivo da correção fica sempre à vista: corrigir sem ele é recusado.
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/andamento");
        $paid = ['payment_status' => 'PAGAMENTO_INFORMADO', 'paid_on' => '2026-10-18', 'paid_amount' => '130,16', 'payment_reference' => 'DEMO-PAG'] + $progressBase;
        self::assertSame(303, $this->request('POST', "/multas/{$id}/andamento", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid] + $paid)->getStatusCode());
        $show = (string) $this->request('GET', "/multas/{$id}")->getBody();
        self::assertTrue(self::shownFor(self::dom($show)->getElementById('f-progress_reason'), $progressBase));
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$id}/andamento");
        $fix = $this->request('POST', "/multas/{$id}/andamento", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'paid_amount' => '120,00'] + $paid);
        self::assertSame(['progress_reason'], self::errorFields(self::dom($fix)));
    }

    public function testRegistrationFormHidesOnlyOptionalOrAlternativeFieldsAndOpensThemWhenTheyMatter(): void
    {
        $user = $this->loginAs('OPERADOR');
        $new = self::dom($this->request('GET', '/multas/novo'));
        // Sem JavaScript, todo campo do cadastro continua na página (os recolhidos abrem com um clique).
        $names = [...array_diff(\Fiscaliza\Application\FineService::CADASTRO, ['speed_considered_kmh', 'radar_details']),
            'operator_agent_id', 'radar_meter_kind', 'radar_inmetro_record', 'radar_certificate', 'radar_verified_on'];
        foreach ($names as $name) {
            self::assertNotNull($new->querySelector("[name=\"{$name}\"]"), $name);
        }
        self::assertCount(3, $new->querySelectorAll('.form-step__n'), 'as etapas só de texto (documentação e revisão) saíram');
        foreach (['ait_missing_reason', 'renavam_missing_reason', 'infraction_code', 'operator_agent_id', 'vehicle_color'] as $name) {
            self::assertFalse($new->getElementById("f-{$name}")->closest('details')->hasAttribute('open'), "{$name} recolhido num cadastro novo");
        }
        self::assertSame('external_ait=', $new->getElementById('f-ait_missing_reason')->closest('[data-show-if]')->getAttribute('data-show-if'));
        self::assertSame('vehicle_renavam=', $new->getElementById('f-renavam_missing_reason')->closest('[data-show-if]')->getAttribute('data-show-if'));

        // Retificar tirando o AIT e o RENAVAM sem dizer por quê: o erro aparece e as alternativas vêm abertas.
        $id = $this->registeredFine($user, ['vehicle_color' => 'Prata']);
        $edit = (string) $this->request('GET', "/multas/{$id}/editar")->getBody();
        self::assertTrue(self::dom($edit)->getElementById('f-vehicle_color')->closest('details')->hasAttribute('open'), 'opcional preenchido vem aberto');
        [$version, $uid] = self::formKeys($edit, self::BASE . "/multas/{$id}");
        $response = $this->request('POST', "/multas/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid, 'reason' => 'Teste']
            + $this->complete(['external_ait' => '', 'vehicle_renavam' => '']));
        self::assertSame(422, $response->getStatusCode());
        $doc = self::dom($response);
        self::assertEqualsCanonicalizing(['external_ait', 'vehicle_renavam'], self::errorFields($doc));
        foreach (['ait_missing_reason', 'renavam_missing_reason'] as $name) {
            self::assertTrue($doc->getElementById("f-{$name}")->closest('details')->hasAttribute('open'), $name);
        }

        // Órgão autuador diferente do órgão do radar: o registro pede a observação, e o formulário traz o órgão de cada
        // radar e agente para mostrá-la nesse caso (a mesma comparação de FineService::divergence).
        $other = $this->services->referenceRepository()->insertAgency('GCD', 'Outro órgão (teste)', 'GUARDA');
        $draft = $this->services->fines()->create(['command_uid' => FineService::newCommandUid()] + $this->complete(['agency_id' => (string) $other, 'external_ait' => 'DEMO-AIT-H9']), $user);
        $show = (string) $this->request('GET', "/multas/{$draft}")->getBody();
        [$version, $uid] = self::formKeys($show, self::BASE . "/multas/{$draft}/registrar");
        $register = $this->request('POST', "/multas/{$draft}/registrar", ['_csrf' => $this->session->csrfToken(), 'version' => $version, 'command_uid' => $uid]);
        self::assertSame(422, $register->getStatusCode());
        self::assertStringContainsString('#f-source_note', (string) $register->getBody());
        $form = self::dom($this->request('GET', "/multas/{$draft}/editar"));
        self::assertSame((string) $this->agencyId, $form->querySelector('#f-equipment_id option[selected]')->getAttribute('data-agency'));
        self::assertSame((string) $other, $form->querySelector('#f-agency_id option[selected]')?->getAttribute('value'), 'opções com o ID do órgão');
        self::assertNotNull($form->getElementById('f-source_note')->closest('[data-source-note]'));
    }

    public function testModuleWorksWithAnEmptyBasePath(): void
    {
        $created = $this->services->auth()->createUser('raiz', 'Raiz', 'OPERADOR', 'senha-de-teste-bem-longa');
        self::assertNotEmpty($created['id']);
        $login = $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => 'raiz', 'password' => 'senha-de-teste-bem-longa'], base: '');
        self::assertSame(303, $login->getStatusCode());
        $list = (string) $this->request('GET', '/multas', base: '')->getBody();
        self::assertStringContainsString('href="/multas/novo"', $list);
        self::assertStringNotContainsString('/promotheusfiscaliza', $list);
        $new = $this->request('POST', '/multas', ['_csrf' => $this->session->csrfToken(), 'command_uid' => str_repeat('3', 32), 'description' => 'Rascunho sem prefixo'], base: '');
        self::assertMatchesRegularExpression('#^/multas/\d+$#', $new->getHeaderLine('Location'));
    }
}
