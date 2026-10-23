<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Application\CepAddress;
use Fiscaliza\Application\ProviderUnavailable;
use Fiscaliza\Config;
use Fiscaliza\Infrastructure\ViaCepClient;
use PHPUnit\Framework\TestCase;

/** Cliente do ViaCEP com transporte falso: nenhuma requisição sai daqui. */
final class CepLookupTest extends TestCase
{
    private const BASE = 'https://viacep.exemplo/ws';

    /** @var list<array{0: string, 1: int}> */
    private array $requests = [];

    /** @param array{0: int, 1: string|false, 2: int} $reply */
    private function client(array $reply): ViaCepClient
    {
        return new ViaCepClient(self::BASE, function (string $url, int $timeout) use ($reply): array {
            $this->requests[] = [$url, $timeout];

            return $reply;
        });
    }

    public function testValidCepReturnsStreetAndOnlyTheDigitsGoOutWithAShortTimeout(): void
    {
        $body = '{"cep":"17501-000","logradouro":"Avenida Sampaio Vidal","complemento":"até 999/1000","unidade":"","bairro":"Centro","localidade":"Marília","uf":"SP","estado":"São Paulo","ibge":"3529005","ddd":"14"}';
        $address = $this->client([200, $body, 0])->find('17501-000');

        self::assertEquals(new CepAddress('17501-000', 'Avenida Sampaio Vidal', 'Centro', 'Marília', 'SP'), $address);
        self::assertTrue($address?->isInMarilia());
        self::assertSame([[self::BASE . '/17501000/json/', 3]], $this->requests, 'só os 8 dígitos na URL; tempo limite de 3 s');
    }

    public function testCepThatDoesNotExistIsNotFound(): void
    {
        self::assertNull($this->client([200, '{"erro": "true"}', 0])->find('99999-999'));
        self::assertNull($this->client([200, '{"erro": true}', 0])->find('99999-999'), 'formato antigo do ViaCEP');
    }

    public function testGeneralCityCepComesWithoutStreet(): void
    {
        $address = $this->client([200, '{"cep":"17500-000","logradouro":"","bairro":"","localidade":"Marília","uf":"SP"}', 0])->find('17500-000');

        self::assertNotNull($address);
        self::assertSame('', $address->street);
        self::assertTrue($address->isInMarilia());
    }

    public function testCepOfAnotherCityIsRecognized(): void
    {
        $address = $this->client([200, '{"cep":"01001-000","logradouro":"Praça da Sé","bairro":"Sé","localidade":"São Paulo","uf":"SP"}', 0])->find('01001-000');

        self::assertFalse($address?->isInMarilia());
    }

    public function testProviderDownOrOddAnswersAreUnavailable(): void
    {
        foreach ([[503, 'Service Unavailable', 0], [429, '', 0], [200, '<html>manutenção</html>', 0], [200, '{"cep":"17501-000"}', 0], [0, false, 7]] as $reply) {
            try {
                $this->client($reply)->find('17501-000');
                self::fail('esperava indisponibilidade para ' . json_encode($reply));
            } catch (ProviderUnavailable $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testTimeoutIsUnavailable(): void
    {
        $this->expectException(ProviderUnavailable::class);
        $this->expectExceptionMessage('tempo esgotado (3 s)');
        $this->client([0, false, CURLE_OPERATION_TIMEDOUT])->find('17501-000');
    }

    public function testStreetIsCutToTheColumnSize(): void
    {
        $long = str_repeat('Rua muito comprida ', 10);
        $address = $this->client([200, json_encode(['logradouro' => $long, 'bairro' => 'Centro', 'localidade' => 'Marília', 'uf' => 'SP'], JSON_THROW_ON_ERROR), 0])->find('17501-000');

        self::assertSame(120, mb_strlen((string) $address?->street));
    }

    public function testProviderAddressComesFromConfigAndMustBeHttps(): void
    {
        self::assertSame('https://viacep.com.br/ws', (new Config([]))->cepLookupUrl(), 'padrão: ViaCEP');
        self::assertSame('https://viacep.com.br/ws', (new Config(['CEP_LOOKUP_URL' => '']))->cepLookupUrl());
        self::assertSame('https://cep.exemplo:8443/v1', (new Config(['CEP_LOOKUP_URL' => 'https://cep.exemplo:8443/v1/']))->cepLookupUrl());
        foreach (['http://viacep.com.br/ws', 'https://viacep.com.br/ws?x=1', 'viacep.com.br/ws', 'https://user@viacep.com.br/ws'] as $bad) {
            try {
                (new Config(['CEP_LOOKUP_URL' => $bad]))->cepLookupUrl();
                self::fail("aceitou {$bad}");
            } catch (\RuntimeException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
