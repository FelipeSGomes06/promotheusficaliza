<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cabeçalhos de proteção emitidos pela própria aplicação (valem em qualquer servidor/proxy).
 *
 * CSP sem 'unsafe-inline' e sem nonce: todo script e estilo é arquivo local. A única origem externa é a dos tiles,
 * derivada de MAP_TILE_URL e liberada apenas em img-src desta aplicação (não altera a política do portal).
 * HSTS não é emitido aqui: vale para o host inteiro e é decisão do servidor do domínio, não de uma subpasta.
 */
final class SecurityHeaders implements MiddlewareInterface
{
    public function __construct(private readonly string $tileUrl) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $tiles = self::tileOrigin($this->tileUrl);
        $csp = implode('; ', [
            "default-src 'none'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self' data:" . ($tiles === null ? '' : " {$tiles}"),
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'none'",
            "object-src 'none'",
            "manifest-src 'none'",
            "worker-src 'none'",
        ]);
        $response = $response
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin');
        if (!$response->hasHeader('Cache-Control')) {
            $response = $response->withHeader('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /** Origem (esquema://host) do provedor de tiles, ou null se a URL não for https/http válida. */
    public static function tileOrigin(string $url): ?string
    {
        $parts = parse_url(str_replace(['{s}', '{z}', '{x}', '{y}', '{r}'], ['a', '0', '0', '0', ''], $url));
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['https', 'http'], true) || empty($parts['host'])) {
            return null;
        }
        $host = str_contains($url, '{s}') ? '*.' . preg_replace('/^a\./', '', $parts['host']) : $parts['host'];

        return $parts['scheme'] . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
