# Repositório das multas

<!-- fontes: src/Infrastructure/Repository/FineRepository.php -->

SQL da aba Multas: a **projeção** (`FINE`), as **constatações documentais** (`FINE_DOCUMENT_CHECK`) e o **histórico
próprio** (`FINE_EVENT`) — estes dois só recebem `INSERT` da aplicação. Linhas com chaves em minúsculas;
`SOURCE_SNAPSHOT`, `RADAR_DETAILS` e `PAYLOAD` decodificados. Tabelas: [dicionário](../../dicionario-de-dados.md#fine).

## `FineRepository`

| Constante | Conteúdo |
|---|---|
| `SELECT` (privada) | `FINE` + órgão, bairro, código/arquivamento do radar e do agente, nomes de quem criou/alterou/cancelou e `LATER_DOC_STATUS` (resultado da última conferência "depois da multa" da identidade atual, mesma escolha de `FineDocumentStatus::select`) |
| `WRITABLE` (privada) | colunas que os casos de uso podem gravar (lista fechada) |
| `ORDER` (privada) | `ocorrencia` (`OCCURRED_AT DESC NULLS LAST`), `codigo` (ID decrescente), `atualizado` — o ID desempata |

| Método | Contrato |
|---|---|
| `FineRepository::__construct` | `(Database $db)`. |
| `FineRepository::find` | `(int $id): ?array` — ficha completa; `@phpstan-impure` (relida depois de um conflito). |
| `FineRepository::lock` | `(int $id): ?array` — `WITH LOCK`; ordem fixa de bloqueio: multa → equipamento → agente. |
| `FineRepository::insert` | `(array $values): int`. |
| `FineRepository::updateVersioned` | `(int $id, int $expectedVersion, array $values): bool` — **toda** mudança avança `VERSION_NO`; só grava se a versão for a lida. |
| `FineRepository::search` | ver abaixo. |
| `FineRepository::appendEvent` | `(array $event): void` — `FINE_EVENT` com `EVENT_UID` (chave do comando), `REQUEST_HASH` (impressão digital), tipo, origem, autor, instante, resumo (≤ 240), motivo e payload JSON. |
| `FineRepository::eventByUid` | `(string $uid): ?array` — `fine_id`, tipo, autor e impressão digital do comando já gravado (idempotência). |
| `FineRepository::events` | `(int $fineId, int $page, int $perPage): array{rows, total}` — histórico mais recente primeiro, paginado (nenhum evento fica inacessível). |
| `FineRepository::insertCheck` | `(array $check): int` — constatação documental. |
| `FineRepository::checks` | `(int $fineId): list<array>` — todas as constatações, mais recentes (pelo instante da conferência) primeiro, com o nome de quem registrou. |
| `FineRepository::sourceOptions` | `(): array{radars, agents}` — origens que o formulário oferece: radares do inventário (com limite cadastrado, funções e modalidade decodificados de `SPECS`) e agentes de **trânsito**; em atividade/ativos primeiro e arquivados por último (permitidos para registro histórico). |
| `FineRepository::where` | `private static (FineFilters $f): array` — estados do cadastro, origem, etapa, órgão, radar, agente (da constatação ou operador), documentação na ocorrência, pagamento, período UTC semiaberto e busca. |
| `FineRepository::onlyWritable` | `private static (array $values): array` — recusa coluna fora da lista; codifica `SOURCE_SNAPSHOT`/`RADAR_DETAILS` (vazio → `NULL`). |
| `FineRepository::hydrate` | `private static (?array $row): ?array` — decodifica JSON, corta instantes em 19 caracteres, formata coordenadas com 6 casas e acrescenta `code` (`DEMO-MUL-000023`). |

### `FineRepository::search`

`search(FineFilters $filters, string $today, int $page, int $perPage): array{rows, total, attention, paid}` — lista,
total e os resumos com o **mesmo filtro**, numa leitura (o chamador abre a transação SNAPSHOT). Sem `JOIN` com
constatações nem eventos: nenhuma multa é contada duas vezes.

- **A conferir** = não cancelada **e** (`ATTENTION_COUNT > 0` **ou** `DOC_REVIEW_ON <= hoje`). O primeiro termo é a
  contagem gravada dos motivos que não dependem do dia; o segundo soma o lembrete vencido no próprio dia, sem regravar
  a multa. O filtro `conferir=1` aplica a mesma condição à lista.
- **Pagas** = `PAYMENT_STATUS = 'PAGAMENTO_INFORMADO'` (informação manual; não é arrecadação).
- **Busca**: código interno `DEMO-MUL-…` vira `ID = ?` (não há coluna de código); senão procura em `AIT_KEY`, placa
  normalizada, RENAVAM e descrição com `CONTAINING CAST(? AS VARCHAR(60))` — sem o `CAST` o Firebird tiparia o
  parâmetro pela coluna (placa `VARCHAR(7)`) e recusaria uma busca mais longa. A busca não vai para logs.

Testes: `FinesTest` (contador, lista e ficha), `FinesHttpTest`.

## Relacionados

[Multas (serviço e consulta)](../application/multas.md) · [Filtros das multas](../application/validacao-e-filtros.md#finefilters)
