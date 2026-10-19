<?php

declare(strict_types=1);

namespace Fiscaliza\Http;

/**
 * Sessão no servidor com nome próprio, cookie HttpOnly/SameSite=Lax, Secure em HTTPS e Path restrito ao prefixo
 * da aplicação (sem Domain). Visitante sem conta é levado ao login sem receber cookie: a sessão só começa quando
 * um formulário (o de login, inclusive) precisa de token CSRF. O Path restrito reduz envio acidental, mas não é
 * fronteira de segurança entre aplicações da mesma origem (ver docs/seguranca.md).
 */
class Session
{
    public const IDLE_SECONDS = 1800;
    public const ABSOLUTE_SECONDS = 28800;

    /** @var array<string, mixed> */
    protected array $data = [];
    protected bool $started = false;

    public function __construct(
        private readonly string $name,
        private readonly string $cookiePath,
        private readonly bool $secure,
    ) {}

    /** @param array<string, mixed> $cookies */
    public function resumeIfPresent(array $cookies): void
    {
        if (isset($cookies[$this->name]) && is_string($cookies[$this->name])) {
            $this->start();
        }
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($this->name);
            session_set_cookie_params(['lifetime' => 0, 'path' => $this->cookiePath, 'secure' => $this->secure, 'httponly' => true, 'samesite' => 'Lax']);
            session_start(['use_strict_mode' => true, 'use_only_cookies' => true, 'cookie_httponly' => true]);
        }
        $this->data = &$_SESSION;
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /** Novo identificador após login (impede fixação de sessão). */
    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        if (!$this->started) {
            return;
        }
        $this->data = [];
        setcookie($this->name, '', ['expires' => time() - 3600, 'path' => $this->cookiePath, 'secure' => $this->secure, 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
        $this->started = false;
    }

    public function csrfToken(): string
    {
        $this->start();
        if (!is_string($this->data['_csrf'] ?? null)) {
            $this->data['_csrf'] = bin2hex(random_bytes(32));
        }

        return $this->data['_csrf'];
    }

    public function validCsrf(mixed $token): bool
    {
        return $this->started && is_string($token) && is_string($this->data['_csrf'] ?? null) && hash_equals($this->data['_csrf'], $token);
    }

    public function flash(string $type, string $message): void
    {
        $this->set('_flash', [...(array) $this->get('_flash', []), ['type' => $type, 'message' => $message]]);
    }

    /** @return list<array{type: string, message: string}> */
    public function takeFlash(): array
    {
        $messages = (array) $this->get('_flash', []);
        $this->remove('_flash');

        return array_values($messages);
    }
}
