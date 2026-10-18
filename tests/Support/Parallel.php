<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

/**
 * Concorrência real para os testes: cada caso roda num processo PHP próprio (conexão própria com o banco de testes),
 * prepara-se, avisa que está pronto e só então todos largam juntos. Ver parallel-worker.php para os casos.
 */
final class Parallel
{
    /**
     * @param list<list<string>> $jobs nome do caso seguido dos argumentos
     * @return list<array{ok: bool, class?: string, message?: string, status?: int}>
     */
    public static function run(array $jobs, \DateTimeImmutable $now): array
    {
        $dir = sys_get_temp_dir() . '/fiscaliza-paralelo-' . bin2hex(random_bytes(6));
        mkdir($dir, 0700);
        $processes = [];
        foreach ($jobs as $i => $job) {
            $command = [PHP_BINARY, __DIR__ . '/parallel-worker.php', $dir, (string) $i, $now->format(DATE_ATOM), ...$job];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('Não foi possível iniciar o processo de teste.');
            }
            $processes[$i] = [$process, $pipes];
        }
        $deadline = microtime(true) + 20;
        while (count(glob($dir . '/pronto-*') ?: []) < count($jobs) && microtime(true) < $deadline) {
            usleep(2000);
        }
        touch($dir . '/largada');

        $results = [];
        foreach ($processes as $i => [$process, $pipes]) {
            $out = (string) stream_get_contents($pipes[1]);
            $err = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $decoded = json_decode(trim($out), true);
            $results[$i] = is_array($decoded) ? $decoded : ['ok' => false, 'class' => 'processo', 'message' => trim($out . ' ' . $err)];
        }
        array_map(unlink(...), glob($dir . '/*') ?: []);
        rmdir($dir);

        /** @var list<array{ok: bool, class?: string, message?: string, status?: int}> */
        return $results;
    }
}
