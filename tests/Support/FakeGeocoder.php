<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Support;

use Fiscaliza\Application\Geocoder;
use Fiscaliza\Application\ProviderUnavailable;

/** Falso da localização pelo endereço: pontos fixos por rua, registro das chamadas e provedor fora do ar sob encomenda. */
final class FakeGeocoder implements Geocoder
{
    /** @var list<string> ruas consultadas, na ordem */
    public array $calls = [];

    /** @param array<string, array{lat: string, lng: string}> $points por rua; rua ausente = não achada */
    public function __construct(private readonly array $points = [], public bool $down = false) {}

    public function locate(string $street): ?array
    {
        $this->calls[] = $street;
        if ($this->down) {
            throw new ProviderUnavailable('provedor fora do ar (falso dos testes)');
        }

        return $this->points[$street] ?? null;
    }
}
