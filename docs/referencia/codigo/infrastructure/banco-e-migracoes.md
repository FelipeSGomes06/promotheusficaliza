# Banco Firebird: conexão, transações e migrações

<!-- fontes: src/Infrastructure/Database.php src/Infrastructure/Migrator.php -->

Acesso ao Firebird 5 por **PDO_FIREBIRD, sem ORM** ([ADR-0002](../../../adr/0002-pdo-direto-sem-orm.md)). As regras
desta camada foram comprovadas na [prova PHP/Firebird](../../../evidencias/2026-10-18-prova-firebird.md): timeout de
instrução por conexão (o driver espera locks indefinidamente), parâmetros **posicionais** (marcador nomeado repetido
falha) e tradução das mensagens do Firebird para exceções da aplicação. Esquema resultante:
[dicionário de dados](../../dicionario-de-dados.md).

## `Database`

| Método | Contrato |
|---|---|
| `Database::__construct` | `(string $dsn, string $user, #[SensitiveParameter] string $password, int $statementTimeoutSeconds = 5)` — não conecta ainda. |
| `Database::pdo` | `(): PDO` — conecta na primeira chamada com `ERRMODE_EXCEPTION`, `FETCH_ASSOC` e `CASE_LOWER` (chaves das linhas em minúsculas: `version_no`, `type_code`…) e executa `SET STATEMENT TIMEOUT n SECOND` (mínimo 1). Falha de conexão → `DatabaseUnavailable` **sem** a mensagem do driver (que pode conter DSN ou caminho). |
| `Database::disconnect` | `(): void` — descarta a conexão (o simulador reconecta no ciclo seguinte depois de uma falha). |
| `Database::transaction` | ver abaixo. |
| `Database::all` | `(string $sql, array $params = []): list<array>` — todas as linhas. |
| `Database::one` | `(string $sql, array $params = []): ?array` — primeira linha ou `null`. |
| `Database::value` | `(string $sql, array $params = []): mixed` — primeira coluna da primeira linha ou `null`. |
| `Database::insert` | `(string $sql, array $params): int` — acrescenta ` RETURNING ID` e devolve o ID gerado pelo Firebird (colunas de identidade). |
| `Database::execute` | `(string $sql, array $params = []): int` — linhas afetadas (usado no bloqueio otimista: 0 = versão mudou). |
| `Database::placeholders` | `static (int $count): string` — `"?, ?, ?"` para listas `IN` (mínimo 1); os valores sempre vão como parâmetros. |
| `Database::translate` | ver abaixo. |
| `Database::run` | `private (string $sql, array $params): PDOStatement` — prepara, liga cada parâmetro pela posição com o tipo (`NULL`, `BOOL`, `INT`, `STR`) e executa; `PDOException` → `translate`. |

### `Database::transaction`

`transaction(callable $work, bool $snapshot = false): mixed` — executa `$work($this)` numa transação curta: commit no
fim, rollback em qualquer exceção (`PDOException` traduzida; as demais sobem como estão). Com `$snapshot = true`, usa
isolamento **`REPEATABLE_READ` (SNAPSHOT) somente leitura** — todas as consultas de uma tela veem o mesmo instante do
banco (central, lista e ficha de multas) — e volta a `READ_COMMITTED` gravável no fim. Bloqueios de linha são feitos
pelos repositórios com `SELECT … WITH LOCK` dentro da transação.

### `Database::translate`

`static translate(PDOException $e): RuntimeException` — SQLSTATE `23000` ou mensagem com "violation of"/"duplicate
value" → [`ConstraintViolation`](../erros.md) com o nome da restrição/índice extraído de `constraint "X"`/`index "X"`;
`HY008`, "timeout", "lock conflict" ou "update conflicts" → `ConcurrencyConflict("Registro ocupado por outra
operação")`; o resto → `RuntimeException("Falha ao acessar o banco de dados")` com a causa em `previous`. Teste:
`DriverContractTest` (mensagens reais do Firebird, timeout de lock, parâmetros posicionais).

## `Migrator`

Aplica `database/migrations/NNNN_nome.sql` em ordem, uma única vez, com a **identidade de migração** (dono do banco). DDL
roda em autocommit: no Firebird um objeto criado só aceita DML depois do commit da sua criação. Ao final, sincroniza os
privilégios mínimos do usuário da aplicação (nunca SYSDBA, nunca DDL).

| Símbolo | Contrato |
|---|---|
| `Migrator::APPEND_ONLY` | tabelas em que a aplicação **só lê e acrescenta**: `RESOURCE_EVENT`, `LOGIN_ATTEMPT`, `URBAN_RUN`, `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_CELL`, `URBAN_SNAPSHOT`, `URBAN_DAILY`, `URBAN_MISSION_LOG`, `FINE_EVENT`, `FINE_DOCUMENT_CHECK`, `ALERT_FOLLOWUP`. |
| `Migrator::__construct` | `(Database $db, string $directory)`. |
| `Migrator::migrate` | `(): list<string>` — cria `SCHEMA_MIGRATION` se faltar; para cada arquivo pendente (ordem do nome), executa as instruções com `exec` e registra nome e instante (UTC). Devolve as aplicadas. Falha numa instrução interrompe (as anteriores do arquivo já foram confirmadas: DDL em autocommit). |
| `Migrator::grantApplicationAccess` | `(string $appUser): list<string>` — valida o nome (`^[A-Za-z][A-Za-z0-9_]{0,62}$`); para cada tabela de usuário (exceto `SCHEMA_MIGRATION`) concede `SELECT, INSERT, UPDATE, DELETE`, ou só `SELECT, INSERT` nas append-only — e **revoga** `UPDATE`/`DELETE` que uma versão anterior tenha concedido nelas. Idempotente. |
| `Migrator::dropEverything` | `(): void` — remove chaves estrangeiras e todas as tabelas. **Só** para os testes de integração no banco de testes dedicado ([`FirebirdTestCase`](../testes/suporte.md#firebirdtestcase)). |
| `Migrator::statements` | `static (string $sql): list<string>` — remove comentários de linha (`--`) e corta em `;` no fim da linha. Instruções com `;` no meio da linha seguem inteiras. |
| `Migrator::pending` | `private (array $applied): array<string, string>` — arquivos `[0-9]{4}_*.sql` ainda não aplicados, ordenados. |
| `Migrator::ensureLedger` | `private (): void` — cria `SCHEMA_MIGRATION (NAME, APPLIED_AT)`. |

`php bin/fiscaliza migrate` chama `migrate()` e depois `grantApplicationAccess(DB_USER)`; rodar de novo não aplica nada
e reaplica os GRANTs. Nunca roda por HTTP. Procedimentos de atualização por migração:
[operação](../../../operacao.md#atualizar-um-banco-existente-para-a-migração-0003) e
[`database/migrations/README.md`](../../../../database/migrations/README.md).

## Relacionados

[Erros](../erros.md) · [Segurança: banco](../../../seguranca.md#controles-implementados) ·
[ADR-0001 PHP e Firebird em contêiner](../../../adr/0001-php-firebird-em-conteiner.md)
