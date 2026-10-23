<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Bairro de referência mais perto de um ponto: o de centro mais próximo em linha reta sobre os graus (sem correção
 * pela latitude, suficiente dentro de Marília). Não há limite de bairro no banco, só o centro (REGION.CENTER_*).
 * Usado pelo Planejar ("perto de …") e para derivar o bairro da base de um agente pelo ponto marcado no mapa.
 */
final class NearestRegion
{
    /**
     * Bairros do mais perto ao mais longe; em empate, mantém a ordem recebida.
     *
     * @param list<array<string, mixed>> $regions linhas com center_lat e center_lng
     * @return list<array<string, mixed>>
     */
    public static function sorted(float $lat, float $lng, array $regions): array
    {
        usort($regions, static fn(array $a, array $b): int => hypot((float) $a['center_lat'] - $lat, (float) $a['center_lng'] - $lng)
            <=> hypot((float) $b['center_lat'] - $lat, (float) $b['center_lng'] - $lng));

        return $regions;
    }

    /**
     * @param list<array<string, mixed>> $regions
     * @return array<string, mixed>|null null quando não há bairro de referência cadastrado
     */
    public static function of(float $lat, float $lng, array $regions): ?array
    {
        return self::sorted($lat, $lng, $regions)[0] ?? null;
    }
}
