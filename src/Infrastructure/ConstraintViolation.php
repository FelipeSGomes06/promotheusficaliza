<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

/** Violação de restrição do Firebird (UNIQUE, FK, CHECK); $constraint é o nome da restrição ou índice. */
final class ConstraintViolation extends \RuntimeException
{
    public function __construct(public readonly string $constraint, ?\Throwable $previous = null)
    {
        parent::__construct("Restrição violada: {$constraint}", 0, $previous);
    }
}
