# Validação de entrada e filtros

<!-- fontes: src/Application/Input.php src/Application/Filters.php src/Application/FineFilters.php -->

Fronteira de confiança da aplicação: tudo o que chega de formulário ou query string passa por estas classes antes de
qualquer regra ou SQL. Leem **só as chaves pedidas** (campos não lidos são ignorados — o cliente nunca define autoria,
origem, versão interna ou papel), normalizam e acumulam erros por campo.

## `Input`

Validação de campos de formulário com erros acumulados. Cada leitor devolve o valor normalizado ou `null`; em erro,
registra a mensagem em `errors()[chave]` e devolve `null`. `assertValid()` lança
[`ValidationFailed`](../erros.md) com todos os erros de uma vez (o formulário mostra todos juntos).

| Símbolo | Contrato |
|---|---|
| `Input::AREA` | Retângulo amplo em torno de Marília: lat −22,40 a −22,00; lng −50,20 a −49,70. **Não** prova que o ponto está dentro do município. Também limita o geocodificador e o retângulo de estudo do planejamento. |
| `Input::__construct` | `(array $data)` — dados crus (corpo ou query). |
| `Input::raw` | `(string $key): mixed` — valor sem tratamento (usado para `specs`, `version`, `command_uid`). |
| `Input::text` | `(string $key, int $max, bool $required = true): ?string` — escalar → string aparada; remove caracteres de controle (exceto quebra de linha); vazio = ausente ("Campo obrigatório." se exigido); mais que `$max` caracteres (contagem multibyte) → "Use no máximo N caracteres.". |
| `Input::int` | `(string $key, int $min, int $max, bool $required = true): ?int` — texto até 12 caracteres e `FILTER_VALIDATE_INT` na faixa, senão "Informe um número inteiro entre MIN e MAX.". |
| `Input::choice` | `(string $key, array $allowed, bool $required = true): ?string` — valor precisa estar na lista fechada ("Opção inválida."). |
| `Input::coordinate` | `(string $key, string $axis, bool $required = true): ?string` — graus decimais (vírgula vira ponto), formato `^-?\d{1,3}(\.\d+)?$`, dentro de `AREA` para o eixo `lat`/`lng`; devolve texto com 6 casas para o `DECIMAL(9,6)`. Erros: "Use graus decimais, ex.: -22.2139." e "Fora da área de Marília/SP atendida pela demonstração.". |
| `Input::cep` | `(string $key, bool $required = false): ?string` — 8 dígitos com ou sem hífen (`^\d{5}-?\d{3}$`), gravado como `00000-000`. Zeros à esquerda preservados. |
| `Input::date` | `(string $key, bool $required = true): ?string` — `AAAA-MM-DD` estrito (ida e volta: `2026-02-31` é recusado). Data local, sem fuso. |
| `Input::localDateTime` | `(string $key, bool $required = true): ?string` — `AAAA-MM-DDTHH:MM[:SS]` do campo `datetime-local`, interpretado como hora de **Marília** e devolvido como instante UTC do banco (`Time::toDb`). |
| `Input::cents` | `(string $key, int $min, int $max, bool $required = false): ?int` — valor em reais à brasileira (`1.234,56`, `293,47`, `130`, aceita prefixo `R$`) convertido para **centavos inteiros sem passar por float**; ponto só como milhar em grupos de três, até duas casas. Fora da faixa → "Informe um valor entre R$ … e R$ …". |
| `Input::flag` | `(string $key): bool` — verdadeiro para `true`, `1`, `'1'`, `'on'`, `'true'` (caixa de confirmação). |
| `Input::fail` | `(string $key, string $message): void` — registra erro de regra cruzada (ex.: local incompatível com a situação). |
| `Input::merge` | `(array $errors): void` — junta erros de outra validação (ex.: `specs.*` do catálogo). |
| `Input::errors` | `(): array<string, string>`. |
| `Input::assertValid` | `(): void` — lança `ValidationFailed($errors)` se houver erro. |

Testes: `InputAndSpecsTest` (CEP, coordenadas, datas, centavos, controle de tamanho).

## `Filters`

Filtros combinados da **central** e das listas de **Equipamentos** e **Agentes**: categoria (tipo de equipamento ou
`AG_<função>`), situação, bairro, órgão e, na central, busca por texto e "só o que precisa de atenção". Valores
desconhecidos ou malformados são descartados e o servidor devolve os filtros **efetivamente aplicados**
(`snapshot.filters`). Classe `final readonly`.

