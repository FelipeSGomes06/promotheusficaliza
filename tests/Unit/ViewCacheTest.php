<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Em produção os templates ficam compilados em cache; uma atualização do código não pode servir a versão antiga. */
final class ViewCacheTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/view-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/templates', 0777, true);
        mkdir($this->dir . '/public');
        file_put_contents($this->dir . '/render.php', <<<'PHP'
            <?php
            require $argv[1];
            $dir = $argv[2];
            $view = new Fiscaliza\Http\View(new Fiscaliza\Config(['APP_ENV' => 'production', 'APP_BASE_PATH' => '/promotheusfiscaliza']), "$dir/templates", "$dir/public", "$dir/cache");
            $request = (new Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', 'http://localhost/promotheusfiscaliza/entrar');
            echo $view->render($request, new Slim\Psr7\Response(), 'pagina.twig')->getBody();
            PHP);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }

    /** Cada requisição é um processo novo: é assim que o servidor lê o cache depois de uma atualização. */
    private function request(): string
    {
        $command = [PHP_BINARY, $this->dir . '/render.php', dirname(__DIR__, 2) . '/vendor/autoload.php', $this->dir];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        self::assertSame(0, proc_close($process), $err);

        return $out;
    }

    public function testProductionCacheRecompilesATemplateChangedByAnUpdate(): void
    {
        file_put_contents($this->dir . '/templates/pagina.twig', 'Entrar para editar');
        touch($this->dir . '/templates/pagina.twig', time() - 60);
        self::assertSame('Entrar para editar', $this->request());
        self::assertDirectoryExists($this->dir . '/cache', 'produção usa o cache de templates compilados');

        // Atualização (git pull): o arquivo muda depois de compilado; a requisição seguinte mostra a versão nova.
        file_put_contents($this->dir . '/templates/pagina.twig', 'Acesso restrito');
        self::assertSame('Acesso restrito', $this->request());
    }
}
