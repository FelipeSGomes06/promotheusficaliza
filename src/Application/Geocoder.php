<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/**
 * Ponto de uma rua de Marília/SP num provedor externo (ADR-0021): o caminho de quem não consegue marcar a base no
 * mapa. Só o nome da rua sai do servidor. Os testes injetam um falso em Services: nenhum teste consulta a rede.
 */
interface Geocoder
{
    /**
     * @return array{lat: string, lng: string, box?: array{0: float, 1: float, 2: float, 3: float}}|null graus decimais com 6
     *         casas e, se o provedor der, o retângulo do lugar achado [sul, norte, oeste, leste]; null se não for achado
     * @throws ProviderUnavailable quando o provedor não responde a tempo, falha ou responde fora do formato esperado
     */
    public function locate(string $street): ?array;
}
