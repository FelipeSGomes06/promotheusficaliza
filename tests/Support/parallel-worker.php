<?php

declare(strict_types=1);

// Processo de um caso concorrente (ver Parallel::run). Usa somente o banco de testes, com a identidade da aplicação.
// Uso: parallel-worker.php <dir> <n> <agora-ISO> <caso> [args...]  → imprime um JSON com o resultado.

use Fiscaliza\Domain\Clock;
use Fiscaliza\Http\WebApp;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\ArraySession;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

require __DIR__ . '/../bootstrap.php';

[, $dir, $n, $now, $case] = $argv;
$args = array_slice($argv, 5);
$services = new Services(FirebirdTestCase::config(), dirname(__DIR__, 2), null, new Clock(new DateTimeImmutable($now)));
$services->database()->pdo(); // conecta antes da largada

$session = new ArraySession();
$http = static function (string $method, string $path, array $body, string $ip) use ($services, $session) {
    $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost/promotheusfiscaliza' . $path, ['REMOTE_ADDR' => $ip]);
    if ($body !== []) {
        $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    if ($session->isStarted()) {
        $request = $request->withCookieParams(['PFSESSID' => 'sessao-' . getmypid()]);
    }

    return WebApp::create($services, $session)->handle($request);
};

$action = match ($case) {
    'start' => static fn() => $services->maintenance()->start((int) $args[0], (int) $args[1]),
    'archive' => static fn() => $services->equipment()->archive((int) $args[0], 'arquivamento concorrente de teste', (int) $args[1], ($args[2] ?? '0') === '1'),
    'login' => static fn() => $services->auth()->attempt($args[0], $args[1], $args[2]),
    // Dois gestores confirmando o mesmo agente: args = execução, setor, agente, conta.
    'mission' => static fn() => $services->planning()->confirm(['run_id' => $args[0], 'sector_id' => $args[1], 'method' => 'baseline', 'agent_id' => $args[2],
        'confirm' => '1', 'justification' => 'teste concorrente', 'note' => "gestor {$n}"], (int) $args[3]),
    'http-login' => (static function () use ($http, $args) {
        // Sessão própria e token CSRF obtidos do formulário real, antes da largada.
        $form = (string) $http('GET', '/entrar', [], $args[2])->getBody();
        preg_match('/name="_csrf" value="([0-9a-f]+)"/', $form, $m);
        $token = $m[1] ?? '';

        return static fn() => $http('POST', '/entrar', ['_csrf' => $token, 'username' => $args[0], 'password' => $args[1]], $args[2]);
    })(),
    default => throw new InvalidArgumentException("Caso desconhecido: {$case}"),
};

touch("{$dir}/pronto-{$n}");
$deadline = microtime(true) + 20;
while (!is_file("{$dir}/largada") && microtime(true) < $deadline) {
    usleep(200);
}

try {
    $result = $action();
    $out = ['ok' => true];
    if ($result instanceof Psr\Http\Message\ResponseInterface) {
        $body = (string) $result->getBody();
        $out = ['ok' => $result->getStatusCode() === 303, 'status' => $result->getStatusCode(),
            'message' => str_contains($body, 'Muitas tentativas') ? 'Muitas tentativas' : (str_contains($body, 'Usuário ou senha inválidos') ? 'Usuário ou senha inválidos.' : '')];
    }
} catch (Throwable $e) {
    $out = ['ok' => false, 'class' => (new ReflectionClass($e))->getShortName(), 'message' => $e->getMessage()];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE), PHP_EOL;
