<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/** Entrada inválida: erros por campo, exibidos junto aos campos do formulário (HTTP 422). */
final class ValidationFailed extends \RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors, string $message = 'Revise os campos destacados.')
    {
        parent::__construct($message);
    }
}
