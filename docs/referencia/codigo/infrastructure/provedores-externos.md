# Provedores externos: ViaCEP e Nominatim

<!-- fontes: src/Infrastructure/ViaCepClient.php src/Infrastructure/NominatimGeocoder.php -->

As **únicas** chamadas externas durante uma requisição web, feitas **pelo servidor** (o navegador só fala com o
próprio servidor: CSP `connect-src 'self'`) e só com conta autenticada
([ADR-0021](../../../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). Sai do servidor apenas o CEP ou o nome da
rua, já validados. Ambos usam **curl** (`allow_url_fopen` desligado), só `https`, sem seguir redirecionamento, tempo
limite de 3 s e um transporte injetável (os testes passam um falso; nenhum teste usa a rede). Falha vira
[`ProviderUnavailable`](../erros.md) — mensagem só para o log privado.

O download mensal do Infosiga não está aqui: é feito pela CLI ([`UrbanUpdater::download`](../application/atualizacao-urbana.md#urbanupdaterdownload)).

## `ViaCepClient`

Implementa [`CepLookup`](../application/endereco.md#ceplookup-interface) sobre `https://viacep.com.br/ws` (configurável
em `CEP_LOOKUP_URL`), gratuito e sem chave.

| Símbolo | Contrato |
|---|---|
| `ViaCepClient::TIMEOUT_SECONDS` | 3 |
| `ViaCepClient::__construct` | `(string $baseUrl, ?\Closure $transport = null)` — transporte: `(url, timeout) → [status HTTP, corpo ou false, código de erro do curl]`. |
| `ViaCepClient::find` | `(string $cep): ?CepAddress` — `GET {base}/{8 dígitos}/json/`. Corpo `false` → `ProviderUnavailable` ("tempo esgotado (3 s)" ou "falha de rede (curl N)"); status ≠ 200 → `ProviderUnavailable`; não JSON → idem; `{"erro": …}` → `null` (CEP inexistente); sem cidade ou UF → `ProviderUnavailable`. Rua cortada em 120 caracteres (tamanho da coluna). Menos de 8 dígitos → `InvalidArgumentException` (não acontece: o CEP chega validado). |
| `ViaCepClient::curl` | `private static (string $url, int $timeout): array` — GET com `Accept: application/json`, User-Agent "PromotheusFiscaliza/1.0 (consulta de CEP no cadastro)", resposta máxima 16 KiB. |

Teste: `CepLookupTest` (respostas do provedor, erro, tempo esgotado, formato inesperado).

## `NominatimGeocoder`

Implementa [`Geocoder`](../application/endereco.md#geocoder-interface) sobre o Nominatim do OpenStreetMap
(`GEOCODER_URL`, padrão `https://nominatim.openstreetmap.org`). Política do serviço respeitada: uso eventual, **no
máximo 1 requisição por segundo** (o transporte padrão espaça as do servidor inteiro), User-Agent que identifica a
aplicação e nada de autocompletar — só há consulta quando a pessoa pede ou ao salvar.

| Símbolo | Contrato |
|---|---|
| `NominatimGeocoder::TIMEOUT_SECONDS` | 3 |
| `NominatimGeocoder::__construct` | `(string $baseUrl, ?\Closure $transport = null)`. |
| `NominatimGeocoder::locate` | `(string $street): ?array` — busca estruturada `/search?street=…&city=Marília&state=São Paulo&country=Brasil&countrycodes=br`, presa ao retângulo `Input::AREA` (`viewbox` + `bounded=1`), `format=jsonv2`, `limit=1`, `accept-language=pt-BR`. Lista vazia → `null`; ponto fora da área → `null`; sem coordenadas → `ProviderUnavailable`. Devolve `{lat, lng}` com 6 casas e `box` = `boundingbox` `[sul, norte, oeste, leste]` quando vier (numa rua, cobre o trecho inteiro). |
| `NominatimGeocoder::curl` | `private static (string $url, int $timeout): array` — chama `pace()` e faz o GET (resposta máxima 64 KiB). |
| `NominatimGeocoder::pace` | `private static (): void` — ritmo de 1/s entre processos do mesmo servidor: trava exclusiva (`flock`) em `sys_get_temp_dir()/fiscaliza-nominatim.ritmo`, espera até 1 s desde a última consulta gravada no arquivo e grava o instante atual. Sem conseguir travar, segue sem esperar. |

Teste: `AddressLocatorTest`. Chamadas reais aos dois provedores **não foram verificadas** nas rodadas registradas (rede
bloqueada no ambiente; ver [verificação](../../../verificacao.md)). Em publicação, o contêiner `app` precisa de saída
HTTPS; sem ela, o cadastro de agente funciona só pelo mapa.

## Relacionados

[Endereço](../application/endereco.md) · [Licenças e fontes](../../../licencas-e-fontes.md) ·
[Segurança: privacidade](../../../seguranca.md#controles-implementados)
