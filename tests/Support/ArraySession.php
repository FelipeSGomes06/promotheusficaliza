<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

use Fiscaliza\Http\Session;

/** Sessão em memória para testes HTTP (mesmas regras, sem cookies nativos do PHP). */
final class ArraySession extends Session
{
    public int $regenerations = 0;

    public function __construct()
    {
        parent::__construct('PFSESSID', '/promotheusfiscaliza/', false);
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function regenerate(): void
    {
        $this->start();
        $this->regenerations++;
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->started = false;
    }
}
