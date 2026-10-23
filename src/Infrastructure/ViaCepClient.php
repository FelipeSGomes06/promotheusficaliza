<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

use Fiscaliza\Application\CepAddress;
use Fiscaliza\Application\CepLookup;
use Fiscaliza\Application\ProviderUnavailable;

/**
 * Consulta ao ViaCEP (https://viacep.com.br), gratuito e sem chave (ADR-0021). Um GET por CEP, só https, sem seguir
 * redirecionamento e com tempo limite curto: a pessoa está esperando no formulário. Só os 8 dígitos vão na URL;
 * nada identifica a conta, o agente ou o servidor além do User-Agent do projeto.
 *
 * O ViaCEP responde 200 com o endereço, 200 com {"erro": true} (ou "true") para CEP inexistente e 400 para formato
 * inválido (não acontece: o CEP chega validado). Qualquer outra coisa é indisponibilidade.
 */
final class ViaCepClient implements CepLookup
{
    public const TIMEOUT_SECONDS = 3;
    private const MAX_BYTES = 16384;

    /** @var \Closure(string, int): array{0: int, 1: string|false, 2: int} */
    private readonly \Closure $transport;

    /**
     * @param string $baseUrl https, sem barra final (Config::cepLookupUrl)
     * @param (\Closure(string, int): array{0: int, 1: string|false, 2: int})|null $transport GET (URL, tempo limite) →
     *        [status HTTP, corpo ou false, código de erro do curl]; os testes passam um falso
     */
    public function __construct(private readonly string $baseUrl, ?\Closure $transport = null)
    {
        $this->transport = $transport ?? self::curl(...);
    }

    public function find(string $cep): ?CepAddress
    {
        $digits = (string) preg_replace('/\D/', '', $cep);
        if (strlen($digits) !== 8) {
            throw new \InvalidArgumentException('O CEP precisa ter 8 dígitos.');
        }
        [$status, $body, $error] = ($this->transport)("{$this->baseUrl}/{$digits}/json/", self::TIMEOUT_SECONDS);
        if ($body === false) {
            throw new ProviderUnavailable($error === CURLE_OPERATION_TIMEDOUT ? 'tempo esgotado (' . self::TIMEOUT_SECONDS . ' s)' : "falha de rede (curl {$error})");
        }
        if ($status !== 200) {
            throw new ProviderUnavailable("provedor respondeu HTTP {$status}");
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new ProviderUnavailable('resposta do provedor não é JSON');
        }
        if (isset($data['erro'])) {
            return null;
        }
        $text = static fn(string $key): string => is_string($data[$key] ?? null) ? trim($data[$key]) : '';
        if ($text('localidade') === '' || $text('uf') === '') {
            throw new ProviderUnavailable('resposta do provedor sem cidade ou UF');
        }

        // Rua cortada no tamanho da coluna (BASE_STREET, VARCHAR 120) para nunca travar o cadastro.
        return new CepAddress(substr($digits, 0, 5) . '-' . substr($digits, 5), mb_substr($text('logradouro'), 0, 120), $text('bairro'), $text('localidade'), $text('uf'));
    }

    /** @return array{0: int, 1: string|false, 2: int} */
    private static function curl(string $url, int $timeout): array
    {
        // curl, e não fopen: allow_url_fopen fica desligado no php.ini do projeto.
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS_STR => 'https',
            CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout, CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_USERAGENT => 'PromotheusFiscaliza/1.0 (consulta de CEP no cadastro)']);
        $body = curl_exec($curl);

        return [(int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), is_string($body) ? $body : false, curl_errno($curl)];
    }
}
