# Cenário de multas da demonstração

<!-- fontes: src/Demo/FineScenario.php -->

Doze casos curados (perfil compacto) ou quarenta (completo), gravados **pelos mesmos casos de uso da interface**
([`FineService`](../application/multas.md)) com origem `SEMENTE` e sem conta. Placas, RENAVAM, AIT, marcas e referências
são fictícios (códigos começam com `DEMO-`); as datas são relativas ao relógio. Não altera recursos, contas nem
histórico existentes. Entra no `demo:seed` e, numa demonstração já carregada, no `demo:seed-multas`.

| Símbolo | Contrato |
|---|---|
| `FineScenario::VERSION` | `multas-v3` — gravada em `APP_META.fines_seed`; repetir a mesma versão não duplica nada. |
| `FineScenario::PREVIOUS` | `multas-v1`, `multas-v2` — cargas anteriores que `upgrade()` leva à atual. |
| `C02` (privada) | dados do caso 2 (radar portátil num trecho de 60 km/h, 81 km/h medidos). |
| `RELATOS` (privada) | relato factual de cada caso, mostrado em "O que aconteceu". |

## Os doze casos

| Caso | O que demonstra |
|---|---|
| c01 | radar fixo de velocidade completo: 50 km/h, 71 medidos, 64 considerados; dados metrológicos; conferência regular; autuação |
| c02 | **radar portátil** operado pelo agente Hermes 04 (operador associado), 60 km/h, 81 medidos; dados metrológicos a conferir |
| c03 | radar de avanço de sinal: sem bloco de velocidade; evidência; regular |
| c04 | agente; placa no formato antigo `DMO1234`; **RENAVAM com zeros à esquerda** `00012345678`; CRLV apresentado |
| c05 | agente; RENAVAM ausente **justificado**; documentação não verificada |
| c06 | pendência documental na ocorrência com **lembrete já vencido** |
| c07 | pendência na ocorrência e **regularidade informada depois** (a ocorrência conserva a pendência) |
| c08 | penalidade notificada + **pagamento informado** + **recurso em análise** ao mesmo tempo |
| c09 | **rascunho** de radar com a placa lida na imagem e o veículo a transcrever |
| c10 | registro **cancelado internamente** (lançado em duplicidade) |
| c11 | radar hoje em mau funcionamento: o registro guarda o **retrato da origem** na ocorrência |
| c12 | **retificação da placa**: a conferência anterior fica só no histórico; a nova identidade pede nova conferência |
| v01–v28 | só no perfil completo: volume alternando radar e agente, com conferência e pagamento em parte |

## `FineScenario::seed`

`static seed(Services $s, string $profile): int` — exige o órgão `DMT`, os radares `VS5-24-00201`, `PCL-0042`,
`PCL-0063`, `VS3-23-00088` e os agentes Hermes 01/04/06 (senão `RuntimeException` "Carregue a demonstração antes");
cria cada caso por `create`/`register`/`addCheck`/`updateProgress`/`update`/`cancel` com chaves de comando estáveis
(`md5("promotheus-multas:multas-v1:<caso>:<ação>")`), grava `fines_seed` e devolve quantos criou.

## `FineScenario::upgrade`

`static upgrade(Services $s): int` — leva a carga anterior à versão atual **sem apagar nada**; cada mudança passa pelo
caso de uso e fica no histórico do registro: de v1, [`upgradeFromV1`](#finescenarioupgradefromv1); depois
[`upgradeToV3`](#finescenarioupgradetov3); grava `fines_seed = multas-v3`.

### `FineScenario::upgradeFromV1`

`private static upgradeFromV1(Services $s): int` — troca o texto genérico pelo relato de cada caso (inclusive os de
volume) e, no rascunho de radar (c09), informa a placa lida na imagem; registros cancelados ficam como estão.

### `FineScenario::upgradeToV3`

`private static upgradeToV3(Services $s): int` — informa a **modalidade** (fixo/portátil) nos radares da demonstração
que ainda não a têm (pela série; o sem série, pelo endereço), com evento `ALTERACAO` origem `SEMENTE`; e move o caso 2,
que tinha agente operador numa lombada fixa, para o **radar portátil** do inventário (com o bairro e o relato novos),
por retificação com motivo.

| Método | Contrato |
|---|---|
| `FineScenario::uid` | `private static (string $key): string` — chave de comando estável entre versões. |
| `FineScenario::volumeRelato` | `private static (int $i, bool $radar, ?string $kind): string` — relato dos registros de volume. |

Testes: `DemoProfileTest` (12/40), `FinesTest` (v1 → v3 e v2 → v3 sem duplicar). Verificado em banco populado em
21/10/2026 e na carga `multas-v3` ([verificação](../../../verificacao.md)).

## Relacionados

[Cenário e carga](cenario-e-carga.md) · [Guia: Multas](../../../guias/multas.md)
