# Instantâneo da central (Visão geral)

<!-- fontes: src/Application/CentralQuery.php -->

`CentralQuery` monta o **instantâneo** que alimenta a Visão geral: recursos, indicadores, resumo por bairro,
manutenções e alertas, todos lidos numa **única transação SNAPSHOT somente leitura** e derivados do **mesmo conjunto
filtrado** — mapa, lista e cartões nunca divergem. É servido como HTML inicial (`GET /`) e como JSON
(`GET /api/central`, a cada `POLL_INTERVAL_SECONDS`). Contrato JSON completo em
[HTTP: `/api/central`](../../../http.md#contrato-de-get-apicentral). Guia: [Visão geral](../../../guias/visao-geral.md).

## `CentralQuery::snapshot`

`snapshot(Filters $filters): array<string, mixed>`

Dentro de `Database::transaction(..., snapshot: true)` (isolamento `REPEATABLE_READ`, somente leitura):

1. Equipamentos e agentes do filtro SQL (`forCentral`: tipo, situação, bairro, órgão; não arquivados).
2. Ordens abertas desses equipamentos, alertas não resolvidos e seu acompanhamento, missões vigentes por agente,
   metadados (`APP_META`) e marca d'água dos eventos.
3. Urgência de cada ordem ([`MaintenanceUrgency::classify`](../domain/situacoes-e-atualidade.md#maintenanceurgencyclassify)) com a data local.
4. Cada linha vira um recurso ([`equipmentResource`](#centralqueryequipmentresource) / [`agentResource`](#centralqueryagentresource)).
5. **Busca e "precisa de atenção" valem para tudo o que segue**: filtra os recursos por texto (sem acento nem
   maiúsculas) e por `attention ≠ []`; ordens e alertas ficam só os dos recursos visíveis.
6. Indicadores ([`Indicators::compute`](../domain/indicadores-e-alertas.md#indicatorscompute)) sobre os recursos
   visíveis; `needs_attention`, `without_location`, `regions`, `maintenance`, `alerts`, `sync`.

`sync.simulator_running` é verdadeiro se o último ciclo (`last_sim_tick_at`) tem até `max(30, 4 × SIM_INTERVAL_SECONDS)`
segundos. `sync.duplicates_ignored` e `sync.late_events` vêm de `APP_META`.

### `CentralQuery::equipmentResource`

`private equipmentResource(array $e, array $orders, array $alertReasons, DateTimeImmutable $now): array`

- `headline` = "tipo curto · endereço" (rua e referência); sem endereço, o bairro; sem nada, "sem endereço cadastrado"
  — nunca inventa endereço.
- Ordem principal = a de menor `rank` de urgência; `maintenance.urgent_waiting` = há ordem **aberta (não iniciada)**
  imediata ou vencida.
- `attention`: rótulo da situação se indisponível, "Manutenção vencida/imediata" se a ordem principal for urgente e os
  textos dos alertas abertos ([`withAlertReasons`](#centralquerywithalertreasons)).
- `position`: recurso **móvel** com posição observada → `ULTIMA_POSICAO` (com `observed_at` e atualidade de posição) e
  `base` à parte; senão o ponto cadastrado com `kind` = `INSTALADO`, `PLANEJADO` ou `BASE`.
- `highlights`: três primeiras características legíveis (`EquipmentType::describe`).

### `CentralQuery::agentResource`

`private agentResource(array $g, array $alertReasons, DateTimeImmutable $now, ?array $pending = null, ?array $lastAction = null, ?int $missionId = null): array`

- `position`: em turno **e** com posição recebida → `ULTIMA_POSICAO`; senão a **base** (`kind = BASE`). A base não
  indica onde o agente está.
- `attention`: "Indisponível" e os alertas ("Em turno sem posição recente").
- `next_action` = [`AlertFollowUp::nextAction`](../domain/indicadores-e-alertas.md#alertfollowupnextaction) — a mesma
  decisão da ficha completa; `mission_id` = missão vigente.

### `CentralQuery::withAlertReasons`

`private static withAlertReasons(array $attention, array $alertReasons): list<string>` — acrescenta o texto de cada
motivo: `FALHA` "Mau funcionamento", `FURTO` "Furtado", `MANUTENCAO_IMEDIATA`, `MANUTENCAO_VENCIDA`,
`SEM_COMUNICACAO` "Sem sinal recente (não confirma falha)", `SEM_POSICAO` "Em turno sem posição recente"; sem
repetição.

| Método | Contrato |
|---|---|
| `CentralQuery::__construct` | `(Database, EquipmentRepository, AgentRepository, MaintenanceRepository, AlertRepository, MissionRepository, EventRepository, MetaRepository, ReferenceRepository, Clock, int $simIntervalSeconds = 5)`. |
| `CentralQuery::place` | `private static (?string $street, ?string $ref): ?string` — "rua, referência" ou `null`. |
| `CentralQuery::matches` | `private static (array $r, string $text): bool` — busca em código, título, tipo, lugar, endereço, bairro, órgão e marca/modelo. |
| `CentralQuery::fold` | `private static (string $text): string` — minúsculas e sem acentos (á, ã, ç…). |
| `CentralQuery::regionSummary` | `private (array $resources, array $alerts): list<array>` — por bairro ("Sem bairro" quando não há): equipamentos, operacionais, em serviço, indisponíveis, manutenção urgente aguardando, agentes em atividade, alertas e disponibilidade; ordenado por (indisponíveis + urgentes) decrescente e nome. A zona (CENTRO, NORTE…) é agrupamento da demonstração. |
| `CentralQuery::maintenanceLists` | `private (array $orders): array{urgent, upcoming}` — só ordens **aguardando início** (`ABERTA`), como nas seções Urgentes e Próximos 7 dias da Manutenção; a iniciada fica em "Em andamento" lá. |

Volume de demonstração: dezenas de recursos, sem paginação oculta. Testes: `CentralAndAnalysisTest` (mesmo conjunto
para mapa, lista e indicadores; busca e atenção; denominador zero; resumo por bairro), `OperationsTest` (resumo de
urgentes = seção Urgentes).

## Relacionados

[Frontend da central](../frontend/central.md) · [Filtros](validacao-e-filtros.md#filters) ·
[ADR-0004 atualização por consulta periódica](../../../adr/0004-atualizacao-por-consulta-periodica.md)
