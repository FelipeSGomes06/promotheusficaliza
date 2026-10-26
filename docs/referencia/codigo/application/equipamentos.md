# Casos de uso de equipamentos e histórico de recursos

<!-- fontes: src/Application/EquipmentService.php src/Application/History.php -->

`EquipmentService` cadastra, edita, muda a situação e arquiva equipamentos; `History` grava o histórico de recursos
(`RESOURCE_EVENT`) com autoria, instante e antes/depois. Toda escrita acontece numa transação curta e termina
**reconciliando os alertas** ([`AlertService::reconcile`](alertas.md#alertservicereconcile)).

Rotas: [`EquipmentController`](../http/controladores.md#equipmentcontroller). Guia: [Equipamentos](../../../guias/equipamentos.md).

## `EquipmentService`

Dependências: `Database`, `EquipmentRepository`, `ReferenceRepository`, `MaintenanceRepository`, `History`,
`AlertService`, `Clock`, `AddressPoint`.

| Constante | Conteúdo |
|---|---|
| `TRACKED` | campos comparados no histórico de alterações: `type_code`, `manufacturer`, `model`, `serial_number`, `asset_tag`, `agency_id`, `cep`, `street`, `address_ref`, `region_id`, `lat`, `lng`, `location_kind`, `maint_interval_days`, `installed_on`, `notes`, `specs` |
| `POINT` | nomes dos campos de local para [`AddressPoint`](endereco.md#addresspoint) |

### `EquipmentService::create`

`create(array $data, ?int $actorId, string $source = 'MANUAL'): int` — devolve o ID.

1. Situação inicial: `status` do formulário (lista fechada) ou `NAO_INSTALADO`.
2. [`validate`](#equipmentservicevalidate) com essa situação; bairro = `AddressPoint::regionAt(ponto)`.
3. Transação: código `PREFIXO-NNNN` por `EquipmentRepository::nextCode` (maior número já usado + 1, comparado como
   número); `status_changed_at = agora`; `last_seen_at = agora` só se a situação espera sinal; `INSERT`; evento
   `CADASTRO` com os valores; reconcilia alertas.
4. Restrições traduzidas por `persist`: série repetida na mesma marca → erro em `serial_number`; patrimônio repetido →
   erro em `asset_tag`; código repetido por cadastro simultâneo → `ConcurrencyConflict` ("Envie novamente").

`$source` é `MANUAL` no formulário; outras origens (ex.: `SEMENTE`) são possíveis para chamadas internas.

### `EquipmentService::update`

`update(int $id, array $data, int $actorId): void`

- Lê `version` (versão do cadastro que o formulário viu) e o cadastro atual; arquivado → `RuleViolation`.
- Valida (a situação **não** muda por aqui: ela muda pela ficha); recalcula o bairro se o ponto mudou.
- Transação: `updateVersioned` (só grava se `VERSION_NO` ainda for a lida; incrementa a versão). Se nenhuma linha mudou
  → `ConcurrencyConflict` ("O cadastro foi alterado depois que você abriu o formulário.") — o controlador mostra a tela
  de conflito.
- Grava `ALTERACAO` com o diff campo a campo (`History::diff`) quando algo mudou.

**Versão do cadastro** ([ADR-0010](../../../adr/0010-versao-do-cadastro-separada-da-telemetria.md)): só avança com
campos de cadastro e com o arquivamento. Posição, sinal e situação não invalidam um formulário aberto; concluir uma
instalação (planejado → instalado) invalida, porque muda `location_kind`.

### `EquipmentService::pendingChanges`

`pendingChanges(int $id, array $data): array<string, array{antes, depois}>` — o que salvar `$data` mudaria no cadastro
**gravado agora**; base da tabela "Salvo agora × Sua versão" no conflito. Dados inválidos → `[]`.

### `EquipmentService::changeStatus`

`changeStatus(int $id, string $target, string $note, int $actorId): void`

- `target` precisa ser uma situação válida; `note` obrigatório, 1–300 caracteres ("Descreva o motivo…").
- Transação: bloqueia o equipamento (`SELECT … WITH LOCK`); com ordem `EM_EXECUCAO` → `RuleViolation` ("a situação será
  definida ao concluí-la"); aplica por [`applyStatus`](#equipmentserviceapplystatus); reconcilia alertas.

### `EquipmentService::applyStatus`

`applyStatus(array $current, EquipmentStatus $target, string $note, ?int $actorId, string $source): void` — transição
dentro de uma transação já aberta, com a linha **bloqueada**. Também usada pelas ordens de manutenção (início e
conclusão).

- Arquivado → `RuleViolation`; mesma situação → nada; transição fora da tabela → `RuleViolation` "Transição não
  permitida: X → Y.".
- Efeitos colaterais da transição:
  - para `OPERACIONAL` com local `PLANEJADO` → **instalação**: `location_kind = INSTALADO` e `installed_on = hoje local`;
  - de `NAO_INSTALADO` para `OPERACIONAL` com local `BASE` e sem data → **entrada em operação** do recurso móvel
    (`installed_on = hoje`; a base não muda);
  - para `NAO_INSTALADO` com local `INSTALADO` → **reposição**: o ponto vira `PLANEJADO`;
  - para situação que espera sinal → `last_seen_at = agora`.
- `EquipmentRepository::updateState` (avança a versão só se tocar coluna de cadastro) e evento `SITUACAO`
  (`de`, `para`, `motivo`) com a origem recebida.

### `EquipmentService::archive`

`archive(int $id, string $reason, int $actorId, bool $cancelScheduled = false): list<int>` — devolve as ordens
canceladas junto.

- Motivo obrigatório, 1–300.
- Transação: bloqueia; já arquivado → `RuleViolation`; ordem `EM_EXECUCAO` → `RuleViolation` ("Conclua a intervenção,
  informando o resultado, antes de arquivar"); ordens só agendadas exigem `cancelScheduled = true` (senão
  `RuleViolation` listando as ordens). Cada agendada é fechada como `CANCELADA` com "Cancelada no arquivamento do
  equipamento: <motivo>" e autoria, e ganha um evento `MANUTENCAO_CANCELADA`.
- `archived_at = agora` (avança a versão), evento `ARQUIVAMENTO` e reconciliação: o equipamento sai da central, não
  recebe ordens e seus alertas se resolvem. Histórico, ordens e alertas continuam consultáveis. Não há desarquivamento
  pela interface ([ADR-0009](../../../adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)).

### `EquipmentService::validate`

`private validate(Input $input, EquipmentStatus $status, ?array $current = null): array` — campos do formulário
(criação e edição) prontos para gravar.

| Campo | Regra |
|---|---|
| `type_code` | tipo do catálogo |
| `manufacturer`, `model` | obrigatórios, até 80 |
| `serial_number` | opcional, até 60 (único por fabricante no banco) |
| `asset_tag` | opcional, até 30 (único) |
| `agency_id` | obrigatório; órgão existente |
| `cep`, `street` (≤120), `address_ref` (≤160) | opcionais |
| `lat`, `lng` | opcionais no formulário, mas o ponto é **obrigatório** ao final (mapa ou endereço) |
| `location_kind` | `INSTALADO`, `PLANEJADO` ou `BASE` (regras abaixo) |
| `maint_interval_days` | opcional, 7–730 |
| `installed_on` | opcional, `AAAA-MM-DD` |
| `notes` | opcional, até 500 |
| `specs` | [`EquipmentType::validateSpecs`](../domain/catalogo-de-equipamentos.md#equipmenttypevalidatespecs) |

Regras de local: móvel só `BASE`; fixo nunca `BASE`; `NAO_INSTALADO` não pode ser `INSTALADO`; em serviço
(operacional, falha, manutenção, furtado) não pode ser `PLANEJADO`; desativado aceita os dois. Na edição, se o
formulário foi aberto numa versão anterior (`version` ≠ `VERSION_NO`), a regra situação × local não é aplicada — o
conflito de edição explica o que mudou. Depois roda `AddressPoint::resolve` e `assertValid`.

| Método | Contrato |
|---|---|
| `EquipmentService::__construct` | dependências acima. |
| `EquipmentService::persist` | `private (callable $write): mixed` — traduz `ConstraintViolation` conhecidas (ver `create`); outras sobem. |

Testes: `InventoryTest` (cadastro, série por fabricante inclusive concorrente, situação × local, reposição, entrada em
operação, arquivamento), `EquipmentLocationTest`, `MaintenanceInvariantsTest`.

## `History`

Registro de alterações feitas por pessoas ou pelo sistema em `RESOURCE_EVENT` (append-only: a aplicação só lê e
insere).

| Método | Contrato |
|---|---|
| `History::__construct` | `(EventRepository $events, Clock $clock)`. |
| `History::record` | `(string $kind, int $resourceId, string $type, ?int $actor, string $summary, array $payload = [], string $source = 'MANUAL'): void` — grava com `uid = <origem>-<24 hex>`, `occurred_at = received_at = agora`, `outcome = APLICADO`. `kind` = `EQUIPAMENTO`/`AGENTE`; `type` = `CADASTRO`, `ALTERACAO`, `SITUACAO`, `ARQUIVAMENTO`, `MANUTENCAO_ABERTA`, `MANUTENCAO_INICIADA`, `MANUTENCAO_CONCLUIDA`, `MANUTENCAO_CANCELADA`. O resumo é cortado em 240 caracteres pelo repositório. |
| `History::diff` | `static (array $before, array $after): array<string, array{antes, depois}>` — só os campos de `$after` que mudaram; normaliza floats com 6 casas, arrays por JSON e `null` como vazio antes de comparar. |

## Relacionados

[Domínio: situações](../domain/situacoes-e-atualidade.md#equipmentstatus) · [Manutenção](manutencao.md) ·
[Repositórios de inventário](../infrastructure/repositorios-inventario.md) · [HTTP: equipamento](../../../http.md#equipamento--post-equipamentos-e-post-equipamentosid)
