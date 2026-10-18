<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Http\HttpError;
use Fiscaliza\Http\Session;
use Fiscaliza\Http\View;
use Fiscaliza\Services;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Utilidades comuns dos controladores: renderizar, redirecionar dentro do prefixo, ler corpo e conta atual. */
abstract class Controller
{
    public function __construct(protected readonly Services $services, protected readonly View $view) {}

    /** @param array<string, mixed> $data */
    protected function render(ServerRequestInterface $request, ResponseInterface $response, string $template, array $data = [], int $status = 200): ResponseInterface
    {
        return $this->view->render($request, $response, $template, $data, $status);
    }

    /** Redireciona para um caminho interno da aplicação (sempre sob o prefixo; nunca para URL externa). */
    protected function redirect(ResponseInterface $response, string $path, int $status = 303): ResponseInterface
    {
        return $response->withStatus($status)->withHeader('Location', $this->services->config->basePath() . $path);
    }

    /** @param array<string, mixed> $data */
    protected function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8')->withHeader('Cache-Control', 'no-store');
    }

    protected function session(ServerRequestInterface $request): Session
    {
        /** @var Session */
        return $request->getAttribute('session');
    }

    /** @return array<string, mixed> */
    protected function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

    protected function userId(ServerRequestInterface $request): int
    {
        $user = $request->getAttribute('user');
        if (!is_array($user)) {
            throw new HttpError(401, 'Entre para continuar.');
        }

        return (int) $user['id'];
    }

    /** @param array<string, string> $args */
    protected static function id(array $args, string $key = 'id'): int
    {
        $id = filter_var($args[$key] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new HttpError(404, 'Página não encontrada.');
        }

        return $id;
    }

    /** @param array<string, mixed> $query */
    protected static function pageNumber(array $query): int
    {
        $page = filter_var($query['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);

        return $page === false ? 1 : $page;
    }
}
