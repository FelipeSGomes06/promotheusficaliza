<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/** Endereço de um CEP como o provedor devolveu. Só a rua (e o CEP) podem ir para um cadastro; o resto é para a tela. */
final readonly class CepAddress
{
    public function __construct(
        public string $cep,
        public string $street,
        public string $district,
        public string $city,
        public string $uf,
    ) {}

    /** Os cadastros são de Marília/SP: um CEP de outra cidade não preenche a rua. */
    public function isInMarilia(): bool
    {
        return $this->uf === 'SP' && mb_strtolower($this->city) === 'marília';
    }
}
