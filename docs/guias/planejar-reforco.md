# Planejar reforço e retrospecto

**Objetivo:** decidir onde concentrar agentes de trânsito na semana, escolher quem enviar e confirmar uma **missão
fictícia**; no retrospecto, ver como a previsão congelada teria se saído em semanas passadas.
**Como acessar:** menu **Planejar reforço**; `/planejar` e `/planejar/retrospecto`. Qualquer conta ativa consulta e
confirma missões.
**Antes de começar:** uma execução importada (`php bin/fiscaliza urban:import`); sem ela a página diz isso e não mostra
números. Método, dados e números do teste: [planejamento por setores](../planejamento-por-setores.md) (canônico);
pipeline de dados: [dados reais e ML](dados-e-ml.md).

> **Real × fictício.** As estimativas vêm de **contagens reais agregadas** de sinistros do Infosiga-SP (selo "Real
> agregado · Infosiga-SP"). Agentes, sugestões e missões são **fictícios** (selo "Fictício"). Prioridade é relativa de
> planejamento: não é probabilidade, emergência nem prova de que reforço evita sinistros.

> **Método na tela:** sempre a **referência histórica** — setores: (registros em 52 semanas + 1) ÷ 53; quadrados de
> 2 km: (registros em 52 semanas + 0,1) ÷ 52,1. O **Poisson** foi treinado e avaliado (candidato), perdeu na escolha
> feita antes do teste e existe só como comparação experimental nos dados; na grade, foi apenas experimentado e
> descartado. A tela não oferece troca de método (o controlador não lê `metodo`).

## O que aparece na tela

| Área | Significado | Origem |
|---|---|---|
| Resumo da semana (faixa escura) | período `[segunda, segunda+7)`, "Reforce primeiro: <bairros>", sinistros esperados ali, até quando vão os dados (corte de informação) e se a previsão é automática | `PlanningService::week` |
| **Como ler estes números** | definições e origem das camadas | — |
| **1. Onde reforçar** | "Quantos lugares a equipe consegue cobrir nesta semana?" (1 a 4 → `quadrados`); mapa de calor com os 64 quadrados e só os prioritários numerados; lista com bairros de referência, setor, parcela dos registros do último ano, esperados na semana e se o quadrado **já tem agente designado** | `URBAN_CELL`; [`pages/planejar.js`](../referencia/codigo/frontend/paginas.md#pagesplanejarjs-página-planejar) |
| Cores do mapa | 5 faixas pela **raiz** da posição entre mínimo e máximo da semana (a previsão é concentrada no centro); o maior sempre vermelho | `band` |
| **Por que este quadrado?** | a conta e as frases sobre o setor | — |
| **2. Quem enviar** | agentes de trânsito em atividade e sem missão, o **sugerido** primeiro; "Por que outros agentes não aparecem?" diz o motivo de cada um | [`PlanningService::candidates`](../referencia/codigo/application/planejamento.md#planningservicecandidates) |
| **3. Confirmar a missão** | sugerido marcado (sem justificativa) ou outro (justificativa obrigatória); ponto de apoio e observação opcionais; caixa "é uma missão fictícia" | [`confirm`](../referencia/codigo/application/planejamento.md#planningserviceconfirm) |
| **Missões** | "agente → quadrado", situação, quem confirmou; **Concluir** ("como foi?") ou **Cancelar** ("por quê?"); todas as vigentes primeiro, das encerradas as 20 recentes; **Detalhes e histórico** | `URBAN_MISSION`, `URBAN_MISSION_LOG` |

Sem a grade importada, o alvo é o **setor** de maior estimativa. Sem JavaScript, a lista ao lado do mapa tem a mesma
informação e os quadrados são links.

## Planejar uma semana

1. Responda quantos lugares a equipe cobre (1 a 4). Toque num quadrado (mapa ou lista) para escolhê-lo
   (`?quadrado=N&quadrados=M#agente`).
2. Em **2. Quem enviar**, veja a ordem: posição atual (≤ 5 min) → posição atrasada (≤ 30 min) → só a base; em cada
   grupo, menor distância **em linha reta** até o quadrado (0 = dentro). Não é rota nem tempo de chegada.
3. Em **3. Confirmar**, mantenha o **Sugerido** ou escolha outro e escreva **Por que escolheu este agente e não o
   sugerido?**. Vale o sugerido que **a tela mostrou**, mesmo que a ordem mude enquanto você preenche.
4. Marque que é uma missão fictícia e **Confirmar missão**.

**Resultado esperado:** missão `CONFIRMADA` com quadrado e setor, método, estimativa, posição e motivo **congelados**,
autor e instante; o agente sai da lista de disponíveis; a ficha do agente mostra a missão com o link para encerrá-la.
A missão começa ao confirmar, não tem horário e **não expira sozinha**: "designado" não quer dizer que o agente chegou.

| Situação | O que a tela informa | Como proceder |
|---|---|---|
| Faltou algo (justificativa, confirmação) | volta no passo 3 com o aviso junto do campo e o que foi digitado (422) | completar |
| Outra pessoa reservou o mesmo agente ao mesmo tempo | aviso de conflito; nada gravado | escolher outro agente |
| Agente deixou de ser elegível (saiu de atividade, foi arquivado) | recusado na confirmação | atualizar a página |
| Semana encerrada | aviso; confirmação recusada até a próxima previsão | aguardar `urban:update` ou importar |
| Missão de semana encerrada ainda confirmada | aviso "o agente continua reservado" | concluir ou cancelar |
| Sem execução importada | estado vazio, sem números | `urban:import` |

## Encerrar uma missão

**Concluir** ou **Cancelar** com motivo (1–300): só missão `CONFIRMADA`; libera o agente; a transição vai para
`URBAN_MISSION_LOG`. Já encerrada → aviso. Arquivar o agente **não** encerra a missão.

## Retrospecto (`/planejar/retrospecto`)

Faixa tracejada grafite/turquesa = **histórico**, não a previsão atual.

1. Escolha uma semana do teste de 2025 (ou semana anterior/próxima). Sem escolher, a página abre na **45ª semana já
   revelada**.
2. Ao escolher uma semana, a coluna "Registrado" fica **oculta** — os números nem vão no HTML — até **Revelar o que
   aconteceu** (`revelar=1`).
3. Depois, a página diz quantos registros houve e quantos caíram no setor priorizado; abaixo, sempre, o resultado do
   teste inteiro e os limites.

Cortes temporais: o método foi **escolhido e fixado em 01/01/2025**, antes do teste; cada semana usa só registros que já
estariam publicados no início dela (ex.: semana de 10/11/2025 → registros até 31/08/2025), e a faixa mostra as duas
datas. Os números vêm de `URBAN_REPLAY`, importados do treino ([`retrospecto.json`](../../ml/README.md)); as previsões
semanais automáticas não têm retrospecto próprio. Uma semana isolada não prova nada; o teste de 2025 **já é
conhecido**.

## Regras da funcionalidade

- Só agentes de trânsito, não arquivados, em atividade e sem missão vigente; guarda e polícia não entram.
- **Uma missão vigente por agente**, garantida pelo índice único parcial `UX_URBAN_MISSION_ACTIVE`, com a linha do agente
  travada para reconferir a elegibilidade.
- O servidor recalcula tudo na confirmação: a execução precisa ser a mais recente e o período não pode ter terminado;
  estimativa, posição e motivo vêm do banco, nunca do navegador.
- O simulador mexe em posição e situação do agente, **nunca** na missão (a reserva não fica em `AGENT`).

## Como funciona no código

Página: `GET /planejar` → `PlanningController::page`/`show` (lê `quadrados` e `quadrado`) →
[`PlanningService::week`](../referencia/codigo/application/planejamento.md#planningserviceweek) (última execução, setores
e quadrados, período) → [`candidates`](../referencia/codigo/application/planejamento.md#planningservicecandidates)
([`SectorForecast::distanceToBoxKm`](../referencia/codigo/domain/setores-e-regioes.md#sectorforecast), atualidade) →
[`missions`](../referencia/codigo/application/planejamento.md#planningservicemissions) → `planning/index.twig` com o JSON
`#plan-data` ([telas](../referencia/codigo/templates/macros-e-telas.md#planningindextwig)).

**Confirmar com disputa pelo mesmo agente:** `POST /planejar/missoes` → `PlanningController::confirm` →
[`PlanningService::confirm`](../referencia/codigo/application/planejamento.md#planningserviceconfirm) → transação: execução
mais recente e período aberto → `SELECT … WITH LOCK` na linha do agente → reconfere elegibilidade e sugerido → `INSERT`
em `URBAN_MISSION` + `URBAN_MISSION_LOG` ([repositórios](../referencia/codigo/infrastructure/repositorios-planejamento.md)).
Se duas confirmações chegam juntas, a segunda espera o lock; ao continuar, ou encontra o agente já reservado, ou o índice
`UX_URBAN_MISSION_ACTIVE` recusa — e vira conflito, sem gravar nada
(`PlanningTest::testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict`, processos separados).
Encerrar: [`close`](../referencia/codigo/application/planejamento.md#planningserviceclose). Retrospecto:
`PlanningController::replay` → [`replay`](../referencia/codigo/application/planejamento.md#planningservicereplay) e
[`replayTimeline`](../referencia/codigo/application/planejamento.md#planningservicereplaytimeline). Dados:
[tabelas `URBAN_*`](../referencia/dicionario-de-dados.md#tabelas-do-planejamento-urban_).

## Verificação e limites

Testes: `PlanningTest` (15), `PlanningHttpTest` (6), `ReviewFlowsHttpTest::testEveryConfirmedMissionKeepsItsCloseActionsAndTheAgentPageLinksToIt`,
`UrbanImportTest`, `UrbanUpdaterTest` ([catálogo](../referencia/codigo/testes/casos.md)). Evidências:
[20/10](../evidencias/2026-10-20-planejar-reforco.md), [mapa de calor](../evidencias/2026-10-22-planejar-mapa-de-calor.md),
[agente sugerido](../evidencias/2026-10-22-planejar-agente-sugerido.md). Limites: snapshot revisado e atraso de publicação
**assumido**; quadrantes e quadrados são recortes experimentais (não bairros nem limite municipal); a lista de quadrados
é quase fixa (centro e arredores); nenhuma evidência causal sobre o efeito de agentes; agentes e missões fictícios.

## Relacionados

[Dados reais e ML](dados-e-ml.md) · [Revisões recomendadas](revisoes-recomendadas.md) · [Agentes](agentes.md) ·
[ADR-0016](../adr/0016-planejamento-preventivo-por-setores.md)
