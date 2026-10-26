# Importação de uma execução do treino por setores

<!-- fontes: src/Application/UrbanImporter.php -->

`UrbanImporter` importa uma execução **completa** do treino separado (`ml/marilia-setores/runs/<execução>`), o GeoJSON
dos setores, o manifesto do snapshot e, opcionalmente, as contagens por quadrado de 2 km
(`ml/marilia-grade-2km/dados/contagens_grade.csv`) — contrato `marilia-setores-v1`. Só pela CLI
(`php bin/fiscaliza urban:import`), com a identidade da aplicação; nada é executado por HTTP.

Os arquivos são **dados, nunca código**: só os nomes esperados, até 10 MiB cada e 64 MiB no conjunto, JSON estrito com
profundidade máxima 16, UTF-8, números finitos. **Tudo é validado antes de abrir a transação**; a gravação é uma
transação só. O SHA-256 dos bytes identifica o conjunto: reimportar devolve a execução existente.

| Constante | Valor |
|---|---|
| `DEFAULT_RUN` | `ml/marilia-setores/runs/primeiro-treino-verificado` |
| `DEFAULT_SECTORS` | `ml/marilia-setores/dados/setores.geojson` |
| `DEFAULT_MANIFEST` | `ml/marilia-setores/dados/manifesto.json` |
| `DEFAULT_GRID` | `ml/marilia-grade-2km/dados/contagens_grade.csv` |
| `MAX_FILE_BYTES` / `MAX_TOTAL_BYTES` | 10 MiB / 64 MiB |
| `MAX_REFERENCE_AGE_DAYS` | 180 — emissão a mais de 180 dias da referência dos dados é recusada (mesma regra do treino) |
| `RUN_FILES` (privada) | `previsao.json`, `avaliacao.json`, `retrospecto.json`, `modelo.json`, `status.json` |
| `FEATURES` (privada) | as nove variáveis do Poisson na ordem do treino |

## `UrbanImporter::import`

`import(string $runDir, string $sectorsFile, string $manifestFile, ?string $gridFile = null): array{id, created, cells, as_of, until, sectors, replay_rows, sha256}`

