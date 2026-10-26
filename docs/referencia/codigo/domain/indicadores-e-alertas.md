# Indicadores, regras de alerta e acompanhamento

<!-- fontes: src/Domain/Indicators.php src/Domain/AlertRules.php src/Domain/AlertFollowUp.php -->

Três regras puras que decidem **o que a central conta**, **quando um alerta existe** e **o que fazer agora** com um
alerta de agente. Não acessam banco: recebem linhas já lidas e devolvem arrays. Os serviços
([`CentralQuery`](../application/central.md), [`AlertService`](../application/alertas.md)) e as telas só apresentam o
resultado.

## `Indicators`

### `Indicators::compute`

`static compute(array $equipment, array $agents): array{equipment: array<string, int|float|null>, agents: array<string, int>}`

Entrada: listas `{status, freshness}` dos equipamentos e dos agentes **já filtrados** — o mesmo conjunto do mapa e da
lista ([`CentralQuery::snapshot`](../application/central.md#centralquerysnapshot)). Por isso mapa, lista e números
nunca divergem.

| Chave (`equipment`) | Fórmula |
|---|---|
| `total` | equipamentos não arquivados no filtro (inclui não instalados e desativados) |
| `operational` | situação `OPERACIONAL` |
| `unavailable` | `isUnavailable()`: falha + manutenção + furtado |
| `failure`, `maintenance`, `stolen`, `not_installed`, `deactivated` | contagem de cada situação |
| `in_service` | `isInService()`: operacionais + falha + manutenção + furtado (denominador) |
| `operational_confirmed` | operacionais com sinal `ATUAL` (≤ 10 min) |
| `operational_unconfirmed` | `operational − operational_confirmed` (sem sinal recente; **não é falha**) |
| `availability` | `operational ÷ in_service`, 4 casas; `null` se `in_service = 0` |
| `availability_confirmed` | `operational_confirmed ÷ in_service`; `null` se `in_service = 0` |

| Chave (`agents`) | Fórmula |
|---|---|
| `total` | agentes não arquivados no filtro |
| `active`, `on_pause`, `unavailable`, `off_duty` | contagem por situação |
| `position_not_current` | em turno (atividade ou pausa) com posição diferente de `ATUAL` |

`null` aparece como "não se aplica" na tela (filtro `pct`), nunca como 0% ou 100%. Agentes têm unidade própria e
**nunca são somados** a equipamentos. Testes: `DomainRulesTest`, `CentralAndAnalysisTest` (mesmo conjunto, denominador
zero).

## `AlertRules`

Condições que mantêm um alerta aberto. O alerta nasce quando a condição aparece e é resolvido **automaticamente**
quando ela desaparece; registrar ciência não resolve a causa.

### `AlertRules::conditions`

`static conditions(array $equipment, array $agents, array $openOrders, DateTimeImmutable $now): array<string, Condition>`

Entrada: equipamentos e agentes **ativos** (não arquivados), ordens `ABERTA`/`EM_EXECUCAO` e o instante atual.
Saída: mapa `OPEN_KEY => {kind, resource_id, reason, severity, message}`, com `OPEN_KEY = "<kind>:<id>:<reason>"`
(ex.: `EQUIPAMENTO:7:FALHA`) — a mesma chave da coluna única `ALERT.OPEN_KEY`, que garante **um alerta aberto por
causa**.

| Motivo (`reason`) | Recurso | Gravidade | Condição |
|---|---|---|---|
| `FURTO` | equipamento | `CRITICA` | situação `FURTADO` |
| `FALHA` | equipamento | `ALTA` | situação `FALHA` |
| `SEM_COMUNICACAO` | equipamento | `MEDIA` | espera sinal e `Freshness` = `SEM_INFORMACAO` (> 60 min ou nunca); mensagem diz "situação não confirmada" |
| `MANUTENCAO_IMEDIATA` | equipamento | `ALTA` | alguma ordem aberta com urgência `Imediata` |
| `MANUTENCAO_VENCIDA` | equipamento | `MEDIA` | alguma ordem programada vencida (data local) |
| `SEM_POSICAO` | agente | `MEDIA` | em turno (atividade **ou pausa**) e posição `SEM_INFORMACAO` (> 30 min ou nunca) |

Ordens de equipamentos fora da lista de ativos são ignoradas. Uma ordem já iniciada que seja imediata continua gerando
`MANUTENCAO_IMEDIATA` até ser concluída (a classificação olha só prioridade e data).
Testes: `DomainRulesTest`, `OperationsTest`, `AlertFollowUpTest` (pausa mantém o alerta).

### `AlertRules::resolutionReason`

`static resolutionReason(string $kind, string $reason, ?array $agent): string` — por que a condição deixou de existir,
gravado em `ALERT.RESOLVED_REASON`:

| Caso | Retorno |
|---|---|
| alerta que não é `AGENTE`/`SEM_POSICAO` | `CAUSA_ENCERRADA` |
| agente arquivado (`$agent === null`) | `ARQUIVADO` |
| agente fora de turno | `FORA_DE_TURNO` — **não** quer dizer que a comunicação voltou |
| agente em turno | `POSICAO_RECEBIDA` |

## `AlertFollowUp`

Acompanhamento de um alerta de agente **entre a ciência e o encerramento** e a "próxima ação" mostrada na ficha do
agente e na ficha resumida da central. É a única política: Twig e JavaScript só exibem o resultado
([ADR-0022](../../../adr/0022-acompanhamento-dos-alertas-de-agentes.md)). Ciência e providências não encerram a causa;
contato confirmado não atualiza a localização.

| Constante | Conteúdo |
|---|---|
| `ACTIONS` | providências: `CONTATO_TENTADO` (sem resposta), `CONTATO_CONFIRMADO`, `COMUNICACAO_VERIFICADA` (rádio), `OUTRA` (relato obrigatório) |
| `RESOLUTIONS` | texto de cada `RESOLVED_REASON` (`POSICAO_RECEBIDA`, `FORA_DE_TURNO`, `ARQUIVADO`, `CAUSA_ENCERRADA`); alertas encerrados antes de 24/10/2026 não têm motivo e a tela diz "motivo não registrado" |
| `MAX_CHECK_DAYS` | 7 — a próxima conferência fica entre agora e 7 dias |

### `AlertFollowUp::nextAction`

`static nextAction(array $agent, ?array $alert, ?array $last, DateTimeImmutable $now): array{tone: 'urgent'|'normal'|'calm', what: string, detail: string}`

| Parâmetro | Chaves lidas |
|---|---|
| `$agent` | `status`, `status_changed_at`, `archived_at`, `pos_observed_at` |
| `$alert` | alerta pendente do agente: `state` (`ABERTO`/`RECONHECIDO`), `next_check_at` |
| `$last` | última providência: `action_code` |

Decisão, na ordem:

1. Arquivado → calm, "Nenhuma: agente arquivado."
2. Sem alerta: indisponível → normal, "Registrar o retorno quando ele acontecer" (com "indisponível desde…"); em turno
   → calm, "Nada pendente agora"; fora de turno → calm, "Nada pendente agora" (posição não esperada).
3. Alerta `ABERTO` → urgent, "Registrar ciência e conferir a comunicação com a equipe", com a idade da última posição e
   o aviso de que falta de posição não confirma falha.
4. Reconhecido e conferência vencida (`next_check_at ≤ agora`) → urgent, "Conferência vencida desde …: conferir de
   novo".
5. Reconhecido sem providência → urgent, "Conferir a comunicação com a equipe e registrar a providência".
6. Com conferência futura → normal, "Aguardar a próxima conferência, …".
7. Sem conferência definida → normal, "Aguardar posição recente" (se o último foi contato confirmado) ou "Conferir de
   novo e registrar o resultado".

Quando o último registro é `CONTATO_CONFIRMADO`, o detalhe acrescenta "ainda aguardando posição recente (contato não
atualiza a localização)". Testes: `DomainRulesTest` (pausa, próxima ação, motivo de encerramento),
`AlertFollowUpTest`.

| Método | Contrato |
|---|---|
| `AlertFollowUp::nextAction` | ver acima. |
| `AlertFollowUp::lastAction` | `static (array $followUps): ?array` — última providência (`kind = PROVIDENCIA`) de uma lista do mais antigo ao mais novo; atribuições não contam. |
| `AlertFollowUp::say` | `private static (string $tone, string $what, string $detail): array` — monta o retorno tipado. |

## Relacionados

[Alertas (serviço)](../application/alertas.md) · [Central (consulta)](../application/central.md) ·
[Guia: Alertas e acompanhamento](../../../guias/alertas-e-acompanhamento.md) · [Guia: Visão geral](../../../guias/visao-geral.md)
