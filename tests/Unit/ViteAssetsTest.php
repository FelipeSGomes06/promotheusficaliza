<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Http\ViteAssets;
use PHPUnit\Framework\TestCase;

final class ViteAssetsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vite-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/manifest.json');
        @rmdir($this->dir);
    }

    /** @param array<string, mixed> $manifest */
    private function assets(array $manifest, string $base = '/promotheusfiscaliza/assets/build'): ViteAssets
    {
        file_put_contents($this->dir . '/manifest.json', json_encode($manifest));

        return new ViteAssets($this->dir, $base);
    }

    public function testTagsPointToHashedFilesUnderTheConfiguredPrefix(): void
    {
        $assets = $this->assets([
            ViteAssets::STYLES => ['file' => 'styles-AbC123.css', 'isEntry' => true],
            ViteAssets::SCRIPT => ['file' => 'app-Xy_9.js', 'isEntry' => true, 'css' => ['extra-1.css']],
        ]);

        self::assertSame("<link rel=\"stylesheet\" href=\"/promotheusfiscaliza/assets/build/styles-AbC123.css\">\n  <link rel=\"stylesheet\" href=\"/promotheusfiscaliza/assets/build/extra-1.css\">", $assets->styles());
        self::assertSame('<script type="module" src="/promotheusfiscaliza/assets/build/app-Xy_9.js"></script>', $assets->script());
        // Origem dedicada (prefixo vazio): o mesmo build serve na raiz.
        self::assertStringContainsString('src="/assets/build/app-Xy_9.js"', $this->assets([ViteAssets::SCRIPT => ['file' => 'app-Xy_9.js']], '/assets/build')->script());
    }

    public function testRejectsUnexpectedFileNamesAndMissingManifest(): void
    {
        $assets = $this->assets([ViteAssets::SCRIPT => ['file' => '../segredo.js']]);
        try {
            $assets->script();
            self::fail('caminho com ".." aceito');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('inesperado', $e->getMessage());
        }
        $this->expectException(\RuntimeException::class);
        (new ViteAssets($this->dir . '/nao-existe', '/x'))->styles();
    }
}