| Símbolo | Contrato |
|---|---|
| `Filters::__construct` | `(list<string> $categories = [], list<string> $statuses = [], list<int> $regions = [], list<int> $agencies = [], string $text = '', bool $attention = false)`. |
| `Filters::fromQuery` | `static (array $query): self` — lê `categoria`, `situacao`, `bairro`, `orgao` (arrays `nome[]` ou texto separado por vírgula), `q` (até 60 caracteres, aparado) e `atencao=1`. Categorias e situações só entram se forem códigos conhecidos; IDs precisam ser inteiros ≥ 1; no máximo 30 valores por filtro (`MAX_VALUES`). |
| `Filters::equipmentTypes` | `(): list<string>` — categorias que são tipos de equipamento. |
| `Filters::agentRoles` | `(): list<string>` — funções (`TRANSITO`…) a partir de `AG_*`. |
| `Filters::equipmentStatuses` | `(): list<string>` — situações de equipamento presentes no filtro. |
| `Filters::agentStatuses` | `(): list<string>` — situações de agente presentes. |
| `Filters::includesEquipment` | `(): bool` — equipamentos entram se o filtro de categoria (quando houver) tiver algum tipo de equipamento **e** o de situação (quando houver) alguma situação de equipamento. |
| `Filters::includesAgents` | `(): bool` — idem para agentes. Ex.: categoria "Radar" exclui agentes; "Agentes ativos" mostra 0. |
| `Filters::isEmpty` | `(): bool` — nenhum filtro (decide o texto "no filtro" e o botão "Limpar"). |
| `Filters::toArray` | `(): array` — filtros aplicados com os nomes da query (`categoria`, `situacao`, `bairro`, `orgao`, `q`, `atencao`); usado em `snapshot.filters` e na paginação. |
| `Filters::pick` | `private static (mixed $raw, array $allowed): list<string>`. |
| `Filters::ids` | `private static (mixed $raw): list<int>`. |

Na central, busca e "precisa de atenção" são aplicadas pela [`CentralQuery`](central.md#centralquerysnapshot) sobre o
conjunto já filtrado no SQL; nas listas, a busca por texto é feita pelos repositórios (`CONTAINING`) e `atencao` não se
aplica. Testes: `CentralAndAnalysisTest` (filtros e busca afetam mapa, lista e indicadores), `InventoryTest`.

## `FineFilters`

Filtros da lista de **Multas**, lidos da query string: só chaves conhecidas, valores de listas fechadas, datas estritas.
Lista, total e os três resumos usam **exatamente** este conjunto
([`FineRepository::search`](../infrastructure/repositorios-multas.md#finerepositorysearch)).

| Símbolo | Contrato |
|---|---|
| `FineFilters::RECORDS` | situação do cadastro: `ativo` (rascunho + registrado, padrão), `rascunho`, `registrado`, `cancelado`, `todos`. |
| `FineFilters::SORTS` | `ocorrencia` (padrão, mais recente primeiro), `codigo` (cadastro mais recente), `atualizado`. |
| `FineFilters::__construct` | privado; use `fromQuery`. Propriedades públicas somente leitura: `q`, `source`, `stage`, `agency`, `radar`, `agent`, `document`, `payment`, `from`, `until`, `record`, `sort`, `attention`; e `errors` (mutável). |
| `FineFilters::fromQuery` | `static (array $query): self` — lê `q` (até 60), `origem`, `etapa`, `orgao`, `radar`, `agente`, `documentacao`, `pagamento`, `de`, `ate`, `cadastro`, `ordem`, `conferir=1`. Data inválida → erro "Data inválida no filtro." e o filtro é ignorado; `de > ate` → erro em `ate` e `ate` ignorado. O controlador responde **422** com o restante do filtro aplicado. |
| `FineFilters::states` | `(): list<string>` — estados de `RECORDS[record]`. |
| `FineFilters::utcRange` | `(): array{0: ?string, 1: ?string}` — período **inclusivo** em datas locais convertido em intervalo UTC **semiaberto** `[00:00 de "de", 00:00 do dia seguinte a "até")`. |
| `FineFilters::moreCount` | `(): int` — quantos filtros de "Mais filtros" estão ativos (abre o painel e mostra o contador). |
| `FineFilters::isEmpty` | `(): bool`. |
| `FineFilters::toArray` | `(): array` — só parâmetros diferentes do padrão; usado na paginação e no "voltar à lista" (a ficha recebe `voltar` como query e **reconstrói** os filtros por `fromQuery`, nunca segue uma URL). |

Testes: `FinesHttpTest` (filtro inválido → 422 preservando o resto; "voltar"), `FinesTest` (contador e lista).

## Relacionados

[Erros](../erros.md) · [HTTP: escrita](../../../http.md#escrita) · [Segurança: entrada](../../../seguranca.md#controles-implementados)
