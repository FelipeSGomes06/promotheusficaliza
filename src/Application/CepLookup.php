<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/**
 * Consulta de CEP num provedor externo (ADR-0021). Só o CEP sai do servidor; da resposta, o cadastro grava no máximo
 * a rua. Os testes injetam um falso em Services: nenhum teste consulta a rede.
 */
interface CepLookup
{
    /**
     * @param string $cep já validado, no formato 00000-000
     * @return CepAddress|null null quando o provedor responde que o CEP não existe
     * @throws ProviderUnavailable quando o provedor não responde a tempo, falha ou responde fora do formato esperado
     */
    public function find(string $cep): ?CepAddress;
}
