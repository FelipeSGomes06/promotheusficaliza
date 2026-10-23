<?php

declare(strict_types=1);

namespace Fiscaliza\Http;

use Fiscaliza\Application\NotFound;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Http\Controller\AddressController;
use Fiscaliza\Http\Controller\AgentController;
use Fiscaliza\Http\Controller\AuthController;
use Fiscaliza\Http\Controller\CentralController;
use Fiscaliza\Http\Controller\EquipmentController;
use Fiscaliza\Http\Controller\FineController;
use Fiscaliza\Http\Controller\OperationsController;
use Fiscaliza\Http\Controller\PageController;
use Fiscaliza\Http\Controller\PlanningController;
use Fiscaliza\Http\Middleware\CsrfGuard;
use Fiscaliza\Http\Middleware\RequireRole;
use Fiscaliza\Http\Middleware\SecurityHeaders;
use Fiscaliza\Http\Middleware\SessionContext;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\DatabaseUnavailable;
use Fiscaliza\Services;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Slim\Routing\RouteCollectorProxy;

/**
 * Montagem da aplicação web. Ordem de execução dos middlewares (de fora para dentro):
 * cabeçalhos de segurança → sessão/conta → tratamento de erros → leitura do corpo → roteamento → CSRF → rota.
 */
final class WebApp
{
    /** @return App<\Psr\Container\ContainerInterface|null> */
    public static function create(Services $services, ?Session $session = null): App
    {
        $config = $services->config;
        $base = $config->basePath();
        $session ??= new Session('PFSESSID', $base . '/', $config->bool('APP_SECURE_COOKIES'));
        $view = new View($config, $services->root . '/templates', $services->root . '/public', $config->isProduction() ? $services->root . '/var/cache/twig' : null);

        $app = AppFactory::create();
        $app->setBasePath($base);

        $central = new CentralController($services, $view);
        $equipment = new EquipmentController($services, $view);
        $agents = new AgentController($services, $view);
        $ops = new OperationsController($services, $view);
        $auth = new AuthController($services, $view);
        $pages = new PageController($services, $view);
        $planning = new PlanningController($services, $view);
        $fines = new FineController($services, $view);
        $address = new AddressController($services, $view);
        $operator = new RequireRole('OPERADOR', $base);
        $admin = new RequireRole('ADMIN', $base);
        $id = '{id:[0-9]+}';

        // Únicas rotas sem conta: login/logout e verificação de saúde (sem dados).
        $app->get('/entrar', [$auth, 'loginForm']);
        $app->post('/entrar', [$auth, 'login']);
        $app->post('/sair', [$auth, 'logout']);
        $app->get('/saude', [$pages, 'health']);

        // Consulta: exige conta cadastrada pelo administrador (não há leitura pública nem cadastro aberto).
        $app->group('', function (RouteCollectorProxy $g) use ($central, $equipment, $agents, $ops, $pages, $planning, $fines, $address, $id): void {
            $g->get('/', [$central, 'page']);
            $g->get('/api/central', [$central, 'snapshot']);
            $g->get('/api/cep/{cep}', [$address, 'cep']);
            $g->get('/api/localizar', [$address, 'locate']);
            $g->get('/equipamentos', [$equipment, 'index']);
            $g->get("/equipamentos/{$id}", [$equipment, 'show']);
            $g->get('/agentes', [$agents, 'index']);
            $g->get("/agentes/{$id}", [$agents, 'show']);
            $g->get('/multas', [$fines, 'index']);
            $g->get("/multas/{$id}", [$fines, 'show']);
            $g->get('/manutencao', [$ops, 'maintenance']);
            $g->get('/antecipacao', [$ops, 'analysis']);
            $g->get('/planejar', [$planning, 'page']);
            $g->get('/planejar/retrospecto', [$planning, 'replay']);
            $g->get('/sobre', [$pages, 'about']);
        })->add($operator);

        // Escrita: operador autenticado.
        $app->group('', function (RouteCollectorProxy $g) use ($equipment, $agents, $ops, $planning, $fines, $id): void {
            $g->get('/equipamentos/novo', [$equipment, 'createForm']);
            $g->post('/equipamentos', [$equipment, 'store']);
            $g->get("/equipamentos/{$id}/editar", [$equipment, 'editForm']);
            $g->post("/equipamentos/{$id}", [$equipment, 'update']);
            $g->post("/equipamentos/{$id}/situacao", [$equipment, 'changeStatus']);
            $g->post("/equipamentos/{$id}/arquivar", [$equipment, 'archive']);
            $g->post("/equipamentos/{$id}/simular-falha", [$equipment, 'simulateFailure']);
            $g->post("/equipamentos/{$id}/manutencoes", [$ops, 'open']);
            $g->get('/agentes/novo', [$agents, 'createForm']);
            $g->post('/agentes', [$agents, 'store']);
            $g->get("/agentes/{$id}/editar", [$agents, 'editForm']);
            $g->post("/agentes/{$id}", [$agents, 'update']);
            $g->post("/agentes/{$id}/situacao", [$agents, 'changeStatus']);
            $g->post("/agentes/{$id}/arquivar", [$agents, 'archive']);
            $g->get('/multas/novo', [$fines, 'createForm']);
            $g->post('/multas', [$fines, 'store']);
            $g->get("/multas/{$id}/editar", [$fines, 'editForm']);
            $g->post("/multas/{$id}", [$fines, 'update']);
            $g->post("/multas/{$id}/registrar", [$fines, 'register']);
            $g->post("/multas/{$id}/conferencias", [$fines, 'addCheck']);
            $g->post("/multas/{$id}/andamento", [$fines, 'progress']);
            $g->post("/multas/{$id}/cancelar", [$fines, 'cancel']);
            $g->post("/manutencoes/{$id}/iniciar", [$ops, 'start']);
            $g->post("/manutencoes/{$id}/concluir", [$ops, 'complete']);
            $g->post("/manutencoes/{$id}/cancelar", [$ops, 'cancel']);
            $g->post("/alertas/{$id}/reconhecer", [$ops, 'acknowledge']);
            $g->post('/antecipacao/recalcular', [$ops, 'recalculate']);
            $g->post('/planejar/missoes', [$planning, 'confirm']);
            $g->post("/planejar/missoes/{$id}/concluir", [$planning, 'complete']);
            $g->post("/planejar/missoes/{$id}/cancelar", [$planning, 'cancel']);
        })->add($operator);

        // Contas: somente administrador.
        $app->group('', function (RouteCollectorProxy $g) use ($auth, $id): void {
            $g->get('/contas', [$auth, 'accounts']);
            $g->post('/contas', [$auth, 'createAccount']);
            $g->post("/contas/{$id}/situacao", [$auth, 'toggleAccount']);
        })->add($admin);

        $app->add(new CsrfGuard($config->get('APP_ORIGIN')));
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();
        $errors = $app->addErrorMiddleware(false, false, false);
        $errors->setDefaultErrorHandler(static fn(ServerRequestInterface $request, \Throwable $e) => self::renderError($request, $e, $view, $config->isProduction()));
        $app->add(new SessionContext($session, $services->auth()));
        $app->add(new SecurityHeaders($config->get('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')));

        return $app;
    }

