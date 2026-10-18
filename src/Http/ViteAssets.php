<?php

declare(strict_types=1);

namespace Fiscaliza\Http;

/**
 * Lê o manifesto gerado por "npm run build" (public/assets/build/manifest.json) e escreve as tags de CSS e do
 * módulo de entrada. Os nomes têm hash de conteúdo; o prefixo vem de APP_BASE_PATH, então o mesmo build serve
 * em /promotheusfiscaliza e na origem dedicada. Produção só lê arquivos estáticos: não há servidor Node.
 */
final class ViteAssets
{
    public const STYLES = 'resources/css/app.css';
    public const SCRIPT = 'resources/js/app.js';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $manifest = null;

    public function __construct(private readonly string $buildDir, private readonly string $publicUrl) {}

    public function styles(): string
    {
        $tags = [$this->link($this->entry(self::STYLES)['file'])];
        foreach ($this->entry(self::SCRIPT)['css'] ?? [] as $css) {
            $tags[] = $this->link((string) $css);
        }

        return implode("\n  ", $tags);
    }

    public function script(): string
    {
        return '<script type="module" src="' . $this->url($this->entry(self::SCRIPT)['file']) . '"></script>';
    }

    /** @return array<string, mixed> */
    private function entry(string $name): array
    {
        $this->manifest ??= $this->load();
        $entry = $this->manifest[$name] ?? null;
        if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
            throw new \RuntimeException("Entrada {$name} ausente do manifesto de assets. Rode \"npm run build\".");
        }

        return $entry;
    }

    /** @return array<string, array<string, mixed>> */
    private function load(): array
    {
        $file = $this->buildDir . '/manifest.json';
        $json = is_file($file) ? file_get_contents($file) : false;
        $data = $json === false ? null : json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Manifesto de assets ausente ou inválido (public/assets/build/manifest.json). Rode "npm run build".');
        }

        return $data;
    }

    private function link(mixed $file): string
    {
        return '<link rel="stylesheet" href="' . $this->url($file) . '">';
    }

    private function url(mixed $file): string
    {
        if (!is_string($file) || preg_match('#^[A-Za-z0-9._/-]+$#', $file) !== 1 || str_contains($file, '..')) {
            throw new \RuntimeException('Nome de arquivo inesperado no manifesto de assets.');
        }

        return htmlspecialchars($this->publicUrl . '/' . $file, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
