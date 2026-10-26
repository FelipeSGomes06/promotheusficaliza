# Previsão por setores, quadrados de 2 km e bairro mais próximo

<!-- fontes: src/Domain/SectorForecast.php src/Domain/NearestRegion.php -->

Regras puras do **Planejar reforço** ([ADR-0016](../../../adr/0016-planejamento-preventivo-por-setores.md)) e do
bairro derivado de um ponto ([ADR-0021](../../../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). O valor
previsto é a **contagem esperada de sinistros registrados** (elegíveis na área de estudo) em 7 dias — não é
probabilidade de acidente. A posição na ordem é **prioridade relativa de planejamento**: o 1º não é emergência.

Os dados vêm do Infosiga-SP (**reais e agregados**); o método e a avaliação estão em
[planejamento por setores](../../../planejamento-por-setores.md) e no pacote `ml/marilia-setores/`.

## `SectorForecast`

| Constante | Valor |
|---|---|
| `PROTOCOL` | `marilia-setores-v1` — contrato dos arquivos importados |
| `SECTORS` | `['NO', 'NE', 'SO', 'SE']` — ordem do treino (`config.sectors`) e dos indicadores de setor do modelo |
| `METHODS` | `baseline` "Referência histórica" · `poisson` "Poisson experimental" |
| `COLUMN` | coluna da estimativa de cada método: `expected_baseline`, `expected_ml` |
| `GRID` | 8 — a área de estudo vira 8 × 8 quadrados de cerca de 2 km; cada setor tem 4 × 4 |

| Método | Contrato |
|---|---|
| `SectorForecast::rank` | `static (array $values): array<string, int>` — setor → posição (1 = maior estimativa); empate pelo código do setor em ordem lexical (contrato v1). |
| `SectorForecast::replayMetrics` | ver abaixo. |
| `SectorForecast::distanceToBoxKm` | `static (float $lat, float $lng, array $box): float` — distância aproximada em **linha reta** (km) do ponto até o retângulo `{south, north, west, east}`; 0 dentro. Projeção equirretangular local (`cos(lat)` na longitude, raio 6371 km). Não é rota nem tempo de chegada. |
| `SectorForecast::cellBox` | `static (array $bounds, int $cell): array` — retângulo do quadrado `cell` (0–63); linha 0 = sul, coluna 0 = oeste; mesma numeração de `ml/marilia-grade-2km`. |
| `SectorForecast::cellSector` | `static (int $cell): string` — setor do quadrado: linhas 4–7 = Norte, colunas 0–3 = Oeste (`NO`, `NE`, `SO`, `SE`). |
| `SectorForecast::rankCells` | `static (array $values): array<int, int>` — quadrado → posição; maior estimativa primeiro, empate pelo número do quadrado. |
| `SectorForecast::cellOf` | `static (float $lat, float $lng, array $bounds): ?int` — quadrado de um ponto; `null` fora do retângulo. Borda dos setores: a linha divisória vai para norte/leste; bordas externas incluídas (mesma conta de `experimento.py`, com tolerância 1e-9). |
| `SectorForecast::cutoff` | `static (string $asOf): string` — corte de informação **exclusivo**: 1º dia do mês, dois meses antes do mês da emissão (atraso de publicação **assumido**). Ex.: emissão `2026-10-20` → `2026-08-01` (dados até 31/07/2026). |
| `SectorForecast::poissonFeatures` | `static (string $sectorId, int $count13, int $count52, string $asOf): list<float>` — as nove variáveis do Poisson na ordem do treino: indicadores dos 4 setores, `log1p((n13+1)/14)`, `log1p((n52+1)/53)`, seno e cosseno do dia do ano (366 em ano bissexto) e anos desde 2020-01-01 (dias ÷ 365,25). |
| `SectorForecast::poisson` | `static (array $model, array $x): float` — previsão do modelo exportado em JSON: `exp(intercepto + Σ coef·(x − média)/escala)`. Paridade com o Python verificada por `ml/marilia-setores/verificar_paridade.php`. |

### `SectorForecast::replayMetrics`

`static replayMetrics(array $rows, string $method): array{captured, observed, weeks, rows, mae, predicted, deviance}`

Teste com **orçamento de um setor por semana**: para cada semana do retrospecto, soma os registros observados no setor
que o método pôs em 1º (`captured`), o total observado, o erro absoluto médio por setor/semana (`mae`), a soma prevista
e o **desvio médio de Poisson** (como o `mean_poisson_deviance` do scikit-learn, previsão limitada a 1e-12). Método
desconhecido → `InvalidArgumentException`. O importador recalcula essas métricas das linhas e recusa a execução se não
baterem com `avaliacao.json` ([`UrbanImporter`](../application/importacao-urbana.md)). A fração é sobre registros
elegíveis na área de estudo; não é taxa de acerto nem acidentes evitados.

Testes: `UrbanImportTest`, `PlanningTest`.

## `NearestRegion`

Bairro de referência mais perto de um ponto: o de **centro** mais próximo em linha reta sobre os graus (sem correção de
latitude, suficiente dentro de Marília). O banco não guarda limites de bairro, só o centro (`REGION.CENTER_*`).

| Método | Contrato |
|---|---|
| `NearestRegion::sorted` | `static (float $lat, float $lng, array $regions): list<array>` — bairros do mais perto ao mais longe (`hypot` dos graus); empate mantém a ordem recebida. |
| `NearestRegion::of` | `static (float $lat, float $lng, array $regions): ?array` — o primeiro de `sorted`; `null` sem bairros cadastrados. |

Quem usa: [`AddressPoint::regionAt`](../application/endereco.md#addresspoint) (bairro de equipamentos e bases de
agente) e `PlanningService::places` ("perto de …" e bairros de referência de cada quadrado). Testes: `DomainRulesTest`,
`AgentBaseTest`.

## Relacionados

[`PlanningService`](../application/planejamento.md) · [`UrbanImporter`](../application/importacao-urbana.md) ·
[`UrbanUpdater`](../application/atualizacao-urbana.md) · [Guia: Planejar reforço](../../../guias/planejar-reforco.md)
