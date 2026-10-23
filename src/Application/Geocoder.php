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
     * @param string $city cidade informada; o provedor continua preso à área atendida (Marília e arredores)
     * @return array{lat: string, lng: string}|null graus decimais com 6 casas; null se a rua não for achada na área atendida
     * @throws ProviderUnavailable quando o provedor não responde a tempo, falha ou responde fora do formato esperado
     */
    public function locate(string $street, string $city = 'Marília'): ?array;
}