    /** Converte exceções em respostas seguras: mensagem útil, identificador de correlação e nada de SQL/DSN/stack. */
    public static function renderError(ServerRequestInterface $request, \Throwable $e, View $view, bool $production): ResponseInterface
    {
        [$status, $message] = match (true) {
            $e instanceof HttpError => [$e->status, $e->getMessage()],
            $e instanceof HttpNotFoundException, $e instanceof NotFound => [404, 'Página não encontrada.'],
            $e instanceof HttpMethodNotAllowedException => [405, 'Método não permitido para este endereço.'],
            $e instanceof ValidationFailed => [422, $e->getMessage()],
            $e instanceof RuleViolation => [409, $e->getMessage()],
            $e instanceof ConcurrencyConflict => [409, $e->getMessage()],
            $e instanceof DatabaseUnavailable => [503, 'Banco de dados indisponível no momento. Tente novamente em instantes.'],
            default => [500, 'Erro inesperado. Informe o código abaixo se o problema continuar.'],
        };
        $reference = substr(bin2hex(random_bytes(6)), 0, 12);
        if ($status >= 500) {
            // Log privado (fora do alcance HTTP): classe, mensagem e origem; sem corpo da requisição nem cookies.
            error_log(sprintf(
                '[fiscaliza %s] %s %s: %s: %s @ %s:%d%s',
                $reference,
                $request->getMethod(),
                $request->getUri()->getPath(),
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getPrevious() ? ' | causa: ' . $e->getPrevious()->getMessage() : '',
            ));
        }
        $response = new Response();
        if (str_contains($request->getUri()->getPath(), '/api/') || $request->getHeaderLine('Accept') === 'application/json') {
            $response->getBody()->write((string) json_encode(['error' => $message, 'reference' => $reference], JSON_UNESCAPED_UNICODE));

            return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8')->withHeader('Cache-Control', 'no-store');
        }

        return $view->render($request, $response, 'error.twig', ['status' => $status, 'message' => $message, 'reference' => $reference], $status);
    }
}
