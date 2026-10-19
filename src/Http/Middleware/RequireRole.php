<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Middleware;

use Fiscaliza\Http\HttpError;
use Fiscaliza\Infrastructure\DatabaseUnavailable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Autorização no servidor por rota: OPERADOR (consulta, cadastros e operação) ou ADMIN (também contas). ADMIN inclui
 * OPERADOR. Sem conta não há acesso: visitante em página GET é levado ao login; nas demais requisições (inclusive a
 * API) recebe 401, mesmo chamando a rota diretamente.
 */
final class RequireRole implements MiddlewareInterface
{
    public function __construct(private readonly string $role, private readonly string $basePath) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if ($user === null) {
            if ($request->getAttribute('database_unavailable') === true) {
                // Sessão existente, mas a conta não pôde ser conferida: mostra a indisponibilidade em vez do login.
                throw new DatabaseUnavailable('Banco indisponível ao conferir a conta da sessão.');
            }
            if ($request->getMethod() === 'GET' && !str_contains($request->getUri()->getPath(), '/api/')) {
                $back = $request->getUri()->getPath() . ($request->getUri()->getQuery() !== '' ? '?' . $request->getUri()->getQuery() : '');

                return (new Response(302))->withHeader('Location', $this->basePath . '/entrar?voltar=' . rawurlencode($back));
            }
            throw new HttpError(401, 'Entre com a conta cadastrada pelo administrador para acessar o sistema.');
        }
        if ($this->role === 'ADMIN' && $user['role'] !== 'ADMIN') {
            throw new HttpError(403, 'Esta área é restrita a administradores.');
        }

        return $handler->handle($request);
    }
}
