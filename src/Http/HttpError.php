<?php

declare(strict_types=1);

namespace Fiscaliza\Http;

/** Erro HTTP com mensagem segura para exibir ao usuário. */
final class HttpError extends \RuntimeException
{
    public function __construct(public readonly int $status, string $publicMessage)
    {
        parent::__construct($publicMessage);
    }
}
