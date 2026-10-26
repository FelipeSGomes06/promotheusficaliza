# Casos de uso de agentes

<!-- fontes: src/Application/AgentService.php -->

Agentes de trânsito, guardas e policiais **fictícios** — recursos de campo mostrados no mapa, **não** contas de acesso.
A **base** é um ponto fixo marcado no mapa ou posto no endereço; a **última posição** vem das observações (simulador
ou "Simular recebimento de posição") e fica em colunas próprias (`POS_*`). O bairro é derivado do ponto da base
([ADR-0021](../../../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)).

Rotas: [`AgentController`](../http/controladores.md#agentcontroller). Guia: [Agentes](../../../guias/agentes.md).

## `AgentService`

Dependências: `Database`, `AgentRepository`, `ReferenceRepository`, `History`, `AlertService`, `Clock`, `AddressPoint`.

| Constante | Conteúdo |
|---|---|
| `TRACKED` | `call_sign`, `role`, `agency_id`, `base_name`, `base_street`, `base_ref`, `base_cep`, `region_id`, `base_lat`, `base_lng` |
| `LABELS` | rótulo de cada campo no texto do histórico ("nome de exibição", "rua", "ponto no mapa"…) |
| `PREFIX` | código por função: `TRANSITO` → `AGT`, `GUARDA` → `GCM`, `POLICIA` → `POL` (ex.: `AGT-007`) |
| `POINT` | campos de local da base para `AddressPoint` |

### `AgentService::create`

`create(array $data, ?int $actorId, string $source = 'MANUAL'): int` — situação inicial do formulário (padrão
`FORA_DE_SERVICO`), [validação](#agentservicevalidate), bairro pelo ponto; transação com código `PREFIXO-NNN`,
evento `CADASTRO` e reconciliação de alertas. Código repetido por cadastro simultâneo → `ConcurrencyConflict`.

### `AgentService::update`

`update(int $id, array $data, int $actorId): void` — valida, recusa arquivado (`RuleViolation`), recalcula o bairro se
o ponto mudou, grava com versão otimista (`updateVersioned`) e evento `ALTERACAO` com o resumo legível
([`changeSummary`](#agentservicechangesummary)). Versão antiga → `ConcurrencyConflict`. Situação e posição não mudam a
versão; o arquivamento muda.

### `AgentService::changeStatus`

`changeStatus(int $id, string $target, ?int $actorId, string $source = 'MANUAL', ?string $note = null): void`

- Situação válida; com origem `MANUAL`, motivo obrigatório (1–300).
- Transação: bloqueia o agente; arquivado → `RuleViolation`; mesma situação → nada.
- Atualiza situação e `status_changed_at`, grava `SITUACAO` (`"De → Para: motivo"`) com a origem (`MANUAL` ou
  `SIMULADOR`) e reconcilia os alertas: sair de turno resolve `SEM_POSICAO` como `FORA_DE_TURNO`.

Não há tabela de transições para agentes. O simulador usa este método (pausa e retorno simulados, sem motivo).

### `AgentService::archive`

`archive(int $id, string $reason, int $actorId): void` — motivo 1–300; bloqueia; já arquivado → `RuleViolation`;
`archived_at`, evento `ARQUIVAMENTO`, reconciliação (o alerta pendente se resolve como `ARQUIVADO`). Missões vigentes
**não** são encerradas aqui: ficam em [Planejar reforço](planejamento.md) até alguém concluí-las ou cancelá-las.

### `AgentService::changeSummary`

`static changeSummary(array $fields): string` — "Cadastro alterado: rua, CEP, ponto no mapa." com os rótulos de
`LABELS` (sem repetição). Também refaz, na ficha, o texto de alterações gravadas antes de 23/10/2026 com nomes técnicos
(`AgentController::readableChange`); o evento gravado não muda.

### `AgentService::validate`

`private validate(Input $input, ?array $current = null): array`

| Campo | Regra |
|---|---|
| `call_sign` | obrigatório, até 40 (nome de exibição fictício) |
| `role` | `TRANSITO`, `GUARDA`, `POLICIA` |
| `agency_id` | obrigatório; órgão existente |
| `base_name` | obrigatório, até 80 |
| `base_street` (≤120), `base_ref` (≤160), `base_cep` | opcionais |
| `base_lat`, `base_lng` | ponto obrigatório ao final (mapa ou endereço, [`AddressPoint::resolve`](endereco.md#addresspointresolve)) |

`status` só é lido na criação; `region_id` enviado é ignorado.

| Método | Contrato |
|---|---|
| `AgentService::__construct` | dependências acima. |

Testes: `AgentBaseTest` (ponto pelo mapa ou endereço, bairro), `InventoryTest`, `AlertFollowUpTest` (saída de turno e
arquivamento), `AgentsHttpTest`.

## Relacionados

[Alertas e acompanhamento](alertas.md) · [Ingestão de posições](ingestao.md#eventingestorposition) ·
[HTTP: agente](../../../http.md#agente--post-agentes-e-post-agentesid)
