<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

use Fiscaliza\Application\Geocoder;
use Fiscaliza\Application\Input;
use Fiscaliza\Application\ProviderUnavailable;

/**
 * Ponto de uma rua pelo Nominatim, o buscador do OpenStreetMap (a mesma base do mapa; ADR-0021). Busca estruturada
 * (rua + cidade, padrão Marília/SP), restrita ao retângulo da demonstração, um resultado, só https, tempo limite curto. Política do
 * serviço: uso eventual, no máximo 1 requisição por segundo (o transporte padrão espaça as do servidor), User-Agent
 * que identifique a aplicação e nada de autocompletar: aqui só há consulta quando a pessoa pede ou ao salvar.
 */
final class NominatimGeocoder implements Geocoder
{
    public const TIMEOUT_SECONDS = 3;
    private const MAX_BYTES = 65536;

    /** @var \Closure(string, int): array{0: int, 1: string|false, 2: int} */
    private readonly \Closure $transport;

    /**
     * @param string $baseUrl https, sem barra final (Config::geocoderUrl)
     * @param (\Closure(string, int): array{0: int, 1: string|false, 2: int})|null $transport GET (URL, tempo limite) →
     *        [status HTTP, corpo ou false, código de erro do curl]; os testes passam um falso
     */
    public function __construct(private readonly string $baseUrl, ?\Closure $transport = null)
    {
        $this->transport = $transport ?? self::curl(...);
    }

    public function locate(string $street, string $city = 'Marília'): ?array
    {
        $area = Input::AREA;
        $url = $this->baseUrl . '/search?' . http_build_query([
            'street' => $street, 'city' => $city, 'state' => 'São Paulo', 'country' => 'Brasil', 'countrycodes' => 'br',
            'viewbox' => "{$area['lng_min']},{$area['lat_max']},{$area['lng_max']},{$area['lat_min']}", 'bounded' => 1,
            'format' => 'jsonv2', 'limit' => 1, 'accept-language' => 'pt-BR',
        ], '', '&', PHP_QUERY_RFC3986);
        [$status, $body, $error] = ($this->transport)($url, self::TIMEOUT_SECONDS);
        if ($body === false) {
            throw new ProviderUnavailable($error === CURLE_OPERATION_TIMEDOUT ? 'tempo esgotado (' . self::TIMEOUT_SECONDS . ' s)' : "falha de rede (curl {$error})");
        }
        if ($status !== 200) {
            throw new ProviderUnavailable("provedor respondeu HTTP {$status}");
        }
        $data = json_decode($body, true);
        if (!is_array($data) || !array_is_list($data)) {
            throw new ProviderUnavailable('resposta do provedor não é uma lista JSON');
        }
        $first = $data[0] ?? null;
        if ($first === null) {
            return null;
        }
        $lat = filter_var($first['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($first['lon'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false) {
            throw new ProviderUnavailable('resposta do provedor sem coordenadas');
        }
        if ($lat < $area['lat_min'] || $lat > $area['lat_max'] || $lng < $area['lng_min'] || $lng > $area['lng_max']) {
            return null;
        }

        return ['lat' => number_format($lat, 6, '.', ''), 'lng' => number_format($lng, 6, '.', '')];
    }

    /** @return array{0: int, 1: string|false, 2: int} */
    private static function curl(string $url, int $timeout): array
    {
        self::pace();
        // curl, e não fopen: allow_url_fopen fica desligado no php.ini do projeto.
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS_STR => 'https',
            CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_TIMEOUT => $timeout, CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_USERAGENT => 'PromotheusFiscaliza/1.0 (localizar base de agente pelo endereco)']);
        $body = curl_exec($curl);

        return [(int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), is_string($body) ? $body : false, curl_errno($curl)];
    }

    /** No máximo 1 requisição por segundo do servidor inteiro: a trava fica com quem espera, então as seguintes enfileiram. */
    private static function pace(): void
    {
        $handle = fopen(sys_get_temp_dir() . '/fiscaliza-nominatim.ritmo', 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            return;
        }
        $wait = (float) stream_get_contents($handle) + 1.0 - microtime(true);
        if ($wait > 0) {
            usleep((int) (min($wait, 1.0) * 1_000_000));
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) microtime(true));
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
