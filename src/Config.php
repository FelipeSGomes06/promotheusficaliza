<?php

declare(strict_types=1);

namespace Fiscaliza;

/**
 * Configuração vinda do ambiente. Um arquivo .env opcional (formato CHAVE=valor) é lido sem sobrescrever
 * variáveis reais do processo, para funcionar tanto no Compose quanto em hospedagem sem variáveis de ambiente.
 */
final class Config
{
    /** @param array<string, string> $values */
    public function __construct(private array $values) {}

    public static function load(string $projectRoot): self
    {
        $values = is_file($projectRoot . '/.env') ? self::parseDotEnv((string) file_get_contents($projectRoot . '/.env')) : [];
        foreach (getenv() as $key => $value) {
            $values[$key] = $value;
        }

        return new self($values);
    }

    /** @return array<string, string> */
    public static function parseDotEnv(string $contents): array
    {
        $values = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map(trim(...), explode('=', $line, 2));
            if (preg_match('/^"(.*)"$|^\'(.*)\'$/', $value, $m) === 1) {
                $value = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
            } else {
                $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
            }
            $values[$key] = $value;
        }

        return $values;
    }

    /** @param array<string, string> $overrides */
    public function with(array $overrides): self
    {
        return new self(array_merge($this->values, $overrides));
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->values[$key] ?? $default;
    }

    public function require(string $key): string
    {
        $value = $this->values[$key] ?? '';
        if ($value === '') {
            throw new \RuntimeException("Variável de ambiente obrigatória ausente: {$key}");
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        if (!isset($this->values[$key]) || $this->values[$key] === '') {
            return $default;
        }

        return filter_var($this->values[$key], FILTER_VALIDATE_BOOL);
    }

    public function int(string $key, int $default): int
    {
        $value = filter_var($this->values[$key] ?? '', FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }

    public function environment(): string
    {
        return $this->get('APP_ENV', 'production');
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    /** Prefixo público sem barra final, ex.: "/promotheusfiscaliza" (vazio quando servido na raiz). */
    public function basePath(): string
    {
        $path = rtrim($this->get('APP_BASE_PATH', ''), '/');
        if ($path !== '' && preg_match('#^(/[A-Za-z0-9._-]+)+$#', $path) !== 1) {
            throw new \RuntimeException('APP_BASE_PATH inválido');
        }

        return $path;
    }
}
