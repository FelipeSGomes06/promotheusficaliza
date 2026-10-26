# Catálogo de tipos de equipamento

<!-- fontes: src/Domain/EquipmentType.php src/Domain/EquipmentCatalog.php -->

Define os sete tipos de equipamento e o **esquema das características próprias de cada um**, gravadas como JSON
validado na coluna `EQUIPMENT.SPECS` ([ADR-0003](../../../adr/0003-caracteristicas-por-tipo-em-json-validado.md)). O
cliente nunca escolhe o formato gravado: campos desconhecidos são descartados e cada valor é conferido contra o
esquema do tipo.

O catálogo é a fonte única de: lista de tipos do formulário e dos filtros, características mostradas na ficha, ajuda
"?" de cada característica (`tip`, `help`, `optionTips`), prefixo do código interno e indicação de recurso móvel.

## `EquipmentCatalog`

| Método | Contrato |
|---|---|
| `EquipmentCatalog::all` | `static (): array<string, EquipmentType>` — todos os tipos, indexados pelo código, na ordem do catálogo; construído uma vez por processo (memoização estática). |
| `EquipmentCatalog::get` | `static (string $code): ?EquipmentType` — tipo pelo código; `null` se desconhecido. |
| `EquipmentCatalog::build` | `private static (): array<string, EquipmentType>` — monta a lista abaixo. |

| Código | Rótulo | Prefixo | Móvel | Campos (obrigatório em **negrito**) |
|---|---|---|:-:|---|
| `RADAR` | Radar de fiscalização | `RAD` | | **`functions`** (multi: `VELOCIDADE`, `AVANCO_SEMAFORO`, `PARADA_FAIXA`, `CONVERSAO_PROIBIDA`, `FAIXA_EXCLUSIVA`), `modality` (enum `FIXO`/`PORTATIL`), `speed_limit_kmh` (int 20–120 km/h, **obrigatório se `functions` contém `VELOCIDADE`**), `lanes` (int 1–8), `direction` (texto ≤ 80) |
| `CAMERA` | Câmera de monitoramento | `CAM` | | **`resolution`** (enum `720P`, `1080P`, `4MP`, `4K`), `night_vision`, `color_image`, `ptz` (bool), `field_of_view_deg` (int 30–360) |
| `BALANCA` | Balança de pesagem | `BAL` | | **`weighing_mode`** (enum `ESTATICA`, `DINAMICA`), **`max_load_t`** (int 1–200 t), `lanes` (int 1–4) |
| `CANCELA` | Cancela automática | `CAN` | | **`access_control`** (enum `TAG`, `TICKET`, `REMOTO`, `MANUAL`), `arm_length_m` (int 2–8), `anti_crush` (bool) |
| `TOTEM` | Totem de atendimento | `TOT` | | **`services`** (multi: `INFORMACAO`, `BOTAO_AJUDA`, `WIFI`, `RECARGA`), `screen_in` (int 7–86 pol.), `accessible_height` (bool) |
| `VEICULO` | Veículo | `VEI` | ✓ | **`vehicle_role`** (enum `FISCALIZACAO`, `TRANSPORTE`, `EQUIPE`), **`call_sign`** (texto ≤ 20, prefixo fictício, nunca placa), `seats` (int 1–60), `fuel` (enum `FLEX`, `DIESEL`, `ELETRICO`, `HIBRIDO`) |
| `PATINETE` | Patinete elétrico | `PAT` | ✓ | **`range_km`** (int 5–120 km), `max_speed_kmh` (int 6–32 km/h) |

