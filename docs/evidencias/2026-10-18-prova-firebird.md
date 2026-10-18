# Prova de viabilidade PHP ↔ Firebird — 18/10/2026

Ambiente: Docker Desktop 29.7.2 (Windows 11, WSL2). Contêineres do `compose.yaml` deste repositório.

| Componente | Versão observada | Como foi obtida |
|---|---|---|
| Servidor Firebird | LI-V5.0.4.1812 (imagem `firebirdsql/firebird:5.0.4-trixie`) | `PDO::ATTR_SERVER_VERSION` e log do contêiner |
| PHP (CLI e Apache, mesma imagem) | 8.5.11 NTS | `php -v` |
| Extensão | PDO_Firebird (compilada com `docker-php-ext-install`) | `php -m` |
| fbclient | 4.0.5.3140 (`libfbclient2` do Debian trixie), API 40 | `dpkg -l`, `Pdo\Firebird::getApiVersion()` |
| Composer | 2.10.3 | `composer --version` |

Script exploratório: [`scripts/sondagem-firebird.php`](../../scripts/sondagem-firebird.php). Os comportamentos abaixo
viraram testes de integração automatizados em `tests/Integration` (ver `docs/verificacao.md`).

## Resultados

| Verificação | Resultado |
|---|---|
| Conexão `charset=UTF8;dialect=3` com usuário não-SYSDBA | OK |
| Texto UTF-8 (`Marília — São João ção`) ida e volta | Idêntico |
| `BLOB SUB_TYPE TEXT` com 5.000 caracteres multibyte | Idêntico (`string`) |
| `BOOLEAN` | volta como `bool` PHP |
| `DECIMAL(9,6)` | volta como `string` (`'-22.213912'`) — conversão explícita na aplicação |
| `TIMESTAMP` / `DATE` | `string` `Y-m-d H:i:s` / `Y-m-d` |
| `BIGINT` / `INTEGER` / `NULL` | `int` / `int` / `null` |
| `INSERT … RETURNING ID` | funciona com `fetch()` |
| `OFFSET ? ROWS FETCH NEXT ? ROWS ONLY` com parâmetros | OK |
| `LIKE` com acento e `CONTAINING` (sem diferenciar maiúsculas) | OK |
| Reusar o mesmo parâmetro nomeado (`:x … :x`) | **Falha** (`-804`) → regra: nunca reutilizar marcador |
| Índice único por expressão (`UPPER(TRIM(fabricante)) \|\| '\|' \|\| UPPER(TRIM(série))`) | rejeita duplicata (`23000`); aceita várias séries `NULL` |
| `beginTransaction` + falha + `rollBack` | nada persiste; `inTransaction()` volta a `false` |
| `UPDATE` concorrente na mesma linha (2 conexões) | **a 2ª espera indefinidamente** (transação WAIT do driver) |
| `SET STATEMENT TIMEOUT 2 SECOND` na 2ª conexão | espera cancelada em 2,00 s com `HY008`; conexão segue utilizável |

## Decisões derivadas

1. Toda conexão da aplicação executa `SET STATEMENT TIMEOUT` (configurável) logo após conectar; `HY008` vira resposta
   "registro ocupado, tente novamente" (HTTP 409), sem expor SQL.
2. Edição concorrente de formulários usa versão otimista (`VERSION_NO`) além do lock do banco.
3. Decimais de coordenadas são lidos como string e convertidos com validação; instantes gravados em UTC como `TIMESTAMP`.
4. Nenhuma consulta reutiliza parâmetro nomeado; a camada de persistência usa marcadores posicionais.
5. Acesso a dados com PDO direto e SQL Firebird em repositórios pequenos (ADR-0002): nenhum ORM foi necessário.

## Achado durante a primeira carga (18/10/2026)

`CHECK (CEP SIMILAR TO '[0-9]{5}-[0-9]{3}')` foi aceito no DDL, mas falha ao avaliar (`-105 Invalid SIMILAR TO pattern`):
no `SIMILAR TO` do Firebird o hífen fora de colchetes é metacaractere. O padrão válido é `'[0-9]{5}[-][0-9]{3}'`
(testado: aceita `17500-012`, rejeita `1750-0012` e `17500012`). A migração 0001 ainda não havia sido publicada,
então foi corrigida no próprio arquivo e o esquema local foi recriado do zero. Depois de publicada, uma correção
assim seria uma nova migração.
