<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

use Fiscaliza\Application\CepAddress;
use Fiscaliza\Application\CepLookup;
use Fiscaliza\Application\ProviderUnavailable;

/** Falso da consulta de CEP: respostas fixas por CEP, registro das chamadas e provedor fora do ar sob encomenda. */
final class FakeCepLookup implements CepLookup
{
    /** @var list<string> CEPs consultados, na ordem */
    public array $calls = [];

    /** @param array<string, CepAddress> $answers por CEP (00000-000); CEP ausente = não encontrado */
    public function __construct(private readonly array $answers = [], public bool $down = false) {}

    public function find(string $cep): ?CepAddress
    {
        $this->calls[] = $cep;
        if ($this->down) {
            throw new ProviderUnavailable('provedor fora do ar (falso dos testes)');
        }

        return $this->answers[$cep] ?? null;
    }
}
