<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

use PDO;
use PDOException;

/**
 * Conexão PDO_FIREBIRD com as regras comprovadas em docs/evidencias/2026-10-18-prova-firebird.md:
 * timeout de instrução por conexão (o driver espera locks indefinidamente), parâmetros posicionais
 * (marcador nomeado repetido falha) e tradução de erros do Firebird para exceções da aplicação.
 */
final class Database
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $user,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly int $statementTimeoutSeconds = 5,
    ) {}

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO($this->dsn, $this->user, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_CASE => PDO::CASE_LOWER,
                ]);
            } catch (PDOException $e) {
                // A mensagem do driver pode conter DSN/caminho do banco: não propaga.
                throw new DatabaseUnavailable('Banco de dados indisponível', 0, $e);
            }
            $this->pdo->exec('SET STATEMENT TIMEOUT ' . max(1, $this->statementTimeoutSeconds) . ' SECOND');
        }

        return $this->pdo;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Executa $work numa transação curta. Com $snapshot = true usa SNAPSHOT (REPEATABLE_READ) somente leitura,
     * para que todas as consultas de uma tela vejam o mesmo instante do banco.
     *
     * @template T
     * @param callable(Database): T $work
     * @return T
     */
    public function transaction(callable $work, bool $snapshot = false): mixed
    {
        $pdo = $this->pdo();
        if ($snapshot) {
            $pdo->setAttribute(\Pdo\Firebird::TRANSACTION_ISOLATION_LEVEL, \Pdo\Firebird::REPEATABLE_READ);
            $pdo->setAttribute(\Pdo\Firebird::WRITABLE_TRANSACTION, false);
        }
        try {
            $pdo->beginTransaction();
            try {
                $result = $work($this);
                $pdo->commit();

                return $result;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e instanceof PDOException ? self::translate($e) : $e;
            }
        } finally {
            if ($snapshot) {
                $pdo->setAttribute(\Pdo\Firebird::TRANSACTION_ISOLATION_LEVEL, \Pdo\Firebird::READ_COMMITTED);
                $pdo->setAttribute(\Pdo\Firebird::WRITABLE_TRANSACTION, true);
            }
        }
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        /** @var list<array<string, mixed>> */
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * @param list<mixed> $params
     * @return array<string, mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param list<mixed> $params */
    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * Executa INSERT … RETURNING ID e devolve o identificador gerado pelo Firebird.
     *
     * @param list<mixed> $params
     */
    public function insert(string $sql, array $params): int
    {
        return (int) $this->run($sql . ' RETURNING ID', $params)->fetchColumn();
    }

    /**
     * @param list<mixed> $params
     * @return int linhas afetadas
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /** @param list<mixed> $params */
    private function run(string $sql, array $params): \PDOStatement
    {
        try {
            $statement = $this->pdo()->prepare($sql);
            foreach ($params as $i => $value) {
                $statement->bindValue($i + 1, $value, match (true) {
                    $value === null => PDO::PARAM_NULL,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_int($value) => PDO::PARAM_INT,
                    default => PDO::PARAM_STR,
                });
            }
            $statement->execute();

            return $statement;
        } catch (PDOException $e) {
            throw self::translate($e);
        }
    }

    /** "?, ?, ?" para listas IN; os valores sempre seguem como parâmetros. */
    public static function placeholders(int $count): string
    {
        return implode(', ', array_fill(0, max(1, $count), '?'));
    }

    public static function translate(PDOException $e): \RuntimeException
    {
        $message = $e->getMessage();
        if ($e->getCode() === '23000' || str_contains($message, 'violation of') || str_contains($message, 'duplicate value')) {
            preg_match('/(?:constraint|index) "([A-Z0-9_]+)"/', $message, $m);

            return new ConstraintViolation($m[1] ?? 'desconhecida', $e);
        }
        if ($e->getCode() === 'HY008' || str_contains($message, 'timeout') || str_contains($message, 'lock conflict') || str_contains($message, 'update conflicts')) {
            return new ConcurrencyConflict('Registro ocupado por outra operação', 0, $e);
        }

        return new \RuntimeException('Falha ao acessar o banco de dados', 0, $e);
    }
}