Recurso **móvel** (veículo, patinete): o endereço cadastrado é a **base** (`LOCATION_KIND = BASE`); a posição atual vem
das observações e é guardada à parte (`POS_*`). Fixo nunca usa `BASE`
([`EquipmentService`](../application/equipamentos.md#equipmentservicevalidate)).

**Novo tipo** (procedimento registrado no próprio código e em [domínio](../../../dominio.md#características-por-tipo-specs)):
acrescentar aqui, ampliar `CK_EQUIPMENT_TYPE` numa migração nova, desenhar ícone `public/assets/img/icons.svg#i-<TIPO>`
e miniatura `public/assets/img/miniaturas/<tipo>.svg`.

## `EquipmentType`

Classe `final readonly` com `code`, `label`, `prefix`, `mobile`, `description` e `fields` (lista de `SpecField`). Cada
campo tem `name`, `label`, `kind` (`int|bool|enum|multi|text`) e, conforme o tipo: `required`, `min`/`max`/`unit`
(int), `options` (enum/multi), `max` (texto, em caracteres; padrão 120), `requiredIf` (`[campo => opção]`), `help`
(texto fixo), `tip` e `optionTips` (só interface: a ajuda "?").

| Método | Contrato |
|---|---|
| `EquipmentType::__construct` | `(string $code, string $label, string $prefix, bool $mobile, string $description, array $fields)` — dados imutáveis. |
| `EquipmentType::shortLabel` | `(): string` — primeira palavra do rótulo ("Radar de fiscalização" → "Radar"); lidera títulos "tipo · endereço". |
| `EquipmentType::validateSpecs` | ver abaixo. |
| `EquipmentType::describe` | `(array $specs): list<array{label, value}>` — características legíveis na ordem do esquema: bool → "Sim"/"Não"; multi → rótulos separados por vírgula ("—" se vazio); enum → rótulo; int → valor + unidade; texto como está. Campos ausentes não aparecem. Usado na ficha e nos `highlights` da central (3 primeiros). |
| `EquipmentType::isRequired` | `private (array $field, array $specs): bool` — `required` ou alguma condição de `requiredIf` satisfeita pelos valores **já validados** (por isso `functions` precede `speed_limit_kmh` no esquema). |

### `EquipmentType::validateSpecs`

`validateSpecs(array $input): array{0: array<string, int|bool|string|list<string>>, 1: array<string, string>}`

Entrada: o array `specs[...]` do formulário (`$_POST['specs']`). Saída: `[specs normalizadas, erros]`, com as chaves de
erro no formato `specs.<campo>` (ex.: `specs.speed_limit_kmh`), que o formulário usa para marcar o campo e o resumo de
erros transforma em link `#s-<TIPO>-<campo>`.

Etapas, campo a campo, na ordem do esquema:

1. **bool**: verdadeiro só para `true`, `1`, `'1'`, `'on'`, `'true'`, `'sim'`; qualquer outra coisa é `false`. Nunca
   gera erro (checkbox desmarcado não envia nada).
2. **multi**: mantém só strings únicas; opção fora da lista → "Opção inválida."; lista vazia num campo obrigatório →
   "Escolha ao menos uma opção.".
3. Demais tipos: texto aparado vazio → "Obrigatório para este tipo." se `isRequired`, senão o campo fica ausente.
4. **int**: `FILTER_VALIDATE_INT` dentro de `[min, max]`, senão "Informe um inteiro entre MIN e MAX UNIDADE.".
5. **enum**: valor precisa ser chave de `options`.
6. **text**: até `max` caracteres (padrão 120), senão "Texto longo demais.".

Campos de outros tipos enviados juntos são ignorados (o esquema itera só os campos do tipo). Testes:
`InputAndSpecsTest` (obrigatoriedade condicional do limite do radar, faixas, opções), `InventoryTest`.

Exemplo: radar com `functions = ['VELOCIDADE']` e `speed_limit_kmh` vazio →
`[['functions' => ['VELOCIDADE'], 'lanes'…], ['specs.speed_limit_kmh' => 'Obrigatório para este tipo.']]`.

## Quem usa

- [`EquipmentService::validate`](../application/equipamentos.md#equipmentservicevalidate) — validação no cadastro e na edição.
- `DemoSeeder::seedEquipment` — valida as características do cenário (erro de cenário é `LogicException`).
- `EquipmentController`, `CentralController::filterOptions`, `PageController::about`, `CentralQuery` — rótulos, filtros e destaques.
- `templates/equipment/form.twig` (passo 3, um `fieldset` por tipo) e `templates/_ajuda.twig` (`spec`).

## Relacionados

[Situações](situacoes-e-atualidade.md) · [Guia: Equipamentos](../../../guias/equipamentos.md) ·
[Contrato HTTP do equipamento](../../../http.md#equipamento--post-equipamentos-e-post-equipamentosid)
