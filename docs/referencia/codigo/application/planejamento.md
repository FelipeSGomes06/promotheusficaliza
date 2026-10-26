# Planejar reforço: semana, candidatos, missão e retrospecto

<!-- fontes: src/Application/PlanningService.php -->

`PlanningService` serve a tela **Planejar reforço** ([ADR-0016](../../../adr/0016-planejamento-preventivo-por-setores.md)):
a semana prevista pela execução mais recente, a explicação pelas evidências, a sugestão de agente de trânsito, a
**missão fictícia** confirmada pelo gestor e o **retrospecto** do teste. Camadas de dados: contagens de sinistros
**reais e agregadas** (Infosiga-SP); agentes, posições, sugestões e missões **fictícios**.

A sugestão é uma regra operacional simples, separada do modelo: agentes de trânsito em atividade e sem missão
vigente, ordenados pela confiabilidade da posição (atual → atrasada → só a base) e pela distância em linha reta até o
alvo. Nada é enviado a pessoas.

Rotas: [`PlanningController`](../http/controladores.md#planningcontroller). Guia: [Planejar reforço](../../../guias/planejar-reforco.md).

| Constante | Conteúdo |
|---|---|
| `STATES` | `CONFIRMADA`, `CONCLUIDA`, `CANCELADA` → rótulos |
| `MAX_CELLS` | 4 — quadrados prioritários que a tela pode mostrar |
| `POINT` (privada) | camada do ponto de referência do agente: 0 posição atual, 1 posição atrasada, 2 base de trabalho (sem posição recente) |

## `PlanningService::week`

`week(?string $method): ?array` — `null` se nenhuma execução foi importada. Usa `UrbanRepository::latestRun` (período
mais recente; empate, a importada por último) e devolve: `run` (com `last_day` = fim exclusivo − 1), `method`
(o pedido, se for um de `SectorForecast::METHODS`, senão o escolhido no ajuste), `sectors` ordenados pelo método com
explicação ([`ordered`](#planningserviceordered)), `cells` (os 4 quadrados de 2 km de maior estimativa, com bairros de
referência), `grid` (os 64, para o mapa de calor) e `period` ([`period`](#planningserviceperiod)). A tela pede sempre
`baseline` (a média simples); o Poisson experimental fica nos dados e no treino.

## `PlanningService::candidates`

`candidates(array $sector): array{eligible: list<array>, unavailable: list<array>}` — alvo = setor ou quadrado
(`area_label = 'quadrado'`). Só agentes **de trânsito** ativos (não arquivados) entram; guarda e polícia não são
escalados por proximidade. Fora de "Em atividade" → `unavailable` com a situação como motivo; com missão vigente →
`unavailable` "Reservado na missão fictícia #N". Elegíveis ordenados por (camada, distância, código), via
[`candidate`](#planningservicecandidate).

## `PlanningService::confirm`

`confirm(array $data, int $actorId): int` — confirma uma missão fictícia; devolve o ID.

| Campo | Regra |
|---|---|
| `run_id` | precisa ser a execução **mais recente** ("A previsão foi atualizada depois que você abriu a tela…") |
| `sector_id` | `NO`, `NE`, `SO`, `SE` |
| `method` | `baseline` ou `poisson` |
| `agent_id` | agente escolhido |
| `suggested_agent_id` | o sugerido que a tela mostrou (opcional); só decide se a justificativa é exigida |
| `justification` | ≤ 300; obrigatória se o agente não for o sugerido mostrado **nem** o primeiro elegível do momento |
| `support_point` | ≤ 160, opcional |
| `note` | ≤ 300, opcional |
| `cell_no` | 0–63, opcional; precisa pertencer ao setor |
| `confirm` | caixa obrigatória ("missão fictícia de demonstração") |

Numa transação: período da execução já encerrado → `RuleViolation` ("Importe uma execução nova…"); **trava a linha do
agente** e reconfere: de trânsito, não arquivado, em atividade, sem missão vigente. Estimativa, posição no ranking e
motivo vêm do banco, nunca do navegador. Grava `URBAN_MISSION` em `CONFIRMADA` (seleção `SUGERIDO` ou `MANUAL`, motivo
"Quadrado N (bairros), X registros esperados…" + setor + agente + camada + distância, até 300) e o log da transição.
O índice único parcial `UX_URBAN_MISSION_ACTIVE` (uma missão `CONFIRMADA` por agente) barra a corrida que escapar da
trava: "Outra pessoa acabou de reservar <agente>. Escolha outro agente.". A missão **não mexe na tabela `AGENT`**: o
simulador nunca apaga uma reserva.

Limite registrado no código: `suggested_agent_id` vem do cliente e decide apenas se a justificativa é exigida
(auditoria, não segurança).

## `PlanningService::close`

`close(int $missionId, string $state, string $reason, int $actorId): void` — `CONCLUIDA` ou `CANCELADA`, motivo 1–300;
trava a missão; só `CONFIRMADA` muda ("já está concluída/cancelada; nada foi alterado"); grava encerramento e log.
Libera o agente para novas sugestões. A missão não expira sozinha: semana encerrada ainda confirmada continua
reservando o agente até alguém encerrá-la.

## `PlanningService::missions`

`missions(): list<array>` — todas as vigentes e as 20 encerradas mais recentes, com o log de cada uma e o rótulo do
quadrado ("Quadrado N · bairros").

## `PlanningService::replay`

`replay(?string $week, ?string $method, bool $reveal): ?array` — retrospecto de uma semana do **teste** com as
previsões do **modelo congelado**, sempre da **execução de referência** (a mais recente com retrospecto; as semanais
automáticas não têm). Sem semana válida, abre na 45ª (ou na última). Devolve setores com posição pelos dois métodos,
estimativas, contagens de 13/52 semanas e `history_through`; `observed` **só com `$reveal`** — sem revelar, os
registros observados **nem saem do servidor**. Com revelação: total observado, registros no 1º de cada método e o
máximo da semana. Inclui navegação (anterior/próxima), linha do tempo ([`replayTimeline`](#planningservicereplaytimeline))
e placar ([`replayScore`](#planningservicereplayscore)).

## `PlanningService::replayTimeline`

`private replayTimeline(int $runId, string $method, int $last): list<array>` — semana a semana até o índice `$last`:
acertou quando o setor priorizado teve o **maior** número de registros da semana (empates contam); semanas sem registro
não entram na taxa; acumula a fração capturada.

## `PlanningService::replayScore`

`private static replayScore(array $timeline): array` — semanas, semanas contadas, acertos, taxa de acerto e fração
capturada acumulada.

## `PlanningService::ordered`

`private static ordered(array $sectors, string $method): array` — setores na ordem do método com `value`, `rank`,
`other_rank` e duas frases de explicação: o ritmo recente (13 semanas) acima, abaixo ou perto (±10%) da média de 52
semanas; e como o método chega ao número (`(n52 + 1) ÷ 53` na referência; no Poisson, que o modelo combina setor,
ritmos, época do ano e tendência e "não decompõe a estimativa por variável"). Associação, nunca causa.

## `PlanningService::candidate`

`private static candidate(array $agent, array $sector, DateTimeImmutable $now): array` — ponto de referência do agente:
posição atual (≤ 5 min) ou atrasada (≤ 30 min) se houver; senão a **base**, dito como tal (posição antiga não vira
"localização atual"). Distância em linha reta até o retângulo
([`SectorForecast::distanceToBoxKm`](../domain/setores-e-regioes.md#sectorforecast)); < 50 m = "dentro do setor/quadrado".

## `PlanningService::period`

`private static period(array $run, string $today): string` — `FUTURA` antes do início, `VIGENTE` durante, `ENCERRADA` a
partir do fim **exclusivo** (`UNTIL_DATE`). Encerrada: a tela avisa e a confirmação é recusada.

| Método | Contrato |
|---|---|
| `PlanningService::__construct` | `(Database, UrbanRepository, MissionRepository, AgentRepository, ReferenceRepository, Clock)`. |
| `PlanningService::places` | `private (array $cell): array{inside: list<string>, near: ?string}` — até três bairros cujo centro cai dentro do quadrado (o mais central primeiro) ou, se nenhum, o mais próximo. Nomes para se localizar, não limites. |
| `PlanningService::placeLabel` | `static (array $places): string` — "Centro, Fragata" ou "perto de …". |
| `PlanningService::method` | `private static (?string $method, array $run): string` — método válido ou o da execução. |
| `PlanningService::fmt` | `private static (float $value): string` — uma casa decimal com vírgula. |
| `PlanningService::brDate` | `private static (string $ymd): string`. |
| `PlanningService::addDays` | `private static (string $date, int $days): string`. |

Testes: `PlanningTest` (ordem, candidatos, missão com processos concorrentes → uma missão e um conflito, período
encerrado, retrospecto oculto), `PlanningHttpTest` (registros ocultos até revelar, aviso de semana encerrada, datas do
retrospecto), `ReviewFlowsHttpTest` (missões vigentes acima de 20).

## Relacionados

[Previsão por setores (domínio)](../domain/setores-e-regioes.md) · [Repositórios de planejamento](../infrastructure/repositorios-planejamento.md) ·
[Método e resultados](../../../planejamento-por-setores.md) · [HTTP: planejar](../../../http.md#planejar-reforço)
