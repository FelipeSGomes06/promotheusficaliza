# Migrações do banco

Esquema do Firebird 5 em arquivos SQL numerados, aplicados **uma vez cada, em ordem**, por
`php bin/fiscaliza migrate` ([`Migrator`](../../docs/referencia/codigo/infrastructure/banco-e-migracoes.md#migrator)),
com a identidade do **dono do esquema** (`DB_MIGRATION_USER`). A aplicação web nunca roda migração. O que cada tabela e
coluna significa está no [dicionário de dados](../../docs/referencia/dicionario-de-dados.md).

## Arquivos

| Arquivo | O que faz | Risco para dados existentes |
|---|---|---|
| `0001_esquema_inicial.sql` | `APP_META`, `AGENCY`, `REGION`, `APP_USER`, `LOGIN_ATTEMPT`, `EQUIPMENT`, `AGENT`, `MAINTENANCE_ORDER`, `RESOURCE_EVENT`, `ALERT`, `ANALYSIS_RUN`, `ANALYSIS_RESULT` | nenhum (banco vazio) |
| `0002_invariantes_e_cota_de_login.sql` | índice parcial `UX_MAINT_ONE_RUNNING`; vagas numeradas de login (`SLOT_NO`, `SETTLES_ID`); classe `NAO_AVALIADO` | o índice **falha** se já houver duas ordens em execução no mesmo equipamento — a consulta para verificar está no comentário do arquivo |
| `0003_planejamento_por_setores.sql` | `URBAN_RUN`, `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_MISSION` (+ `UX_URBAN_MISSION_ACTIVE`), `URBAN_MISSION_LOG` | nenhum (só `CREATE`) |
| `0004_quadrados_de_2km.sql` | `URBAN_CELL` (grade 8 × 8) | nenhum |
| `0005_atualizacao_mensal_infosiga.sql` | `URBAN_SNAPSHOT`, `URBAN_DAILY` | nenhum |
| `0006_missao_por_quadrado.sql` | `URBAN_MISSION.CELL_NO` (anulável) | nenhum |
| `0007_multas.sql` | `FINE` (+ `UX_FINE_AIT`), `FINE_DOCUMENT_CHECK`, `FINE_EVENT` | nenhum |
| `0008_pendencia_regularizada_depois.sql` | só recalcula `FINE.ATTENTION_COUNT` | nenhum (valor derivado); atalho documentado no arquivo |
| `0009_acompanhamento_de_alertas.sql` | colunas de encerramento e acompanhamento em `ALERT`; tabela `ALERT_FOLLOWUP` | nenhum (alertas antigos ficam com motivo nulo) |
| `0010_lembrete_da_ultima_conferencia.sql` | renomeia `OCC_DOC_REVIEW_ON` → `DOC_REVIEW_ON`, acrescenta `DOC_REVIEW_SCOPE` e recalcula | nenhum (renomeia, não apaga) |
| `0011_redefinicao_de_senha.sql` | `APP_USER.CREDENTIAL_NO`, `PASSWORD_RESET_AT`, `PASSWORD_RESET_BY` | nenhum |

## Convenções dos arquivos

- Nome `NNNN_descricao.sql`; o migrador só considera arquivos que casam com `[0-9]{4}_*.sql`, em ordem de nome, e
  registra cada um em `SCHEMA_MIGRATION`.
- Cabeçalho obrigatório em comentário: propósito, dependências, risco de perda de dados e recuperação (restaurar backup
  `gbak`).
- **Cada instrução termina com `;` no fim da linha** — é por esse marcador que
  [`Migrator::statements`](../../docs/referencia/codigo/infrastructure/banco-e-migracoes.md#migrator) divide o arquivo.
  Comentários `--` são removidos.
- DDL roda em autocommit: se uma instrução falhar, as anteriores do mesmo arquivo já valem e o arquivo não é marcado
  como aplicado. Corrija a causa (por exemplo, a consulta da 0002) e rode `migrate` de novo; se não for possível,
  restaure o backup.
- Nunca altere um arquivo já aplicado em algum banco: crie a migração seguinte.

## Depois de migrar

`migrate` sincroniza os privilégios do usuário da aplicação: `SELECT, INSERT, UPDATE, DELETE` nas projeções e só
`SELECT, INSERT` nas tabelas de histórico (lista e motivo no [dicionário](../../docs/referencia/dicionario-de-dados.md#privilégios-do-usuário-da-aplicação)).
Rodar de novo não aplica nada e reaplica os GRANTs. Procedimentos de atualização de bancos existentes (backup, ordem de
comandos e o que reimportar depois da 0003/0004/0005/0007) estão em [operação](../../docs/operacao.md#atualizar-um-banco-existente-para-a-migração-0003).

Testes: `InputAndSpecsTest::testMigrationSplitterIgnoresCommentsAndSplitsAtLineEnd` (divisão de instruções),
`DriverContractTest::testApplicationIdentityCannotRewriteHistoryOrChangeSchema` (GRANTs) e os testes de integração, que recriam o esquema inteiro no banco de testes
dedicado a cada classe ([suporte de testes](../../docs/referencia/codigo/testes/suporte.md#firebirdtestcase)).
