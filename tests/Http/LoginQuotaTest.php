<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Http;

use Fiscaliza\Application\AuthenticationFailed;
use Fiscaliza\Domain\Time;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Fiscaliza\Tests\Support\Parallel;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Cota de login atômica (achado SEC-01 da auditoria de 18/10/2026): verificações simultâneas não ultrapassam
 * o limite de 5 falhas por usuário. Os casos concorrentes rodam em processos separados, com sessões e
 * tokens CSRF próprios no caso HTTP.
 */
final class LoginQuotaTest extends FirebirdTestCase
{
    private const PASSWORD = 'senha-de-teste-bem-longa';

    public function testVerificationInProgressCountsAgainstTheQuota(): void
    {
        $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', self::PASSWORD);
        $this->priorFailures('ana', 4);
        // Outra conexão reservou a 5ª vaga e ainda está calculando o hash.
        $this->newServices()->userRepository()->reserveAttempt(self::key('ana'), Time::toDb($this->clock->now()));

        $this->expectException(AuthenticationFailed::class);
        $this->expectExceptionMessage('Muitas tentativas');
        $this->services->auth()->attempt('ana', self::PASSWORD, '198.51.100.1');
    }

    public function testSuccessfulLoginSettlesItsOwnReservation(): void
    {
        $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', self::PASSWORD);
        $this->priorFailures('ana', 3);
        $since = Time::toDb($this->clock->now()->modify('-15 minutes'));

        $this->services->auth()->attempt('ana', self::PASSWORD, '198.51.100.1');
        self::assertSame(3, $this->services->userRepository()->failuresSince(self::key('ana'), $since), 'login correto não consome a cota');
        try {
            $this->services->auth()->attempt('ana', 'errada', '198.51.100.1');
        } catch (AuthenticationFailed) {
        }
        self::assertSame(4, $this->services->userRepository()->failuresSince(self::key('ana'), $since));
    }

    public function testRepeatedSuccessfulLoginsAndOlderFailureRowsCoexist(): void
    {
        $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', self::PASSWORD);
        // Falhas gravadas antes da migração 0002 não têm número de vaga.
        $this->services->database()->execute('INSERT INTO LOGIN_ATTEMPT (KEY_HASH, ATTEMPTED_AT) VALUES (?, ?)', [self::key('ana'), Time::toDb($this->clock->now()->modify('-1 minute'))]);
        $this->services->database()->execute('INSERT INTO LOGIN_ATTEMPT (KEY_HASH, ATTEMPTED_AT) VALUES (?, ?)', [self::key('ana'), Time::toDb($this->clock->now()->modify('-1 minute'))]);

        foreach (range(1, 3) as $_) {
            self::assertSame('ana', $this->services->auth()->attempt('ana', self::PASSWORD, '198.51.100.1')['username']);
        }
        self::assertSame(2, $this->services->userRepository()->failuresSince(self::key('ana'), Time::toDb($this->clock->now()->modify('-15 minutes'))), 'só as falhas antigas contam');
    }

    /** @return array<string, array{0: bool}> */
    public static function accounts(): array
    {
        return ['conta existente' => [true], 'conta inexistente' => [false]];
    }

    #[DataProvider('accounts')]
    public function testConcurrentAttemptsNeverExceedTheUserLimit(bool $exists): void
    {
        if ($exists) {
            $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', self::PASSWORD);
        }
        $this->priorFailures('ana', 4);
        $jobs = array_map(static fn(int $i) => ['login', 'ana', 'senha-errada', "198.51.100.{$i}"], range(1, 4));
        $results = Parallel::run($jobs, $this->clock->now());

        $messages = array_count_values(array_column($results, 'message'));
        ksort($messages);
        self::assertSame(['Muitas tentativas. Aguarde 15 minutos e tente novamente.' => 3, 'Usuário ou senha inválidos.' => 1], $messages, json_encode($results, JSON_UNESCAPED_UNICODE) ?: '');
        self::assertSame(5, $this->services->userRepository()->failuresSince(self::key('ana'), Time::toDb($this->clock->now()->modify('-15 minutes'))));
    }

    public function testConcurrentHttpLoginsFromDistinctSessionsWithValidCsrfRespectTheLimit(): void
    {
        $this->services->auth()->createUser('ana', 'Ana', 'OPERADOR', self::PASSWORD);
        $this->priorFailures('ana', 4);
        $results = Parallel::run([['http-login', 'ana', 'senha-errada', '198.51.100.21'], ['http-login', 'ana', 'outra-errada', '198.51.100.22']], $this->clock->now());

        self::assertSame([401, 401], array_column($results, 'status'), 'CSRF aceito nas duas sessões: as duas chegaram à autenticação');
        $messages = array_column($results, 'message');
        sort($messages);
        self::assertSame(['Muitas tentativas', 'Usuário ou senha inválidos.'], $messages, json_encode($results, JSON_UNESCAPED_UNICODE) ?: '');
        self::assertSame(5, $this->services->userRepository()->failuresSince(self::key('ana'), Time::toDb($this->clock->now()->modify('-15 minutes'))));
    }

    private function priorFailures(string $username, int $count): void
    {
        foreach (range(1, $count) as $_) {
            $this->services->userRepository()->reserveAttempt(self::key($username), Time::toDb($this->clock->now()->modify('-1 minute')));
        }
    }

    private static function key(string $username): string
    {
        return hash('sha256', 'u:' . $username);
    }
}
