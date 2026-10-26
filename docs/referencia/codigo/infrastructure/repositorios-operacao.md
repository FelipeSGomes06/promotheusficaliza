# Repositórios da operação: manutenção, alertas, eventos e metadados

<!-- fontes: src/Infrastructure/Repository/MaintenanceRepository.php src/Infrastructure/Repository/AlertRepository.php src/Infrastructure/Repository/EventRepository.php src/Infrastructure/Repository/MetaRepository.php -->

SQL das ordens de manutenção, dos alertas e seu acompanhamento, do histórico de recursos e dos metadados da
instalação. Convenções gerais em [repositórios do inventário](repositorios-inventario.md). Tabelas:
[`MAINTENANCE_ORDER`](../../dicionario-de-dados.md#maintenance_order), [`ALERT` e `ALERT_FOLLOWUP`](../../dicionario-de-dados.md#alert-e-alert_followup),
[`RESOURCE_EVENT`](../../dicionario-de-dados.md#resource_event), [`APP_META`](../../dicionario-de-dados.md#app_meta).

## `MaintenanceRepository`

Ordens nunca são apagadas: conclusão e cancelamento preservam o histórico.

| Método | Contrato |
|---|---|
| `MaintenanceRepository::__construct` | `(Database $db)`. |
| `MaintenanceRepository::find` | `(int $id): ?array` — ordem com código, tipo, situação, rua e bairro do equipamento e nomes de quem abriu/encerrou. |
| `MaintenanceRepository::lock` | `(int $id): ?array` — `WITH LOCK` (sempre depois do equipamento). |
| `MaintenanceRepository::insert` | `(array $o): int` — `equipment_id`, `kind`, `priority`, `state` (padrão `ABERTA`), `due_date`, `reason`, `source`, `opened_at`, `opened_by`. |
| `MaintenanceRepository::markStarted` | `(int $id, string $at): void` — `EM_EXECUCAO`, `STARTED_AT`, versão + 1. O índice `UX_MAINT_ONE_RUNNING` barra a segunda em execução do mesmo equipamento. |
| `MaintenanceRepository::markClosed` | `(int $id, string $state, string $at, ?int $by, string $resolution, ?string $resultStatus): void` — `CONCLUIDA` ou `CANCELADA`, com resolução/motivo e resultado. |
| `MaintenanceRepository::open` | `(?array $equipmentIds = null): list<array>` — ordens `ABERTA`/`EM_EXECUCAO` de equipamentos **não arquivados**, por data (sem data primeiro: imediatas) e ID; lista vazia de IDs → `[]`. |
| `MaintenanceRepository::forEquipment` | `(int $equipmentId, int $closedLimit = 30): list<array>` — **todas** as pendentes (decidem próxima ação, início, cancelamento, arquivamento) e só as 30 encerradas mais recentes. Um limite único escondia uma pendência antiga (achado R03, ADR-0023). |
| `MaintenanceRepository::closedCountFor` | `(int $equipmentId): int` — total de encerradas (a ficha diz "mostrando N de M"). |
| `MaintenanceRepository::recentlyClosed` | `(int $limit = 20): list<array>` — "Encerradas recentemente" da Manutenção. |
| `MaintenanceRepository::equipmentOf` | `(int $orderId): ?int` — equipamento da ordem (para travar o equipamento antes). |
| `MaintenanceRepository::openFor` | `(int $equipmentId): list<array>` — pendentes de um equipamento, **inclusive arquivado** (regras de início e arquivamento). |
| `MaintenanceRepository::hasOpenOfKind` | `(int $equipmentId, string $kind): bool` — evita duas preventivas abertas ao agendar a próxima. |
| `MaintenanceRepository::all` | `(): list<array>` — todas, com instantes UTC (análise preventiva). |
| `MaintenanceRepository::trim` | `private static (?array $row): ?array`. |

## `AlertRepository`

`OPEN_KEY` + índice único `UQ_ALERT_OPEN_KEY` = no máximo um alerta não resolvido por causa.

| Método | Contrato |
|---|---|
| `AlertRepository::__construct` | `(Database $db)`. |
| `AlertRepository::find` | `(int $id): ?array` — com quem reconheceu, responsável (nome e se a conta está ativa), código/tipo e bairro do recurso. |
| `AlertRepository::lock` | `(int $id): ?array` — `WITH LOCK` (acompanhamento). |
| `AlertRepository::unresolved` | `(): list<array>` — `ABERTO` e `RECONHECIDO`, por gravidade (crítica, alta, média) e antiguidade. |
| `AlertRepository::forResource` | `(string $kind, int $id, int $limit = 20): list<array>` — pendentes do recurso (todos) seguidos dos encerrados mais recentes. |
| `AlertRepository::unresolvedFor` | `(string $kind, int $id): list<array>` — pendentes do recurso, mais antigo primeiro. Marcado `@phpstan-impure`: lido antes e depois de uma ação para saber se ela encerrou o alerta. |
| `AlertRepository::resolvedFor` | `(string $kind, int $id, int $limit = 20): list<array>` — encerrados mais recentes. |
| `AlertRepository::countResolvedFor` | `(string $kind, int $id): int`. |
| `AlertRepository::openKeys` | `(): array<string, int>` — `OPEN_KEY => ID` dos não resolvidos (base da reconciliação). |
| `AlertRepository::open` | `(string $kind, int $resourceId, string $reason, string $severity, string $message, string $at): bool` — insere `ABERTO` com `OPEN_KEY`; `false` se outro processo abriu o mesmo alerta no mesmo instante. |
| `AlertRepository::resolve` | `(int $id, string $at, string $reason): void` — `RESOLVIDO`, `OPEN_KEY = NULL`, instante e motivo. |
| `AlertRepository::acknowledge` | `(int $id, int $userId, string $at, ?string $note): bool` — só se ainda `ABERTO`. |
| `AlertRepository::advance` | `(int $id, ?int $assigneeId, ?string $nextCheckAt): void` — novo responsável/próxima conferência e `FOLLOWUP_NO + 1` (versão do acompanhamento). |
| `AlertRepository::addFollowUp` | `(array $f): int` — linha em `ALERT_FOLLOWUP` (`ATRIBUICAO` ou `PROVIDENCIA`). |
| `AlertRepository::followUps` | `(array $alertIds): array<int, list<array>>` — registros por alerta, do mais antigo ao mais novo, com nomes de quem registrou e das contas de/para. |
| `AlertRepository::trim` | `private static (?array $row): ?array`. |

## `EventRepository`

Histórico de recursos, **append-only** (a aplicação só tem `SELECT, INSERT`). `EVENT_UID` único garante que um evento
repetido não é gravado duas vezes, inclusive quando chega por duas conexões ao mesmo tempo.

| Método | Contrato |
|---|---|
| `EventRepository::__construct` | `(Database $db)`. |
| `EventRepository::append` | `(array $e): ?int` — grava (resumo cortado em 240; payload em JSON ou `NULL`); `null` se o `EVENT_UID` já existia (`UQ_RESOURCE_EVENT_UID`). |
| `EventRepository::exists` | `(string $uid): bool`. |
| `EventRepository::forResource` | `(string $kind, int $id, int $limit = 40): list<array>` — histórico da ficha, mais recente primeiro (ocorrência, ID), com o nome do autor e `payload` decodificado. |
| `EventRepository::recent` | `(int $limit = 15): list<array>` — eventos recentes sem posições. |
| `EventRepository::watermark` | `(): array{last_id: int, last_received_at: ?string}` — marca d'água do instantâneo da central (`sync`). |
| `EventRepository::equipmentStatusHistory` | `(string $until): list<array>` — eventos `SITUACAO` de equipamentos **recebidos** antes de `$until`, em ordem de ocorrência (base da análise). |
| `EventRepository::equipmentIntervalChanges` | `(string $until): list<array>` — `ALTERACAO` de equipamentos cujo payload contém `maint_interval_days`, em ordem de recebimento (reconstrução da periodicidade em cada corte). |
| `EventRepository::decode` | `private static (array $row): array` — apara e decodifica `payload`. |

## `MetaRepository`

Metadados da instalação em `APP_META` (`NAME`, `TEXT_VALUE`, `NUM_VALUE`, `UPDATED_AT`).

| Símbolo | Contrato |
|---|---|
| `MetaRepository::DATASET_MARKER` | `demo-sintetico` — o marcador `dataset` com este valor é a **condição para comandos destrutivos** de demonstração (`demo:seed --reset`, `demo:seed-multas`, `avaliar-sementes.php`). |
| `MetaRepository::__construct` | `(Database $db)`. |
| `MetaRepository::text` | `(string $name): ?string`. |
| `MetaRepository::number` | `(string $name): int` (ausente = 0). |
| `MetaRepository::set` | `(string $name, ?string $text, ?int $number, string $at): void` — `UPDATE OR INSERT … MATCHING (NAME)`. |
| `MetaRepository::increment` | `(string $name, int $by, string $at): void` — incremento atômico (o `UPDATE` bloqueia a linha até o commit); cria a chave se não existir. |
| `MetaRepository::all` | `(): array<string, array{text, number, updated_at}>`. |

Chaves usadas: `dataset`, `profile`, `seeded_at`, `sim_tick`, `last_sim_tick_at`, `duplicates_ignored`, `late_events`,
`fines_seed`, `infosiga_ultima_checagem`.

## Relacionados

[Manutenção](../application/manutencao.md) · [Alertas](../application/alertas.md) · [Ingestão](../application/ingestao.md) ·
[Central](../application/central.md)
