# Repositórios do planejamento: execuções urbanas e missões

<!-- fontes: src/Infrastructure/Repository/UrbanRepository.php src/Infrastructure/Repository/MissionRepository.php -->

SQL das execuções importadas do treino por setores e das atualizações automáticas (`URBAN_*`, **somente leitura e
inserção**: uma execução gravada não muda) e das missões fictícias de reforço (`URBAN_MISSION`, `URBAN_MISSION_LOG`).
Tabelas: [dicionário](../../dicionario-de-dados.md#tabelas-do-planejamento-urban_).

## `UrbanRepository`

Estimativas em `DECFLOAT(34)` entram pelo **texto mais curto do double** (`json_encode`, `serialize_precision = -1`) e
voltam como `float` idêntico ao do JSON importado.

| Método | Contrato |
|---|---|
| `UrbanRepository::__construct` | `(Database $db)`. |
| `UrbanRepository::findByBundle` | `(string $sha256): ?int` — execução pelo SHA-256 do conjunto (idempotência). |
| `UrbanRepository::insertRun` | `(array $bundle, string $importedAt): int` — grava `URBAN_RUN` (período, referência, emissão, snapshot, métodos, ganho, aviso, avaliação/modelo/proveniência em JSON original, arquivos), os 4 `URBAN_SECTOR`, os 4 `URBAN_FORECAST` e as linhas de `URBAN_REPLAY`. Chamar dentro de uma transação. |
| `UrbanRepository::insertCells` | `(int $runId, array $cells): void` — os 64 `URBAN_CELL`. |
| `UrbanRepository::cellCount` | `(int $runId): int`. |
| `UrbanRepository::topCells` | `(int $runId, int $limit): list<array>` — quadrados com posição ≤ limite, com o nome do setor e a **parcela** dos registros de 52 semanas da área de estudo (`COUNT_52W ÷ soma`). |
| `UrbanRepository::allCells` | `(int $runId): list<array>` — a grade inteira (64), na ordem de prioridade (mapa de calor). |
| `UrbanRepository::cell` | `(int $runId, int $cellNo): ?array` — um quadrado com a posição. |
| `UrbanRepository::latestRun` | `(): ?array` — a execução que a tela usa: período mais recente (`AS_OF DESC`), empate pela importada por último. |
| `UrbanRepository::sectors` | `(int $runId): array<string, array>` — setores com retângulo, centro, previsão e evidências, na ordem importada. |
| `UrbanRepository::referenceRun` | `(): ?array` — a execução de **referência**: a mais recente que tem retrospecto (importada do treino); inclui o `model` decodificado. As semanais automáticas copiam dela método, modelo e avaliação. |
| `UrbanRepository::findSnapshot` | `(string $sha256): ?int`. |
| `UrbanRepository::latestSnapshot` | `(): ?array` — último download mensal guardado, com a auditoria decodificada. |
| `UrbanRepository::insertSnapshot` | `(array $snapshot, array $daily): int` — `URBAN_SNAPSHOT` e as contagens `URBAN_DAILY` (dia, quadrado, contagem). |
| `UrbanRepository::cellCounts` | `(int $snapshotId, string $from, string $through): array<int, int>` — registros por quadrado entre duas datas inclusivas; quadrados sem registro valem 0. |
| `UrbanRepository::monthlyTotals` | `(int $snapshotId): array<string, int>` — registros por mês (`AAAA-MM`) para medir revisões entre downloads. |
| `UrbanRepository::replayWeeks` | `(int $runId): list<string>` — início de cada semana do retrospecto, em ordem. |
| `UrbanRepository::replay` | `(int $runId, ?string $week = null): list<array>` — linhas do retrospecto (todas ou de uma semana), com o observado. |
| `UrbanRepository::dec` | `private static (float $value): string` — texto decimal mais curto do double. |
| `UrbanRepository::coordinate` | `private static (float $value): string` — 6 casas. |
| `UrbanRepository::numbers` | `private static (array $row, array $columns): array` — apara textos e converte colunas numéricas. |
| `UrbanRepository::hydrateRun` | `private static (?array $row): ?array` — ID, ganho e JSONs decodificados. |

## `MissionRepository`

A reserva do agente é a própria missão em `CONFIRMADA` (índice único parcial `UX_URBAN_MISSION_ACTIVE`); a tabela
`AGENT` **não é alterada**, então o simulador nunca apaga uma reserva.

| Método | Contrato |
|---|---|
| `MissionRepository::__construct` | `(Database $db)`. |
| `MissionRepository::insert` | `(array $m): int` — missão `CONFIRMADA` com execução, setor, período, método, estimativa e posição congeladas, agente, seleção (`SUGERIDO`/`MANUAL`), motivo, justificativa, ponto de apoio, observação, autoria e quadrado (opcional). |
| `MissionRepository::lock` | `(int $id): ?array` — `WITH LOCK`. |
| `MissionRepository::find` | `(int $id): ?array` — com agente, setor e nomes de quem confirmou/encerrou. |
| `MissionRepository::close` | `(int $id, string $state, string $at, int $by, string $reason): bool` — só se ainda `CONFIRMADA`; `false` se já encerrada. |
| `MissionRepository::activeForAgent` | `(int $agentId): ?int` — missão vigente do agente. |
| `MissionRepository::activeByAgent` | `(): array<int, int>` — agente → missão vigente (central e candidatos). |
| `MissionRepository::recent` | `(int $closedLimit = 20): list<array>` — **todas** as vigentes e as 20 encerradas mais recentes (um limite único deixava vigentes fora da lista — achado R07, ADR-0023). |
| `MissionRepository::log` | `(int $missionId, ?string $from, string $to, int $actor, string $at, ?string $note): void` — transição em `URBAN_MISSION_LOG`. |
| `MissionRepository::logs` | `(array $missionIds): array<int, list<array>>` — transições por missão, com o nome de quem fez. |
| `MissionRepository::hydrate` | `private static (?array $row): ?array`. |

## Relacionados

[Planejamento (serviço)](../application/planejamento.md) · [Importação](../application/importacao-urbana.md) ·
[Atualização](../application/atualizacao-urbana.md)
