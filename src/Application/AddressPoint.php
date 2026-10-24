<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\NearestRegion;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/**
 * Ponto de um cadastro com endereço (base do agente, local do equipamento; ADR-0021). O ponto é obrigatório e nunca é
 * digitado: vem do mapa ou, sem ele, do endereço (rua informada ou a do CEP, localizada pelo provedor). Com ponto e
 * endereço novo ou alterado, o ponto precisa ficar no endereço: fora da área dele, vai para o endereço (provedor fora
 * do ar: fica o marcado). O bairro é o de centro mais próximo do ponto, a mesma régua do Planejar.
 *
 * Os nomes dos campos variam por cadastro: $fields = ['lat' => …, 'lng' => …, 'street' => …, 'cep' => …, 'number' => …].
 */
final class AddressPoint
{
    /** @param \Closure(): AddressLocator $locator criado só quando é preciso consultar o endereço */
    public function __construct(private readonly \Closure $locator, private readonly ReferenceRepository $references) {}

    /**
     * Consulta externa só com o resto do formulário válido (e fora de transação); a rua achada pelo CEP passa a ser a
     * rua gravada.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed>|null $current cadastro atual, na edição
     * @param array{lat: string, lng: string, street: string, cep: string, number: string} $fields
     * @param string $what o que se marca, para a mensagem ("a base", "o local")
     */
    public function resolve(Input $input, array &$values, ?array $current, array $fields, string $what): void
    {
        ['lat' => $latKey, 'lng' => $lngKey, 'street' => $streetKey, 'cep' => $cepKey, 'number' => $numberKey] = $fields;
        $errors = $input->errors();
        if (isset($errors[$latKey]) || isset($errors[$lngKey])) {
            return;
        }
        if ($values[$latKey] !== null && $values[$lngKey] !== null) {
            if ($errors === [] && $values[$streetKey] !== null && self::addressChanged($current, $values, [$streetKey, $cepKey, $numberKey])) {
                $this->keepInsideAddress($values, $fields);
            }

            return;
        }
        if ($values[$latKey] !== null || $values[$lngKey] !== null || ($values[$streetKey] === null && $values[$cepKey] === null)) {
            $input->fail($latKey, "Marque {$what} no mapa ou, sem o mapa, informe o CEP e a rua.");

            return;
        }
        if ($errors !== []) {
            return;
        }
        $found = ($this->locator)()->locate($values[$streetKey], $values[$cepKey], $values[$numberKey]);
        if ($found['situacao'] !== 'ENCONTRADO') {
            $input->fail($found['situacao'] === 'INDISPONIVEL' ? $latKey : $streetKey, AddressLocator::MESSAGES[$found['situacao']]);

            return;
        }
        $values[$latKey] = $found['lat'];
        $values[$lngKey] = $found['lng'];
        $values[$streetKey] = $found['rua'];
    }

    /**
     * Na edição, o bairro acompanha o ponto: é recalculado quando o ponto muda (ou se ainda não havia bairro); fora
     * isso, fica o gravado, inclusive o escolhido à mão em cadastros antigos.
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $values
     * @return array<string, mixed> ['region_id' => …] ou vazio
     */
    public function regionOnUpdate(array $current, array $values, string $latKey, string $lngKey): array
    {
        $moved = $current[$latKey] === null || $current[$lngKey] === null
            || number_format((float) $current[$latKey], 6, '.', '') !== $values[$latKey]
            || number_format((float) $current[$lngKey], 6, '.', '') !== $values[$lngKey];

        return $moved || $current['region_id'] === null ? ['region_id' => $this->regionAt((float) $values[$latKey], (float) $values[$lngKey])] : [];
    }

    /** Bairro de referência de centro mais próximo do ponto. */
    public function regionAt(float $lat, float $lng): ?int
    {
        $region = NearestRegion::of($lat, $lng, $this->references->regions());

        return $region === null ? null : (int) $region['id'];
    }

    /**
     * @param array<string, mixed>|null $current
     * @param array<string, mixed> $values
     * @param list<string> $keys
     */
    private static function addressChanged(?array $current, array $values, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($current === null || (string) ($current[$key] ?? '') !== (string) ($values[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $values
     * @param array{lat: string, lng: string, street: string, cep: string, number: string} $fields
     */
    private function keepInsideAddress(array &$values, array $fields): void
    {
        $found = ($this->locator)()->locate($values[$fields['street']], $values[$fields['cep']], $values[$fields['number']]);
        if ($found['situacao'] !== 'ENCONTRADO') {
            return;
        }
        [$south, $north, $west, $east] = $found['caixa'];
        [$lat, $lng] = [(float) $values[$fields['lat']], (float) $values[$fields['lng']]];
        if ($lat < $south || $lat > $north || $lng < $west || $lng > $east) {
            $values[$fields['lat']] = $found['lat'];
            $values[$fields['lng']] = $found['lng'];
        }
    }
}
