<?php

declare(strict_types=1);

namespace Fiscaliza\Http;

use Fiscaliza\Config;
use Fiscaliza\Domain\Time;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Renderização Twig com escape automático de HTML. Todo texto vindo do banco (endereços, séries, observações)
 * é tratado como não confiável; templates nunca usam |raw com dados externos.
 */
final class View
{
    private Environment $twig;

    public function __construct(private readonly Config $config, string $templates, private readonly string $publicDir, ?string $cache)
    {
        $this->twig = new Environment(new FilesystemLoader($templates), [
            'autoescape' => 'html',
            'strict_variables' => !$config->isProduction(),
            'cache' => $cache ?? false,
            // Com cache (produção), recompila o template cujo arquivo mudou: uma atualização por "git pull" não pode
            // continuar servindo os templates compilados da versão anterior.
            'auto_reload' => true,
        ]);
        $base = $config->basePath();
        $this->twig->addFunction(new TwigFunction('url', static function (string $path = '/', array $query = []) use ($base): string {
            $query = array_filter($query, static fn($v) => $v !== null && $v !== '' && $v !== []);

            return $base . $path . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }));
        $this->twig->addFunction(new TwigFunction('asset', function (string $path) use ($base): string {
            $file = $this->publicDir . '/assets/' . ltrim($path, '/');
            $version = is_file($file) ? substr(md5((string) filemtime($file)), 0, 8) : '0';

            return "{$base}/assets/{$path}?v={$version}";
        }));
        // Versão de uma pasta (pelo arquivo mais recente), para URLs que o navegador monta, como as miniaturas da central.
        $this->twig->addFunction(new TwigFunction('asset_dir_version', function (string $dir): string {
            $mtimes = array_map(filemtime(...), glob($this->publicDir . '/assets/' . trim($dir, '/') . '/*') ?: []);

            return $mtimes === [] ? '0' : substr(md5((string) max($mtimes)), 0, 8);
        }));
        $vite = new ViteAssets($this->publicDir . '/assets/build', $base . '/assets/build');
        $this->twig->addFunction(new TwigFunction('vite_styles', $vite->styles(...), ['is_safe' => ['html']]));
        $this->twig->addFunction(new TwigFunction('vite_script', $vite->script(...), ['is_safe' => ['html']]));
        $this->twig->addFilter(new TwigFilter('local_dt', static function (mixed $value, string $format = 'd/m/Y H:i'): string {
            $instant = $value instanceof \DateTimeImmutable ? $value : (is_string($value) && str_contains($value, 'T') ? new \DateTimeImmutable($value) : Time::fromDb($value));

            return $instant === null ? '—' : $instant->setTimezone(Time::local())->format($format);
        }));
        $this->twig->addFilter(new TwigFilter('br_date', static function (mixed $value): string {
            return is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m) === 1 ? "{$m[3]}/{$m[2]}/{$m[1]}" : '—';
        }));
        $this->twig->addFilter(new TwigFilter('pct', static fn(?float $v): string => $v === null ? 'não se aplica' : number_format($v * 100, 1, ',', '.') . '%'));
    }

    /** @param array<string, mixed> $data */
    public function render(ServerRequestInterface $request, ResponseInterface $response, string $template, array $data = [], int $status = 200): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $session = $session instanceof Session ? $session : null;
        $data['app'] = [
            'base' => $this->config->basePath(),
            'path' => substr($request->getUri()->getPath(), strlen($this->config->basePath())) ?: '/',
            'user' => $request->getAttribute('user'),
            'flash' => $session?->isStarted() ? $session->takeFlash() : [],
            'csrf' => $session !== null && ($session->isStarted() || $request->getAttribute('user') !== null || ($data['needs_csrf'] ?? false)) ? $session->csrfToken() : '',
            'map' => ['tiles' => $this->config->get('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'), 'attribution' => $this->config->get('MAP_TILE_ATTRIBUTION', '&copy; colaboradores do OpenStreetMap')],
            'poll_seconds' => max(3, $this->config->int('POLL_INTERVAL_SECONDS', 5)),
            'sim_seconds' => max(2, $this->config->int('SIM_INTERVAL_SECONDS', 5)),
        ];
        $response->getBody()->write($this->twig->render($template, $data));

        return $response->withStatus($status)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
