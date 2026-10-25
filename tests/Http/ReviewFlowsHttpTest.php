<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Application\FineService;
use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Http\WebApp;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Fluxos da revisão de lógica de 24/10/2026 (R01–R08, ADR-0023) pelo app Slim real: o que a tela oferece é o que o
 * serviço aceita, nada pendente fica fora do conjunto que a tela usa, e o texto não promete mais do que o sistema sabe.
 */
final class ReviewFlowsHttpTest extends FirebirdTestCase
{
    private const BASE = '/promotheusfiscaliza';
    private const PASSWORD = 'senha-de-teste-bem-longa';
    private ArraySession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
    }

    /** @param array<string, mixed> $body */
    private function request(string $method, string $path, array $body = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . self::BASE . $path, ['REMOTE_ADDR' => '203.0.113.24']);
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

    private function login(string $username, string $password): int
    {
        return $this->request('POST', '/entrar', ['_csrf' => $this->session->csrfToken(), 'username' => $username, 'password' => $password])->getStatusCode();
    }

    private function loginAs(string $role): int
    {
        $id = (int) $this->services->auth()->createUser(strtolower($role) . '1', 'Pessoa ' . $role, $role, self::PASSWORD)['id'];
        self::assertSame(303, $this->login(strtolower($role) . '1', self::PASSWORD));

        return $id;
    }

    private function page(string $path): string
    {
        $response = $this->request('GET', $path);
        self::assertSame(200, $response->getStatusCode(), $path);

        return (string) $response->getBody();
    }

    private function openOrder(int $equipmentId, int $actor): int
    {
        return $this->services->maintenance()->open($equipmentId, ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'reason' => 'Inspeção de teste.'], $actor);
    }

    /** Furtado: a próxima ação pede a decisão (repor ou dar baixa) ali mesmo, sem mandar para o fim da página. */
    public function testStolenEquipmentAsksForReplacementOrWriteOffInTheNextAction(): void
    {
        $this->loginAs('OPERADOR');
        $id = $this->createEquipment(['status' => 'FURTADO', 'serial_number' => 'FUR-A']);

        $html = $this->page("/equipamentos/{$id}");
        self::assertStringContainsString('O equipamento será reposto?', $html);
        self::assertStringContainsString('name="status" value="NAO_INSTALADO"', $html);
        self::assertStringContainsString('name="status" value="DESATIVADO"', $html);
    }

    /** R02: a próxima ação e o quadro só oferecem iniciar quando o serviço aceita; o pré-requisito aparece junto. */
    public function testMaintenanceStartIsOfferedOnlyWhenTheEquipmentCanEnterMaintenance(): void
    {
        $actor = $this->loginAs('OPERADOR');
        $planned = $this->createEquipment(['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO', 'serial_number' => 'R02-A']);
        $deactivated = $this->createEquipment(['status' => 'DESATIVADO', 'serial_number' => 'R02-B']);
        $working = $this->createEquipment(['serial_number' => 'R02-C']);
        $orders = [$planned => $this->openOrder($planned, $actor), $deactivated => $this->openOrder($deactivated, $actor), $working => $this->openOrder($working, $actor)];

        $html = $this->page("/equipamentos/{$planned}");
        self::assertStringContainsString('Registrar a instalação quando ela acontecer', $html, 'a instalação continua sendo o próximo passo');
        self::assertStringContainsString("Ordem #{$orders[$planned]} agendada. A intervenção só pode começar depois da instalação", $html);
        self::assertStringNotContainsString("/manutencoes/{$orders[$planned]}/iniciar", $html);
        self::assertStringContainsString("/manutencoes/{$orders[$planned]}/cancelar", $html, 'cancelar continua possível');
        self::assertStringContainsString('Nesta situação dá para agendar, mas não para iniciar.', $html);

        $html = $this->page("/equipamentos/{$deactivated}");
        self::assertStringContainsString("Decidir sobre a ordem #{$orders[$deactivated]}", $html);
        self::assertStringNotContainsString('Iniciar a ordem', $html);
        self::assertStringNotContainsString("/manutencoes/{$orders[$deactivated]}/iniciar", $html);

        self::assertStringContainsString("Iniciar a ordem #{$orders[$working]}", $this->page("/equipamentos/{$working}"));

        $board = $this->page('/manutencao');
        self::assertStringNotContainsString("/manutencoes/{$orders[$planned]}/iniciar", $board);
        self::assertStringNotContainsString("/manutencoes/{$orders[$deactivated]}/iniciar", $board);
        self::assertStringContainsString("/manutencoes/{$orders[$working]}/iniciar", $board);
        self::assertSame(2, substr_count($board, 'Ainda não pode ser iniciada.'));
    }

    /** R03: uma ordem antiga pendente continua na ficha (com cancelar e no arquivamento) atrás de mais de 30 encerradas. */
    public function testAnOldPendingOrderStaysReachableBehindThirtyClosedOnes(): void
    {
        $actor = $this->loginAs('OPERADOR');
        $id = $this->createEquipment();
        $old = $this->openOrder($id, $actor);
        for ($i = 0; $i < 31; $i++) {
            $this->clock->advance('+1 minute');
            $this->services->maintenance()->cancel($this->openOrder($id, $actor), 'Aberta por engano no teste.', $actor);
        }

        $html = $this->page("/equipamentos/{$id}");
        self::assertStringContainsString("id=\"ordem-{$old}\"", $html);
        self::assertStringContainsString("/manutencoes/{$old}/cancelar", $html);
        self::assertStringContainsString("Iniciar a ordem #{$old}", $html, 'a próxima ação considera a pendência antiga');
        self::assertStringContainsString("a ordem agendada (#{$old})", $html, 'o arquivamento pede para cancelá-la');
        self::assertStringContainsString('Mostrando as 30 encerradas mais recentes de 31.', $html, 'o limite do histórico é explícito');
        self::assertStringContainsString("/equipamentos/{$id}#ordem-{$old}", $this->page('/manutencao'), 'a fila leva direto à ordem');
    }

    /** R07: acima de 20 missões confirmadas, todas continuam com concluir/cancelar, e a ficha do agente leva à dela. */
    public function testEveryConfirmedMissionKeepsItsCloseActionsAndTheAgentPageLinksToIt(): void
    {
        $runId = $this->services->urbanImporter()->import(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST)['id'];
        $actor = $this->loginAs('OPERADOR');
        $missions = [];
        for ($i = 1; $i <= 22; $i++) {
            $agent = $this->createAgent(['call_sign' => sprintf('Hermes R%02d', $i)]);
            $missions[$agent] = $this->services->planning()->confirm(['run_id' => (string) $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => (string) $agent,
                'confirm' => '1', 'justification' => 'teste'], $actor);
        }
        $this->services->planning()->close(end($missions), 'CONCLUIDA', 'Ronda feita.', $actor);

        $html = $this->page('/planejar');
        self::assertSame(21, substr_count($html, '/concluir"'), 'as 21 vigentes, inclusive a mais antiga');
        $first = array_key_first($missions);
        self::assertStringContainsString("id=\"missao-{$missions[$first]}\"", $html);
        self::assertStringContainsString("/planejar/missoes/{$missions[$first]}/cancelar", $html);
        self::assertStringContainsString(self::BASE . "/planejar#missao-{$missions[$first]}", $this->page("/agentes/{$first}"));
    }

    /** R06: o administrador devolve o acesso à mesma conta; a senha anterior e as sessões abertas deixam de valer. */
    public function testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd(): void
    {
        $carla = (int) $this->services->auth()->createUser('carla', 'Carla', 'OPERADOR', self::PASSWORD)['id'];
        self::assertSame(303, $this->login('carla', self::PASSWORD));
        $carlaSession = $this->session;
        self::assertSame(200, $this->request('GET', '/')->getStatusCode());

        $this->session = new ArraySession();
        $admin = $this->loginAs('ADMIN');
        self::assertStringContainsString('Redefinir senha', $this->page('/contas'));
        $reset = $this->request('POST', "/contas/{$carla}/senha", ['_csrf' => $this->session->csrfToken()]);
        $body = (string) $reset->getBody();
        self::assertSame(200, $reset->getStatusCode());
        self::assertSame(1, preg_match('#Senha nova \(mostrada só agora\): <code>([A-Za-z0-9_-]{20})</code>#', $body, $m), 'senha nova mostrada uma vez');
        self::assertStringContainsString('Senha redefinida em', $body, 'autoria e instante na lista');
        self::assertStringNotContainsString($m[1], (string) json_encode((fn(): array => $this->data)->call($this->session)), 'a senha não vai para a sessão');
        $row = $this->services->userRepository()->find($carla);
        self::assertSame([$admin, 1, 2], [(int) $row['password_reset_by'], (int) $row['credential_no'], count($this->services->auth()->users())], 'mesma conta, sem cadastro novo');
        self::assertStringNotContainsString($m[1], (string) $this->services->database()->value('SELECT LIST(SUMMARY) FROM RESOURCE_EVENT'));

        $self = $this->request('POST', "/contas/{$admin}/senha", ['_csrf' => $this->session->csrfToken()]);
        self::assertSame(303, $self->getStatusCode(), 'a própria senha não se redefine por aqui');
        self::assertSame(0, (int) $this->services->userRepository()->find($admin)['credential_no']);

        $this->session = $carlaSession;
        self::assertSame(302, $this->request('GET', '/')->getStatusCode(), 'a sessão aberta antes da redefinição termina');
        self::assertStringContainsString('foi redefinida pelo administrador', (string) $this->request('GET', '/entrar')->getBody());

        $this->session = new ArraySession();
        self::assertSame(401, $this->login('carla', self::PASSWORD), 'a senha anterior deixou de valer');
        self::assertSame(303, $this->login('carla', $m[1]));
        self::assertSame(200, $this->request('GET', '/')->getStatusCode());
        self::assertSame(403, $this->request('POST', "/contas/{$admin}/senha", ['_csrf' => $this->session->csrfToken()])->getStatusCode(), 'operador não redefine senhas');
        $this->session = new ArraySession();
        self::assertStringContainsString('Esqueceu a senha? Peça ao administrador para redefini-la em Contas', $this->page('/entrar'));
    }

    /** R08: o resumo diz que conta a situação registrada e mostra, junto, quantos têm sinal recente. */
    public function testWorkingSummaryNamesTheRegisteredSituationAndTheRecentSignal(): void
    {
        $this->loginAs('OPERADOR');
        $this->createEquipment(['serial_number' => 'R08-A']);
        $this->clock->advance('+1 hour');
        $html = $this->page('/');
        self::assertStringContainsString('operacionais<br><span data-kpi="operational_confirmed" x-text="kpi.operational_confirmed">0</span> com sinal</span>', $html);
        self::assertStringNotContainsString('>funcionando<', $html);
    }

    /** R01: a retificação recusada mantém a digitação, diz quais datas conflitam e leva à situação da multa. */
    public function testFineRetificationConflictKeepsTypingAndPointsToTheProgress(): void
    {
        $actor = $this->loginAs('OPERADOR');
        $radar = $this->createEquipment(['type_code' => 'RADAR', 'serial_number' => 'R01-R', 'specs' => ['functions' => ['VELOCIDADE'], 'speed_limit_kmh' => '50', 'lanes' => '2', 'modality' => 'PORTATIL']]);
        $data = ['description' => 'Excesso de velocidade de teste', 'occurred_at' => '2026-10-10T09:10', 'infraction_kind' => 'VELOCIDADE', 'legal_reference' => 'Ref. fictícia',
            'agency_id' => (string) $this->agencyId, 'external_ait' => 'DEMO-AIT-R01', 'street' => 'Avenida Sampaio Vidal', 'source_kind' => 'RADAR', 'equipment_id' => (string) $radar,
            'speed_limit_kmh' => '50', 'speed_measured_kmh' => '71', 'vehicle_plate' => 'ABC1D23', 'vehicle_uf' => 'SP', 'vehicle_manufacturer' => 'Movix', 'vehicle_model' => 'Urbano',
            'vehicle_species' => 'Passageiro', 'vehicle_renavam' => '00123456789', 'radar_inmetro_record' => 'I', 'radar_certificate' => 'C', 'radar_verified_on' => '2026-03-01',
            'evidence_reference' => 'DEMO-IMG-1'];
        $fines = $this->services->fines();
        $id = $fines->create($data + ['command_uid' => FineService::newCommandUid()], $actor);
        $version = fn(): string => (string) $this->services->fineRepository()->find($id)['version_no'];
        $fines->register($id, ['version' => $version(), 'command_uid' => FineService::newCommandUid()], $actor);
        $fines->updateProgress($id, ['version' => $version(), 'command_uid' => FineService::newCommandUid(), 'process_stage' => 'NAO_INFORMADA', 'payment_status' => 'PAGAMENTO_INFORMADO',
            'paid_on' => '2026-10-14', 'paid_amount' => '10,00', 'payment_reference' => 'DEMO-PAG', 'contest_status' => 'NAO_INFORMADA', 'nominal_amount' => '10,00'], $actor);

        $response = $this->request('POST', "/multas/{$id}", ['_csrf' => $this->session->csrfToken(), 'version' => $version(), 'command_uid' => FineService::newCommandUid(),
            'occurred_at' => '2026-10-16T09:10', 'reason' => 'Data relida no auto'] + $data);
        $html = (string) $response->getBody();
        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('pago em 14/10/2026', $html);
        self::assertStringContainsString('>Data relida no auto</textarea>', $html, 'o motivo digitado continua');
        self::assertStringContainsString('value="2026-10-16T09:10"', $html, 'a data digitada continua, para corrigir');
        self::assertStringContainsString("/multas/{$id}#andamento", $html);
        self::assertStringContainsString('a data mais antiga anotada é 14/10/2026.', $html, 'o limite aparece no "?" do campo');
    }
}
