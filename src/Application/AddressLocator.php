<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/**
 * Ponto da base pelo endereço, para quem não consegue marcar no mapa (ADR-0021): a rua informada ou, sem ela, a rua
 * do CEP; depois o provedor de localização. Latitude e longitude nunca são digitadas. Usado pela tela
 * (GET /api/localizar) e pelos cadastros de agente e de equipamento quando chegam sem ponto (formulário sem JavaScript).
 */
final class AddressLocator
{
    /** Folga em graus em volta do lugar achado (≈ 100 m) e raio em volta da casa achada pelo número (≈ 150 m). */
    private const MARGIN = 0.001;
    private const HOUSE = 0.0015;

    /** Mensagem de cada situação, pronta para a tela. */
    public const MESSAGES = [
        'ENCONTRADO' => 'Ponto posto no endereço (aproximado). Confira no mapa; para ajustar, clique no ponto certo.',
        'SEM_RUA' => 'Para localizar pelo endereço, informe a rua: este CEP não indica a rua.',
        'NAO_ENCONTRADO' => 'Não achamos esta rua em Marília. Confira a rua e o CEP ou marque no mapa.',
        'INDISPONIVEL' => 'A localização pelo endereço não respondeu agora. Tente de novo em instantes ou marque no mapa.',
    ];

    public function __construct(private readonly CepLookup $cep, private readonly Geocoder $geocoder) {}

    /**
     * @param string|null $street rua já validada (pode vir vazia)
     * @param string|null $cep CEP já validado (00000-000) ou null
     * @param string|null $number número da base; só os dígitos iniciais ajudam ("1200", "1200 fundos")
     * @return array{situacao: 'ENCONTRADO', lat: string, lng: string, rua: string, caixa: list<float>}|array{situacao: 'SEM_RUA'|'NAO_ENCONTRADO'|'INDISPONIVEL'}
     *         caixa = área do endereço [sul, norte, oeste, leste]: um ponto fora dela está "fora do endereço" (a tela leva a base
     *         para o endereço); com o número achado, ≈ 150 m em volta da casa; só com a rua, o trecho da rua com ≈ 100 m de folga
     */
    public function locate(?string $street, ?string $cep, ?string $number = null): array
    {
        $street = trim((string) $street);
        try {
            if ($street === '' && $cep !== null) {
                $address = $this->cep->find($cep);
                $street = $address !== null && $address->isInMarilia() ? $address->street : '';
            }
            if ($street === '') {
                return ['situacao' => 'SEM_RUA'];
            }
            // Com número, tenta primeiro a casa; o mapa nem sempre tem números, então cai para a rua.
            $house = preg_match('/^\d{1,6}/', trim((string) $number), $m) === 1 ? $m[0] : null;
            $point = $house === null ? null : $this->geocoder->locate("{$house} {$street}");
            $exact = $point !== null;
            $point ??= $this->geocoder->locate($street);
        } catch (ProviderUnavailable $e) {
            // Log privado (sem a rua nem o CEP): ajuda a ver se o servidor publicado consegue sair para a internet.
            error_log('[fiscaliza] localização pelo endereço indisponível: ' . $e->getMessage());

            return ['situacao' => 'INDISPONIVEL'];
        }

        if ($point === null) {
            return ['situacao' => 'NAO_ENCONTRADO'];
        }
        [$lat, $lng] = [(float) $point['lat'], (float) $point['lng']];
        $box = !$exact && isset($point['box'])
            ? [$point['box'][0] - self::MARGIN, $point['box'][1] + self::MARGIN, $point['box'][2] - self::MARGIN, $point['box'][3] + self::MARGIN]
            : [$lat - self::HOUSE, $lat + self::HOUSE, $lng - self::HOUSE, $lng + self::HOUSE];

        return ['situacao' => 'ENCONTRADO', 'lat' => $point['lat'], 'lng' => $point['lng'], 'rua' => $street, 'caixa' => array_map(static fn(float $v): float => round($v, 6), $box)];
    }
}
