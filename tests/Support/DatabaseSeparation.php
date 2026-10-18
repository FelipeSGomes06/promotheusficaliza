<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

/**
 * Evidência de que o banco de testes não é o principal, antes de qualquer operação destrutiva dos testes.
 * Compara o arquivo indicado nos DSNs e, quando o principal está acessível, o arquivo físico informado pelo
 * próprio Firebird (MON$DATABASE_NAME) — dois apelidos diferentes podem apontar para o mesmo arquivo.
 */
final class DatabaseSeparation
{
    /** @return string|null motivo da recusa, ou null se a separação foi demonstrada */
    public static function problem(string $mainDsn, string $testDsn, ?string $mainFile = null, ?string $testFile = null): ?string
    {
        if (trim($testDsn) === '') {
            return 'DB_TEST_DSN não definido.';
        }
        if (trim($mainDsn) !== '' && self::target($mainDsn) === self::target($testDsn)) {
            return 'DB_TEST_DSN aponta para o mesmo banco de DB_DSN.';
        }
        if ($mainFile !== null && $testFile !== null && strcasecmp(trim($mainFile), trim($testFile)) === 0) {
            return 'DB_TEST_DSN e DB_DSN abrem o mesmo arquivo no servidor Firebird.';
        }

        return null;
    }

    /** "host/porta:caminho" em minúsculas, sem charset e demais opções. */
    private static function target(string $dsn): string
    {
        return preg_match('/dbname=([^;]+)/i', $dsn, $m) === 1 ? strtolower(trim($m[1])) : strtolower(trim($dsn));
    }
}
