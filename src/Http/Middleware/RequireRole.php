<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Middleware;

use Fiscaliza\Http\HttpError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Autorização no servidor por rota: OPERADOR (cadastros e operação) ou ADMIN (também contas). ADMIN inclui OPERADOR.
 * Visitante em página GET é levado ao login; nas demais requisições recebe 401, mesmo chamando a rota diretamente.
 */
final class RequireRole implements MiddlewareInterface
{
    public function __construct(private readonly string $role, private readonly string $basePath) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if ($user === null) {
            if ($request->getMethod() === 'GET' && !str_contains($request->getUri()->getPath(), '/api/')) {
                $back = $request->getUri()->getPath() . ($request->getUri()->getQuery() !== '' ? '?' . $request->getUri()->getQuery() : '');

                return (new Response(302))->withHeader('Location', $this->basePath . '/entrar?voltar=' . rawurlencode($back));
            }
            throw new HttpError(401, 'Entre com uma conta de operador para fazer alterações.');
        }
        if ($this->role === 'ADMIN' && $user['role'] !== 'ADMIN') {
            throw new HttpError(403, 'Esta área é restrita a administradores.');
        }

        return $handler->handle($request);
    }
}
