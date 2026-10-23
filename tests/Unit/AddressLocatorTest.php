<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Application\AddressLocator;
use Fiscaliza\Application\CepAddress;
use Fiscaliza\Application\ProviderUnavailable;
use Fiscaliza\Config;
use Fiscaliza\Infrastructure\NominatimGeocoder;
use Fiscaliza\Tests\Support\FakeCepLookup;
use Fiscaliza\Tests\Support\FakeGeocoder;
use PHPUnit\Framework\TestCase;

/** Ponto da base pelo endereço: cliente do Nominatim com transporte falso e a junção CEP → rua → ponto. Sem rede. */
final class AddressLocatorTest extends TestCase
{
    private const BASE = 'https://nominatim.exemplo';

    /** @var list<array{0: string, 1: int}> */
    private array $requests = [];

    /** @param array{0: int, 1: string|false, 2: int} $reply */
    private function geocoder(array $reply): NominatimGeocoder
    {
        return new NominatimGeocoder(self::BASE, function (string $url, int $timeout) use ($reply): array {
            $this->requests[] = [$url, $timeout];

            return $reply;
        });
    }

    public function testStreetInMariliaBecomesAPointAndOnlyTheStreetGoesOut(): void
    {
        $point = $this->geocoder([200, '[{"place_id":1,"lat":"-22.2139","lon":"-49.9458","display_name":"Avenida Sampaio Vidal, Centro, Marília"}]', 0])->locate('Avenida Sampaio Vidal');

        self::assertSame(['lat' => '-22.213900', 'lng' => '-49.945800'], $point);
        [$url, $timeout] = $this->requests[0];
        self::assertSame(3, $timeout);
        self::assertStringStartsWith(self::BASE . '/search?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('Avenida Sampaio Vidal', $query['street'] ?? null);
        self::assertSame(['Marília', 'São Paulo', 'br', '1', '1', 'jsonv2'], [$query['city'], $query['state'], $query['countrycodes'], $query['bounded'], $query['limit'], $query['format']]);
        self::assertSame('-50.2,-22,-49.7,-22.4', $query['viewbox'], 'busca presa ao retângulo de Marília');
        self::assertSame(['street', 'city', 'state', 'country', 'countrycodes', 'viewbox', 'bounded', 'format', 'limit', 'accept-language'], array_keys($query), 'nada além da rua e de Marília');
    }

    public function testStreetNotFoundOrOutsideTheAreaIsNotFound(): void
    {
        self::assertNull($this->geocoder([200, '[]', 0])->locate('Rua Inexistente'));
        self::assertNull($this->geocoder([200, '[{"lat":"-23.5505","lon":"-46.6333"}]', 0])->locate('Praça da Sé'), 'fora da área atendida');
    }

    public function testProviderDownTimeoutOrOddAnswersAreUnavailable(): void
    {
        foreach ([[503, 'x', 0], [403, 'Access blocked', 0], [429, '', 0], [200, '{"erro":1}', 0], [200, '[{"lat":"x","lon":"y"}]', 0], [0, false, 6], [0, false, CURLE_OPERATION_TIMEDOUT]] as $reply) {
            try {
                $this->geocoder($reply)->locate('Avenida Sampaio Vidal');
                self::fail('esperava indisponibilidade para ' . json_encode($reply));
            } catch (ProviderUnavailable $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testLocatorUsesTheTypedStreetOrTheStreetOfTheCep(): void
    {
        $cep = new FakeCepLookup([
            '17501-000' => new CepAddress('17501-000', 'Avenida Sampaio Vidal', 'Centro', 'Marília', 'SP'),
            '17500-000' => new CepAddress('17500-000', '', '', 'Marília', 'SP'),
            '01001-000' => new CepAddress('01001-000', 'Praça da Sé', 'Sé', 'São Paulo', 'SP'),
        ]);
        $geo = new FakeGeocoder(['Avenida Sampaio Vidal' => ['lat' => '-22.213900', 'lng' => '-49.945800'], 'Rua Paraíba' => ['lat' => '-22.226000', 'lng' => '-49.930000']]);
        $locator = new AddressLocator($cep, $geo);

        self::assertSame(['situacao' => 'ENCONTRADO', 'lat' => '-22.213900', 'lng' => '-49.945800', 'rua' => 'Avenida Sampaio Vidal'], $locator->locate(null, '17501-000'), 'rua do CEP');
        self::assertSame('-22.226000', $locator->locate(' Rua Paraíba ', '17501-000')['lat'] ?? null, 'a rua informada vale mais que a do CEP');
        self::assertSame(['17501-000'], $cep->calls, 'com a rua informada, o CEP nem é consultado');
        self::assertSame(['situacao' => 'SEM_RUA'], $locator->locate('', '17500-000'), 'CEP geral não indica a rua');
        self::assertSame(['situacao' => 'SEM_RUA'], $locator->locate(null, '01001-000'), 'rua de outra cidade não serve');
        self::assertSame(['situacao' => 'SEM_RUA'], $locator->locate(null, '99999-999'));
        self::assertSame(['situacao' => 'NAO_ENCONTRADO'], $locator->locate('Rua Que Não Existe', null));
        self::assertSame('-22.213900', $locator->locate('Avenida Sampaio Vidal', null, '1200 fundos')['lat'] ?? null, 'casa não achada: cai para a rua');
        self::assertSame(['Avenida Sampaio Vidal', 'Rua Paraíba', 'Rua Que Não Existe', '1200 Avenida Sampaio Vidal', 'Avenida Sampaio Vidal'], $geo->calls);

        $geo->down = true;
        self::assertSame(['situacao' => 'INDISPONIVEL'], $locator->locate('Avenida Sampaio Vidal', null));
        $cep->down = true;
        self::assertSame(['situacao' => 'INDISPONIVEL'], $locator->locate(null, '17501-000'));
    }

    public function testGeocoderAddressComesFromConfigAndMustBeHttps(): void
    {
        self::assertSame('https://nominatim.openstreetmap.org', (new Config([]))->geocoderUrl(), 'padrão: Nominatim');
        self::assertSame('https://geo.exemplo/nominatim', (new Config(['GEOCODER_URL' => 'https://geo.exemplo/nominatim/']))->geocoderUrl());
        $this->expectException(\RuntimeException::class);
        (new Config(['GEOCODER_URL' => 'http://nominatim.openstreetmap.org']))->geocoderUrl();
    }
}
