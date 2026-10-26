# Manutenção

**Objetivo:** abrir, programar, iniciar, concluir e cancelar ordens de manutenção dos equipamentos, sabendo o que é
urgente e garantindo uma intervenção por vez.
**Como acessar:** menu **Manutenção** (`/manutencao`, a fila); as ações ficam na fila e na ficha do equipamento
(seção Manutenção). Qualquer conta ativa.

## O que aparece na tela

| Seção | O que entra | Regra |
|---|---|---|
| **Em andamento** | ordens `EM_EXECUCAO` | uma por equipamento |
| **Urgentes** | ordens abertas **imediatas** ou programadas **vencidas** | [`MaintenanceUrgency`](../referencia/codigo/domain/situacoes-e-atualidade.md#maintenanceurgency); datas locais de Marília |
| **Próximos 7 dias** | programadas até hoje + 7 dias (hoje inclusive) | idem |
| **Programadas depois** | o resto das programadas | idem |
| **Encerradas recentemente** | as 20 últimas concluídas ou canceladas | — |

Cada ordem: "tipo · endereço", código do equipamento, número da ordem (`#N`), motivo, prazo; quando não pode começar,
"Ainda não pode ser iniciada" com o que falta, ou "Aguardando: a ordem #N está em execução". O resumo "manutenções
urgentes" da Visão geral conta as mesmas ordens de **Urgentes** (as que ainda não começaram).

## Abrir uma ordem

1. Ficha do equipamento → **Manutenção → Abrir ordem de manutenção**.
2. Tipo (**corretiva** ou **preventiva**), prioridade (**imediata** ou **programada**, esta com data — não no passado),
   motivo (1–300).

**Resultado esperado:** ordem `ABERTA`, evento `MANUTENCAO_ABERTA` no histórico do equipamento; a situação **não**
muda; imediata abre o alerta "Manutenção imediata". Pode-se agendar em qualquer situação, até em não instalado,
desativado ou furtado. Equipamento arquivado → recusado.

## Iniciar a intervenção

1. Na fila ou na ficha, **Iniciar** ("Iniciando…").

**Resultado esperado:** ordem `EM_EXECUCAO`, equipamento em **Em manutenção**, evento `MANUTENCAO_INICIADA`.

| Situação | Resposta | Como proceder |
|---|---|---|
| Outra ordem do mesmo equipamento em execução | "A ordem #M já está em execução neste equipamento. Conclua-a…" | concluir a outra primeiro |
| Não instalado, desativado ou furtado | o botão não aparece; "Ainda não pode ser iniciada" com o que falta (instalação, voltar a uso, reposição) ou cancelar | resolver a situação ou cancelar a ordem |
| Ordem não está aberta | recusado | — |
| Dois cliques/duas pessoas ao mesmo tempo | só uma vale (índice `UX_MAINT_ONE_RUNNING`) | a outra recebe a mesma mensagem |

## Concluir

1. **Concluir esta intervenção**: o que foi feito (1–500) e **como o equipamento ficou** — operacional, continua com
   mau funcionamento, ou desativado.

**Resultado esperado:** ordem `CONCLUIDA`, a situação informada passa a valer, evento `MANUTENCAO_CONCLUIDA`. Concluir
"em falha" é aceito (a ordem fecha e o equipamento fica em mau funcionamento; abra nova corretiva se precisar).
Preventiva de equipamento com **periodicidade** (e resultado ≠ desativado, sem outra preventiva aberta) agenda a próxima
para **hoje + intervalo**, com origem "sistema".

## Cancelar

1. Na ficha (o link da fila leva direto à ordem), **Cancelar esta ordem**, motivo (1–500).

**Resultado esperado:** `CANCELADA` com o motivo; evento no histórico. Só ordens **não iniciadas**: uma intervenção
iniciada precisa ser concluída com o resultado real.

## Estados e exceções

| Situação | Comportamento |
|---|---|
| Equipamento com intervenção em execução | não muda de situação pela ficha ("a situação será definida ao concluí-la") nem pode ser arquivado |
| Arquivar com ordens agendadas | exige "Cancelar também as ordens agendadas"; cada uma cancelada com o motivo e autoria ([equipamentos](equipamentos.md#arquivar)) |
| Histórico longo | a ficha mostra **todas** as agendadas e em execução, por mais antigas; das encerradas, 3 à vista e "Ver ordens encerradas mais antigas" (até 30, com o total) |
| Ação falhou | aviso vermelho e volta à página de origem (`voltar` interno) |
| Sem JavaScript | formulários comuns; aviso no topo após o redirecionamento |

## Regras da funcionalidade

- Programada sempre tem data (`CK_MAINT_DUE`); `CLOSED_AT` existe se e somente se concluída ou cancelada.
- Uma intervenção em execução por equipamento, garantida pelo banco mesmo fora do serviço
  ([ADR-0009](../adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)).
- Iniciar só onde a transição para "Em manutenção" existe ([`maintenanceStartBlocker`](../referencia/codigo/domain/situacoes-e-atualidade.md#equipmentstatusmaintenancestartblocker)).
- Urgência por data **local** de Marília; vira à meia-noite de São Paulo, não de UTC.

## Como funciona no código

Abrir: `POST /equipamentos/{id}/manutencoes` → `OperationsController::open` →
[`MaintenanceService::open`](../referencia/codigo/application/manutencao.md#maintenanceserviceopen) (trava o equipamento,
`INSERT` em `MAINTENANCE_ORDER`, evento, reconciliação). Iniciar: `POST /manutencoes/{id}/iniciar` →
`OperationsController::start` → [`MaintenanceService::start`](../referencia/codigo/application/manutencao.md#maintenanceservicestart)
→ `lockOrder` (trava **equipamento e depois ordem**, sempre nessa ordem) → bloqueador de início → "já em execução?" →
`EM_EXECUCAO` (o índice parcial `UX_MAINT_ONE_RUNNING` é a última barreira: uma violação vira a mesma mensagem via
[`Database::translate`](../referencia/codigo/infrastructure/banco-e-migracoes.md#databasetranslate)) →
[`EquipmentService::applyStatus`](../referencia/codigo/application/equipamentos.md#equipmentserviceapplystatus)
(`MANUTENCAO`) → reconciliação → 303 para `voltar` (só `/manutencao`, `/central` ou `/equipamentos/{id}` com âncora;
senão `/manutencao`). Concluir/cancelar: [`complete`](../referencia/codigo/application/manutencao.md#maintenanceservicecomplete),
[`cancel`](../referencia/codigo/application/manutencao.md#maintenanceservicecancel). Fila:
`OperationsController::maintenance`. Dados: [`MAINTENANCE_ORDER`](../referencia/dicionario-de-dados.md#maintenance_order).

**Tentativa concorrente.** Duas sessões iniciando ordens do mesmo equipamento ao mesmo tempo: a primeira trava o
equipamento; a segunda espera o lock (até `DB_STATEMENT_TIMEOUT_SECONDS`) e, ao ganhar a vez, encontra a ordem em
execução e recebe a mensagem — ou, se a espera estourar, "Registro ocupado por outra operação". Provado com processos
separados em `MaintenanceInvariantsTest::testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne` e
`testStartRacingArchiveNeverLeavesAnUnfinishableOrder`.

## Verificação e limites

Testes: `MaintenanceInvariantsTest` (9), `OperationsTest::testMaintenanceLifecycle`,
`testUrgentSummaryCountsOnlyOrdersWaitingToStart`, `testCompletionCanReportThatEquipmentStillFails`,
`DomainRulesTest::testMaintenanceUrgencyUsesLocalDates`, `ReviewFlowsHttpTest::testMaintenanceStartIsOfferedOnlyWhenTheEquipmentCanEnterMaintenance`,
`testAnOldPendingOrderStaysReachableBehindThirtyClosedOnes` ([catálogo](../referencia/codigo/testes/casos.md)). Evidência:
[revisão de 24/10](../evidencias/2026-10-24-revisao-logica-e-fluxos.md). Limites: ordens e equipes são fictícias; não há
agenda de técnicos, custos ou peças; a fila não pagina as encerradas além das 20 recentes.

## Relacionados

[Equipamentos](equipamentos.md) · [Revisões recomendadas](revisoes-recomendadas.md) · [Alertas](alertas-e-acompanhamento.md)
