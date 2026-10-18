<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\UserRepository;

/**
 * Autenticação com password_hash/password_verify do PHP (Argon2id quando disponível, senão bcrypt), limite de
 * tentativas por usuário e por IP (valores guardados como hash) e mensagens que não revelam se a conta existe.
 * O limite vale também para tentativas simultâneas: cada verificação reserva sua vaga antes do hashing.
 */
final class AuthService
{
    public const MAX_FAILURES_PER_USER = 5;
    public const MAX_FAILURES_PER_IP = 20;
    public const WINDOW_MINUTES = 15;
    /** Hash Argon2id de uma senha descartável: verificado quando a conta não existe (tempo de resposta uniforme). */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$WWhpMWNNZUsxOS9oVk5vMA$4E0qZBl0rfvtXtH0oGBRsEatMJIh5Yw17+y1ID3RPHY';

    public function __construct(
        private readonly Database $db,
        private readonly UserRepository $users,
        private readonly Clock $clock,
    ) {}

    public static function hash(#[\SensitiveParameter] string $password): string
    {
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
    }

    /**
     * @return array<string, mixed> conta autenticada
     * @throws AuthenticationFailed credenciais inválidas, conta inativa ou excesso de tentativas
     */
    public function attempt(string $username, #[\SensitiveParameter] string $password, string $ip): array
    {
        $username = mb_substr(trim($username), 0, 40);
        $userKey = hash('sha256', 'u:' . mb_strtolower($username));
        $ipKey = hash('sha256', 'ip:' . $ip);
        $now = $this->clock->now();
        // A vaga é reservada (e confirmada no banco) antes do hashing; ela conta como falha até um login correto quitá-la.
        $reservations = $this->reserve($userKey, $ipKey, Time::toDb($now), Time::toDb($now->modify('-' . self::WINDOW_MINUTES . ' minutes')));

        $user = $username === '' ? null : $this->users->findByUsername($username);
        // Verifica um hash fictício quando a conta não existe, para o tempo de resposta não revelar isso.
        $valid = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);
        if ($user === null || !$valid || $user['active'] !== true) {
            throw new AuthenticationFailed('Usuário ou senha inválidos.');
        }
        $this->db->transaction(function () use ($user, $password, $now, $reservations): void {
            foreach ($reservations as $id) {
                $this->users->settleAttempt($id, Time::toDb($now));
            }
            if (password_needs_rehash($user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)) {
                $this->users->updateHash((int) $user['id'], self::hash($password));
            }
            $this->users->touchLogin((int) $user['id'], Time::toDb($now));
        });
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Confere a cota e reserva uma vaga por usuário e por IP numa transação curta (o hashing fica fora dela).
     * Se outra tentativa simultânea pegou a mesma vaga, recomeça com contagem nova; persistindo a disputa, recusa.
     *
     * @return array{0: int, 1: int} ids das reservas (usuário, IP)
     */
    private function reserve(string $userKey, string $ipKey, string $at, string $since): array
    {
        $blocked = new AuthenticationFailed('Muitas tentativas. Aguarde ' . self::WINDOW_MINUTES . ' minutos e tente novamente.');
        for ($try = 0; $try < 5; $try++) {
            try {
                return $this->db->transaction(function () use ($userKey, $ipKey, $at, $since, $blocked): array {
                    if ($this->users->failuresSince($userKey, $since) >= self::MAX_FAILURES_PER_USER || $this->users->failuresSince($ipKey, $since) >= self::MAX_FAILURES_PER_IP) {
                        throw $blocked;
                    }

                    return [$this->users->reserveAttempt($userKey, $at), $this->users->reserveAttempt($ipKey, $at)];
                });
            } catch (ConstraintViolation $e) {
                if ($e->constraint !== 'UX_LOGIN_ATTEMPT_SLOT') {
                    throw $e;
                }
            } catch (ConcurrencyConflict) {
                // Espera de bloqueio esgotada: trata como disputa e reconta.
            }
        }

        throw $blocked;
    }

    /** @return array<string, mixed>|null conta ativa ou null */
    public function activeUser(int $id): ?array
    {
        $user = $this->users->find($id);
        if ($user === null || $user['active'] !== true) {
            return null;
        }
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Cria conta com senha gerada (mostrada uma única vez) ou informada. Sem cadastro público: só CLI e administrador.
     *
     * @return array{id: int, password: string}
     */
    public function createUser(string $username, string $displayName, string $role, #[\SensitiveParameter] ?string $password = null): array
    {
        $input = new Input(['username' => $username, 'display_name' => $displayName, 'role' => $role]);
        $username = (string) $input->text('username', 40);
        $displayName = (string) $input->text('display_name', 80);
        $role = (string) $input->choice('role', ['OPERADOR', 'ADMIN']);
        if ($username !== '' && preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username) !== 1) {
            $input->fail('username', 'Use 3 a 40 letras, números, ponto, hífen ou sublinhado.');
        }
        if ($password !== null && mb_strlen($password) < 12) {
            $input->fail('password', 'A senha precisa ter ao menos 12 caracteres.');
        }
        $input->assertValid();
        $password ??= rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');

        try {
            $id = $this->db->transaction(fn() => $this->users->insert($username, $displayName, self::hash($password), $role, Time::toDb($this->clock->now())));
        } catch (ConstraintViolation $e) {
            throw $e->constraint === 'UX_APP_USER_USERNAME' ? new ValidationFailed(['username' => 'Nome de usuário já existe.']) : $e;
        }

        return ['id' => $id, 'password' => $password];
    }

    public function setActive(int $id, bool $active, int $actorId): void
    {
        $this->db->transaction(function () use ($id, $active, $actorId): void {
            $user = $this->users->find($id) ?? throw new NotFound('Conta não encontrada.');
            if (!$active && $id === $actorId) {
                throw new RuleViolation('Você não pode desativar a própria conta.');
            }
            if (!$active && $user['role'] === 'ADMIN' && $user['active'] === true && $this->users->countActiveAdmins() <= 1) {
                throw new RuleViolation('É preciso manter ao menos um administrador ativo.');
            }
            $this->users->setActive($id, $active);
        });
    }

    /** @return list<array<string, mixed>> */
    public function users(): array
    {
        return $this->users->all();
    }
}