1. [`read`](#urbanimporterread) valida o conjunto inteiro; com grade, [`cells`](#urbanimportercells) calcula os 64
   quadrados.
2. Mesmo SHA-256 já importado → devolve a execução existente (`created: false`) e, se ela ainda não tinha quadrados,
   grava-os (`addCells`) — os quadrados não entram no SHA-256 da execução.
3. Senão grava execução, setores, previsão, retrospecto e quadrados numa transação. Corrida com outra importação dos
   mesmos bytes (`UQ_URBAN_RUN_BUNDLE`) → mesmo resultado (`created: false`).

## `UrbanImporter::read`

`static read(string $runDir, string $sectorsFile, string $manifestFile): array` — lê e valida **sem tocar no banco**:

- arquivos presentes, regulares (não links) e dentro dos limites;
- SHA-256 de cada arquivo e do conjunto (`nome:sha` ordenados, um por linha);
- [manifesto](#urbanimportermanifest), [setores](#urbanimportersectors), [previsão](#urbanimporterforecast),
  [avaliação](#urbanimporterevaluation), [retrospecto](#urbanimporterreplay), modelo e status coerentes entre si.

Qualquer violação → `ValidationFailed` com o caminho do campo (ex.: `previsao.json.sectors[2].rank`) e a mensagem
"Execução recusada; nada foi gravado.".

### `UrbanImporter::manifest`

`private static manifest(array $m): array` — versão 1, protocolo, fonte com `source_url`/`source_catalog` https,
licença `CC-BY-4.0`, `snapshot_date`, retângulo de estudo dentro de `Input::AREA`, contagens inteiras não negativas,
`daily_sha256`, limitações (até 20 textos) e `source_sha256`/`data_through`.

### `UrbanImporter::sectors`

`private static sectors(array $g, array $bounds): array` — `FeatureCollection` com **exatamente 4** setores `NO`, `NE`,
`SO`, `SE`, cada um `experimental: true` e `official_neighborhood: false`, polígono retângulo fechado
(`[longitude, latitude]`), igual ao quadrante do retângulo de estudo dividido no ponto médio, centro igual ao centro do
retângulo, nome ≤ 80.

### `UrbanImporter::forecast`

`private static forecast(array $d, array $manifest): array` — período `[as_of, until)` de exatamente 7 dias; referência
dos dados anterior ao período, coberta pelo snapshot e com até 180 dias; `generated_at_utc` ISO com fuso; mesmo
snapshot do manifesto; `selected_method` válido, candidato `poisson`, unidade
`expected_registered_crashes_in_7_days`, status `experimental_retrospective`. Quatro setores com posição 1–4,
prioridade coerente (`PRIORITARIA` só o 1º), estimativa principal = a do método escolhido, referência = `(n52+1)/53`,
histórico de 52 semanas terminando na referência, ordem decrescente com desempate pelo código.

### `UrbanImporter::evaluation`

`private static evaluation(array $d, array $run): array` — método escolhido **antes do teste** igual ao da previsão;
alpha; modelo do teste; métricas dos dois métodos coerentes entre si (linhas = semanas × 4, capturado ≤ observado,
fração = capturado ÷ observado) e medidas no **mesmo período e nas mesmas linhas**; ganho relativo do desvio coerente;
modelo do teste sem rótulos dentro do teste.

### `UrbanImporter::replay`

`private static replay(array $d, array $run, array $evaluation): array` — modelo emitido antes do teste e depois dos
rótulos de treino; cada linha uma semana de 7 dias dentro do teste, sem repetição de semana/setor, com os quatro
setores por semana; **as linhas precisam reproduzir as métricas declaradas** na avaliação
([`SectorForecast::replayMetrics`](../domain/setores-e-regioes.md#sectorforecastreplaymetrics)), senão a execução é
recusada.

## `UrbanImporter::cells`

`static cells(string $path, array $bundle): list<array>` — lê `date,cell,count` (cabeçalho exato; data válida; quadrado
0–63; contagem positiva; sem data/quadrado repetidos); soma as janelas de 52 semanas (`through − 363` a `through`) e de
13 semanas (`through − 90`) usando a referência dos dados da execução; **os quadrados de cada setor precisam somar
exatamente as contagens de 13 e 52 semanas** que a execução traz para o setor (senão: "grade de outro snapshot ou
corte"). Devolve [`cellRows`](#urbanimportercellrows).

## `UrbanImporter::cellRows`

`static cellRows(array $c13, array $c52, array $bounds): list<array>` — 64 linhas com setor, contagens, estimativa pela
**média simples** `(n52 + 0,1) ÷ 52,1` (o método que venceu o teste de 2026 no experimento da grade), posição e
retângulo. Também usado por [`UrbanUpdater::forecast`](atualizacao-urbana.md#urbanupdaterforecast).

| Método | Contrato |
|---|---|
| `UrbanImporter::__construct` | `(Database, UrbanRepository, Clock)`. |
| `UrbanImporter::bounds` | `static (array $sectors): array` — retângulo de estudo = união dos setores. |
| `UrbanImporter::addCells` | `private (int $runId, array $cells): int` — grava quadrados numa execução que ainda não os tem (`PK_URBAN_CELL` em corrida é ignorada); devolve quantos ela tem. |
| `UrbanImporter::decode` | `private static (string $bytes, string $name): array` — UTF-8, JSON estrito (NaN/Infinity e truncado são erro), objeto (não lista), números finitos. |
| `UrbanImporter::model` | `private static (array $d, array $run, array $evaluation): void` — protocolo, coeficientes e o modelo da emissão sem rótulos do período previsto. |
| `UrbanImporter::status` | `private static (array $d, array $run): void` — `complete`, teste final avaliado, mesmo método. |
| `UrbanImporter::modelCoefficients` | `private static (array $m, string $p, float $alpha): array` — tipo `poisson_log_link_standardized`, nomes das 9 variáveis, 9 médias/escalas (positivas)/coeficientes finitos, intercepto, alpha igual ao escolhido, janela de treino não vazia. |
| `UrbanImporter::evidence` | `private static (array $e, string $p, string $before): array` — 52 semanas terminando antes do corte, `n13 ≤ n52`, referência `(n52+1)/53`, disponibilidade `assumed_publication_delay_not_observed`, idade dos dados 0–400 dias. |
| `UrbanImporter::fail` | `private static (string $path, string $message): never` — lança `ValidationFailed([$path => $message], 'Execução recusada; nada foi gravado.')`. |
| `UrbanImporter::same`, `object`, `items`, `text`, `strings`, `choice`, `date`, `hash`, `int`, `number`, `isNumber`, `close`, `addDays`, `days` | Leitores estritos privados: valor igual ao esperado; objeto; lista com tamanho; texto ≤ N sem controle; lista de textos; valor de lista fechada; data ISO estrita; SHA-256 hex; inteiro na faixa; número finito (não negativo, salvo `signed`); tolerância relativa 1e-9; aritmética de datas em UTC. |

Os leitores privados, um a um: `UrbanImporter::same`, `UrbanImporter::object`, `UrbanImporter::items`,
`UrbanImporter::text`, `UrbanImporter::strings`, `UrbanImporter::choice`, `UrbanImporter::date`, `UrbanImporter::hash`,
`UrbanImporter::int`, `UrbanImporter::number`, `UrbanImporter::isNumber`, `UrbanImporter::close`,
`UrbanImporter::addDays`, `UrbanImporter::days`.

Testes: `UrbanImportTest` (bytes do pacote iguais a `ENTREGA-MANIFESTO.json`, conjuntos adulterados recusados,
idempotência, grade). Verificado em 20/10/2026: duas importações → 1 execução; dois conjuntos adulterados recusados
([evidência](../../../evidencias/2026-10-20-planejar-reforco.md)).

## Relacionados

[Atualização automática](atualizacao-urbana.md) · [Contrato dos arquivos](../../../../ml/marilia-setores/03-CONTRATO-DOS-ARQUIVOS.md) ·
[Repositório urbano](../infrastructure/repositorios-planejamento.md#urbanrepository)
