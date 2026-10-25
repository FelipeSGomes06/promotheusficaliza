<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

use Fiscaliza\Services;

/**
 * Corrida com ordem controlada: cada caso roda o código real do serviço numa Fiber, com conexão própria, e pausa logo
 * depois da primeira consulta que casar com um padrão. O teste decide quem continua, sem sleep. Usar só quando nenhum
 * dos lados precisa esperar lock do outro (o processo é um só); para espera de lock, ver Parallel.
 */
final class Interleaving
{
    /** @var array<int, string> id da Fiber => padrão da consulta em que ela pausa */
    private static array $pauses = [];

    /** Liga a pausa nas consultas desta conexão. */
    public static function connect(Services $services): Services
    {
        $services->database()->pdo()->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PausingStatement::class, []]);

        return $services;
    }

    /**
     * @param callable(): mixed $work
     * @return array{0: \Fiber, 1: \ArrayObject<string, mixed>} a Fiber (ainda não iniciada) e o resultado: ok, error (classe curta)
     */
    public static function case(callable $work, string $pauseAfter): array
    {
        $result = new \ArrayObject(['ok' => null, 'error' => null, 'message' => null]);
        $fiber = new \Fiber(static function () use ($work, $result): void {
            try {
                $work();
                $result['ok'] = true;
            } catch (\Throwable $e) {
                $result['ok'] = false;
                $result['error'] = (new \ReflectionClass($e))->getShortName();
                $result['message'] = $e->getMessage();
            }
        });
        self::$pauses[spl_object_id($fiber)] = $pauseAfter;

        return [$fiber, $result];
    }

    /** @internal chamado por PausingStatement */
    public static function after(string $sql): void
    {
        $fiber = \Fiber::getCurrent();
        $id = $fiber === null ? null : spl_object_id($fiber);
        if ($id === null || !isset(self::$pauses[$id]) || preg_match(self::$pauses[$id], $sql) !== 1) {
            return;
        }
        unset(self::$pauses[$id]);
        \Fiber::suspend();
    }
}

/** @internal */
class PausingStatement extends \PDOStatement
{
    protected function __construct() {}

    public function execute(?array $params = null): bool
    {
        $ok = parent::execute($params);
        Interleaving::after($this->queryString);

        return $ok;
    }
}
