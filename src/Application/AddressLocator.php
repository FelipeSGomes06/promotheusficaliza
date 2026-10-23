<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/**
 * Ponto da base pelo endereço, para quem não consegue marcar no mapa (ADR-0021): a rua informada ou, sem ela, a rua
 * do CEP; depois o provedor de localização. Latitude e longitude nunca são digitadas. Usado pela tela
 * (GET /api/localizar) e pelo cadastro de agente quando chega sem ponto (formulário sem JavaScript).
 */
final class AddressLocator
{
    /** Mensagem de cada situação, pronta para a tela. */
    public const MESSAGES = [
        'ENCONTRADO' => 'Base posta no endereço (ponto aproximado, no meio da rua). Confira no mapa; para ajustar, clique no ponto certo.',
        'SEM_RUA' => 'Para localizar pelo endereço, informe a rua: este CEP não indica a rua.',
        'NAO_ENCONTRADO' => 'Não achamos esta rua em Marília. Confira a rua e o CEP ou marque a base no mapa.',
        'INDISPONIVEL' => 'A localização pelo endereço não respondeu agora. Tente de novo em instantes ou marque a base no mapa.',
    ];

    public function __construct(private readonly CepLookup $cep, private readonly Geocoder $geocoder) {}

    /**
     * @param string|null $street rua já validada (pode vir vazia)
     * @param string|null $cep CEP já validado (00000-000) ou null
     * @return array{situacao: 'ENCONTRADO', lat: string, lng: string, rua: string}|array{situacao: 'SEM_RUA'|'NAO_ENCONTRADO'|'INDISPONIVEL'}
     */
    public function locate(?string $street, ?string $cep): array
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
            $point = $this->geocoder->locate($street);
        } catch (ProviderUnavailable $e) {
            // Log privado (sem a rua nem o CEP): ajuda a ver se o servidor publicado consegue sair para a internet.
            error_log('[fiscaliza] localização pelo endereço indisponível: ' . $e->getMessage());

            return ['situacao' => 'INDISPONIVEL'];
        }

        return $point === null ? ['situacao' => 'NAO_ENCONTRADO'] : ['situacao' => 'ENCONTRADO', 'lat' => $point['lat'], 'lng' => $point['lng'], 'rua' => $street];
    }
}
