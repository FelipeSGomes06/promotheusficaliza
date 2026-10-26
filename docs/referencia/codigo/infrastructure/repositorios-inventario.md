# Repositórios do inventário: equipamentos, agentes e referência

<!-- fontes: src/Infrastructure/Repository/EquipmentRepository.php src/Infrastructure/Repository/AgentRepository.php src/Infrastructure/Repository/ReferenceRepository.php -->

Todo o SQL de equipamentos, agentes, órgãos e bairros. Convenções comuns a todos os repositórios: parâmetros sempre
posicionais; nomes de coluna e ordenações **de listas fechadas** (nunca vêm da requisição); linhas com chaves em
minúsculas e `CHAR` aparados (`rtrim`); `lock()` usa `SELECT … WITH LOCK` e precisa estar dentro de
`Database::transaction`. Tabelas: [`EQUIPMENT`](../../dicionario-de-dados.md#equipment),
[`AGENT`](../../dicionario-de-dados.md#agent), [`AGENCY`, `REGION`](../../dicionario-de-dados.md#agency-e-region).

## `EquipmentRepository`

| Constante | Conteúdo |
|---|---|
| `SELECT` (privada) | `EQUIPMENT` + nome/código do órgão + nome/zona do bairro |
| `WRITABLE` (privada) | colunas graváveis; qualquer outra → `LogicException` ("Coluna não gravável") |
| `CADASTRAL` (privada) | colunas do formulário + arquivamento: mudá-las avança `VERSION_NO` |
| `SORTS` | `codigo`, `tipo`, `situacao`, `bairro`, `atualizado` → `ORDER BY` fixo |

| Método | Contrato |
|---|---|
| `EquipmentRepository::__construct` | `(Database $db)`. |
| `EquipmentRepository::find` | `(int $id): ?array` — linha com órgão e bairro, `specs` decodificado e coordenadas como `float`. |
| `EquipmentRepository::lock` | `(int $id): ?array` — `SELECT * … WITH LOCK`: serializa mudanças concorrentes do mesmo equipamento. |
| `EquipmentRepository::nextCode` | `(string $prefix): string` — `PREFIXO-NNNN` com o maior número já usado + 1, comparado **como número** (em texto, `CAM-9999` viria depois de `CAM-10000` e o código se repetiria). Corrida entre dois cadastros é barrada por `UQ_EQUIPMENT_CODE`. |
| `EquipmentRepository::insert` | `(array $values): int` — só colunas graváveis; `specs` vira JSON. |
| `EquipmentRepository::updateVersioned` | `(int $id, int $expectedVersion, array $values): bool` — `UPDATE … SET …, VERSION_NO = VERSION_NO + 1 WHERE ID = ? AND VERSION_NO = ?`; `false` quando outra pessoa salvou antes. |
| `EquipmentRepository::updateState` | `(int $id, array $values): void` — estado operacional (situação, posição, sinal, arquivamento) sem exigir versão; **avança a versão só se tocar coluna cadastral** (instalação concluída troca o local; arquivamento) ([ADR-0010](../../../adr/0010-versao-do-cadastro-separada-da-telemetria.md)). |
| `EquipmentRepository::touchSignal` | `(int $id, string $seenAt): void` — batimento: `LAST_SEEN_AT` só avança; sem versão nem histórico (controle de volume). |
| `EquipmentRepository::search` | `(Filters $filters, string $text, bool $archived, string $sort, int $page, int $perPage): array{rows, total}` — lista paginada (`OFFSET … FETCH NEXT`) com tipo, situação, bairro e órgão; texto em código, série, marca, modelo, rua e patrimônio (`CONTAINING`, sem diferenciar maiúsculas); arquivados **ou** ativos. |
| `EquipmentRepository::forCentral` | `(Filters $filters): list<array>` — todos os ativos do filtro (sem paginação: volume de demonstração); vazio se o filtro exclui equipamentos. |
| `EquipmentRepository::allActive` | `(): list<array>` — não arquivados (alertas, simulador). |
| `EquipmentRepository::allIncludingArchived` | `(): list<array>` — todos (a avaliação retrospectiva precisa de quem existia em cada corte). |
| `EquipmentRepository::where` | `private static (Filters $filters, bool $archived): array{0: string, 1: list}` — cláusula com `IN (?, …)`. |
| `EquipmentRepository::onlyWritable` | `private static (array $values): array` — chaves em maiúsculas, recusa coluna fora de `WRITABLE`, codifica `SPECS`. |
| `EquipmentRepository::hydrate` | `private static (?array $row): ?array` — apara textos, decodifica `specs`, converte `lat`, `lng`, `pos_lat`, `pos_lng`. |

## `AgentRepository`

Agentes são recursos operacionais fictícios; **não** são contas de acesso.

| Método | Contrato |
|---|---|
| `AgentRepository::SORTS` | `codigo`, `nome`, `funcao`, `situacao`, `bairro`. |
| `AgentRepository::__construct` | `(Database $db)`. |
| `AgentRepository::find` | `(int $id): ?array` — com órgão e bairro; coordenadas como `float`. |
| `AgentRepository::lock` | `(int $id): ?array` — `WITH LOCK` (mudança de situação, arquivamento, missão, multa). |
| `AgentRepository::nextCode` | `(string $prefix): string` — `PREFIXO-NNN`, numérico como no equipamento. |
| `AgentRepository::insert` | `(array $values): int`. |
| `AgentRepository::updateVersioned` | `(int $id, int $expectedVersion, array $values): bool` — bloqueio otimista. |
| `AgentRepository::updateState` | `(int $id, array $values): void` — situação e arquivamento; só o arquivamento avança a versão (a troca de situação, inclusive a do simulador, não invalida um formulário aberto). |
| `AgentRepository::movePosition` | `(int $id, string $lat, string $lng, string $observedAt, string $receivedAt): bool` — posição **só avança no tempo** (`POS_OBSERVED_AT IS NULL OR < ?`); `false` para observação antiga. |
| `AgentRepository::search` | `(Filters, string $text, bool $archived, string $sort, int $page, int $perPage): array{rows, total}` — texto em código, nome de exibição e nome da base. |
| `AgentRepository::forCentral` | `(Filters $filters): list<array>`. |
| `AgentRepository::allActive` | `(): list<array>`. |
| `AgentRepository::where` | `private static` — função (`ROLE`), situação, bairro, órgão. |
| `AgentRepository::onlyWritable` | `private static` — lista fechada de colunas. |
| `AgentRepository::hydrate` | `private static` — apara e converte coordenadas. |

## `ReferenceRepository`

Dados de referência: órgãos e bairros (com a zona de agrupamento da demonstração).

| Método | Contrato |
|---|---|
| `ReferenceRepository::__construct` | `(Database $db)`. |
| `ReferenceRepository::agencies` | `(): list<array{id, code, name, kind}>` — por nome. |
| `ReferenceRepository::regions` | `(): list<array{id, name, zone, center_lat, center_lng}>` — por nome. |
| `ReferenceRepository::agency` | `(int $id): ?array`. |
| `ReferenceRepository::agencyExists` | `(int $id): bool`. |
| `ReferenceRepository::regionExists` | `(int $id): bool`. |
| `ReferenceRepository::insertAgency` | `(string $code, string $name, string $kind): int` — só pela carga de demonstração. |
| `ReferenceRepository::insertRegion` | `(string $name, string $zone, string $lat, string $lng): int` — só pela carga. |
| `ReferenceRepository::trim` | `private static (array $row): array`. |

Não há tela para órgãos e bairros: vêm do [cenário de demonstração](../demo/cenario-e-carga.md#demoscenario).

## Relacionados

[Equipamentos (serviço)](../application/equipamentos.md) · [Agentes (serviço)](../application/agentes.md) ·
[Repositórios de operação](repositorios-operacao.md)
