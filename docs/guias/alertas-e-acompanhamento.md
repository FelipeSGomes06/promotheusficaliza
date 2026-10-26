# Alertas e acompanhamento

**Objetivo:** saber que algo precisa de atenção, registrar que alguém viu, combinar quem acompanha e anotar o que foi
feito — sem confundir isso com resolver a causa.
**Onde aparece:** Visão geral (resumo "precisam de atenção", ficha resumida, avisos de alertas novos), ficha do
equipamento (bloco Alertas), ficha do agente (**Próxima ação** e **Acompanhamento do alerta**). Não há aba própria.
Qualquer conta ativa.

> **Ciência ≠ resolução.** Registrar ciência só informa que alguém viu. O alerta **só encerra quando a causa acaba** (ou
> o recurso é arquivado). Contato confirmado com a equipe também não atualiza a localização.

## Quando um alerta existe

Os alertas são calculados a partir do estado atual por [`AlertRules::conditions`](../referencia/codigo/domain/indicadores-e-alertas.md#alertrulesconditions)
e mantidos por [`AlertService::reconcile`](../referencia/codigo/application/alertas.md#alertservicereconcile), que roda na
mesma transação de toda escrita (cadastro, situação, manutenção, ingestão, simulador).

| Motivo | Recurso | Gravidade | Nasce quando | Encerra quando |
|---|---|---|---|---|
| Furto | equipamento | crítica | situação `FURTADO` | sai de furtado |
| Mau funcionamento | equipamento | alta | situação `FALHA` | sai de falha |
| Sem comunicação | equipamento que espera sinal | média | sem sinal há mais de 60 min ("situação não confirmada") | sinal volta |
| Manutenção imediata | equipamento | alta | ordem aberta imediata (inclusive já iniciada) | ordem concluída/cancelada |
| Manutenção vencida | equipamento | média | ordem programada com data local vencida | ordem concluída/cancelada |
| Sem posição | agente | média | **em turno** (atividade ou pausa) e sem posição há mais de 30 min | posição recente, saída de turno ou arquivamento |

Um alerta aberto por causa: `OPEN_KEY = "<tipo>:<id>:<motivo>"` é único no banco, então duas reconciliações simultâneas
nunca duplicam ([dicionário](../referencia/dicionario-de-dados.md#alert-e-alert_followup)). Pausa **mantém** o mesmo
alerta (não resolve e reabre).

## Registrar ciência

1. Na ficha resumida da Visão geral ou na ficha (equipamento ou agente), **Registrar ciência**; no agente, nota
   opcional (até 300).
2. Confirmar.

**Resultado esperado:** estado `RECONHECIDO` com seu nome e hora. O alerta **continua** na lista de pendentes; ninguém é
atribuído.

| Situação | Resposta |
|---|---|
| Alerta já resolvido | "já foi resolvido: a causa deixou de existir" (JSON: 409) |
| Já reconhecido por outra pessoa (agente) | ficha volta (409) e a sua nota vai para o formulário de **providência** |
| Alerta de outro recurso na URL do agente | 404 |
| Conexão caiu antes da resposta (Visão geral) | "resultado não confirmado"; conferir antes de repetir |

## Acompanhar um alerta de agente

Na ficha do agente, bloco **Acompanhamento do alerta** com três fatos lado a lado: ciência, responsável e próxima
conferência ([ADR-0022](../adr/0022-acompanhamento-dos-alertas-de-agentes.md)).

1. **Atribuir a / Reatribuir para:** escolha uma **conta ativa** cadastrada em [Contas](acesso-e-contas.md) (uma conta
   nova aparece na lista sem mais nada). Independe da ciência. A troca fica registrada (de quem para quem, quem trocou,
   quando).
2. **Registrar providência** (só **depois da ciência**): o que foi feito **por outro meio** — contato tentado (sem
   resposta), contato confirmado, comunicação verificada (rádio) ou outra (relato obrigatório) —, relato até 500 e, se
   quiser, a **próxima conferência** (hora de Marília, depois de agora, até 7 dias; substitui a anterior). Os registros
   anteriores ficam.
3. **Encerramento** é automático: posição recente → "Encerrado: posição recente recebida"; saída de turno → "Deixou de
   se aplicar: o agente saiu de turno" (**não** quer dizer que a comunicação voltou); arquivamento → "arquivado".
   Encerrados ficam em **Alertas encerrados** com o motivo; os de antes de 24/10/2026 dizem "motivo não registrado".

**Próxima ação** (mesma decisão na ficha resumida e na ficha completa, por
[`AlertFollowUp::nextAction`](../referencia/codigo/domain/indicadores-e-alertas.md#alertfollowupnextaction)), na ordem:
alerta aberto → "Registrar ciência e conferir a comunicação com a equipe" (urgente); reconhecido com conferência vencida
→ "conferir de novo"; reconhecido sem providência → "Conferir a comunicação com a equipe e registrar a providência";
com conferência futura → aguardar; sem conferência → "Aguardar posição recente" (se o último foi contato confirmado) ou
"Conferir de novo e registrar o resultado". O responsável **não** entra nessa decisão; uma conta desativada aparece no
bloco de acompanhamento como "conta desativada: reatribua".

| Situação | O que a tela informa | Como proceder |
|---|---|---|
| Outra pessoa mexeu no acompanhamento enquanto você preenchia | "O acompanhamento deste alerta mudou depois que você abriu a ficha…" (409) e o que você digitou fica | conferir e enviar de novo |
| Alerta encerrou durante o preenchimento | "Este alerta foi encerrado em … enquanto você preenchia" com o motivo | nada a fazer |
| Mesma conta, conta inexistente ou desativada | erro no campo (422) | escolher outra conta ativa |
| Providência antes da ciência | "Registre a ciência antes de registrar providências." (409) | registrar ciência |
| "Outra" sem relato, data fora do prazo | erro no campo, digitação mantida | corrigir |
| Responsável teve a conta desativada depois | "conta desativada: reatribua"; não pode receber atribuição nova | reatribuir |

Para demonstrar uma resolução: **Mais ações → Simular recebimento de posição** ([agentes](agentes.md#simular-recebimento-de-posição)).

## Regras da funcionalidade

- Responsável é sempre uma **conta** (`APP_USER`), nunca um agente.
- `FOLLOWUP_NO` é a versão do acompanhamento: cada atribuição ou providência a incrementa; formulário com versão antiga
  é recusado sem perder a digitação.
- `ALERT_FOLLOWUP` é só de acréscimo (o usuário da aplicação não tem `UPDATE`/`DELETE`).
- Equipamentos têm ciência, mas não responsável nem providências (o acompanhamento é dos alertas de agente).
- O sistema **não envia** mensagem, e-mail, rádio ou notificação a ninguém.

## Como funciona no código

Ciência (central): `POST /alertas/{id}/reconhecer` (JSON, `X-CSRF-Token`) → `OperationsController::acknowledge` →
[`AlertService::acknowledge`](../referencia/codigo/application/alertas.md#alertserviceacknowledge) → `ALERT`
(`STATE`, `ACK_*`). Ciência (agente): `POST /agentes/{id}/alertas/{alerta}/ciencia` → `AgentController::acknowledgeAlert`.
Responsável: `…/responsavel` → `AgentController::assignAlert` → [`AlertService::assign`](../referencia/codigo/application/alertas.md#alertserviceassign)
(trava o alerta, confere `version`, conta ativa) → `ALERT.ASSIGNEE_ID`, `FOLLOWUP_NO + 1`, `ALERT_FOLLOWUP` `ATRIBUICAO`.
Providência: `…/providencias` → `AgentController::recordFollowUp` →
[`AlertService::recordFollowUp`](../referencia/codigo/application/alertas.md#alertservicerecordfollowup) →
`ALERT.NEXT_CHECK_AT`, `ALERT_FOLLOWUP` `PROVIDENCIA`. Encerramento: qualquer escrita → `reconcile` →
[`AlertRules::resolutionReason`](../referencia/codigo/domain/indicadores-e-alertas.md#alertrulesresolutionreason) →
`RESOLVED_REASON`. Erros 422/409 reapresentam a ficha com o formulário aberto (`AgentController::alertAction`).

## Verificação e limites

Testes: `AlertFollowUpTest` (10 casos), `OperationsTest::testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve`,
`testConcurrentReconcileNeverDuplicatesAnOpenAlert`, `AgentsHttpTest::testFollowUpOnTheAgentPageKeepsWhatWasTypedOnEveryRefusal`,
`DomainRulesTest::testNextActionFollowsTheAlertFromOpenToFollowUp` ([catálogo](../referencia/codigo/testes/casos.md)).
Evidência: [24/10/2026](../evidencias/2026-10-24-acompanhamento-de-alertas.md) e o script
`scripts/verificacao-interface/acompanhamento.cjs`. Limites: o "rádio" e as posições são simulados; providências são
anotações do que alguém diz ter feito fora do sistema.

## Relacionados

[Agentes](agentes.md) · [Visão geral](visao-geral.md) · [Contas](acesso-e-contas.md) · [Domínio](../dominio.md)
