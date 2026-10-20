<?php

declare(strict_types=1);

// Landing pública do Promotheus Fiscaliza, servida em "/". Não usa banco, sessão nem cookies: só monta o HTML.
// Configuração e template ficam fora deste diretório público (landing/config.php e landing/pagina.php).

$config = require dirname(__DIR__) . '/config.php';

/** Escapa um valor para HTML (texto ou atributo). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** URL de um arquivo da landing com versão pela data de modificação (o navegador pode guardá-lo em cache). */
function asset(string $caminho): string
{
    $arquivo = __DIR__ . '/landing-assets/promotheus/' . $caminho;

    return '/landing-assets/promotheus/' . $caminho . (is_file($arquivo) ? '?v=' . filemtime($arquivo) : '');
}

/** URL de um arquivo gerado pelo build (nome com hash), a partir do manifest.json do Vite. */
function construido(string $fonte): string
{
    static $manifesto = null;
    $manifesto ??= json_decode((string) file_get_contents(__DIR__ . '/landing-assets/promotheus/build/manifest.json'), true);

    return '/landing-assets/promotheus/build/' . $manifesto[$fonte]['file'];
}

// Vídeo só entra quando o arquivo existe nesta landing (a CSP aceita mídia só do próprio site): um caminho ausente
// não vira player quebrado nem requisição 404.
$video = $config['video'];
$existe = static fn (?string $src): bool => $src !== null && str_starts_with($src, '/')
    && !str_contains($src, '..') && is_file(__DIR__ . $src);
$video['ativo'] = $existe($video['src']);
foreach (['legendas', 'capa'] as $campo) {
    if (!$existe($video[$campo])) {
        $video[$campo] = null;
    }
}

header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; media-src 'self'; connect-src 'self'; form-action 'none'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cache-Control: no-cache');
header('Content-Type: text/html; charset=utf-8');

require dirname(__DIR__) . '/pagina.php';
