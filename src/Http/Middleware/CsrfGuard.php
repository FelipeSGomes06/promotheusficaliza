<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Middleware;

use Fiscaliza\Http\HttpError;
use Fiscaliza\Http\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Toda requisição que altera estado (POST/PUT/PATCH/DELETE) exige o token de sincronização da sessão, enviado no
 * campo "_csrf" ou no cabeçalho X-CSRF-Token. Se houver cabeçalho Origin, ele precisa ser a própria origem.
 * GET nunca altera estado. SameSite=Lax é defesa complementar, não substituto.
 */
final class CsrfGuard implements MiddlewareInterface
{
    /** @param string $origin origem pública configurada (APP_ORIGIN); necessária atrás de proxy que termina TLS */
    public function __construct(private readonly string $origin = '') {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $handler->handle($request);
        }
        $origin = $request->getHeaderLine('Origin');
        if ($origin !== '' && $origin !== ($this->origin !== '' ? rtrim($this->origin, '/') : self::expectedOrigin($request))) {
            throw new HttpError(403, 'Origem da requisição não permitida.');
        }
        /** @var Session $session */
        $session = $request->getAttribute('session');
        $body = $request->getParsedBody();
        $token = is_array($body) ? ($body['_csrf'] ?? null) : null;
        $token ??= $request->getHeaderLine('X-CSRF-Token') ?: null;
        if (!$session->validCsrf($token)) {
            throw new HttpError(403, 'Formulário expirado ou inválido. Recarregue a página e tente de novo.');
        }

        return $handler->handle($request);
    }

    private static function expectedOrigin(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $port = $uri->getPort();

        return $uri->getScheme() . '://' . $uri->getHost() . ($port === null ? '' : ':' . $port);
    }
}
