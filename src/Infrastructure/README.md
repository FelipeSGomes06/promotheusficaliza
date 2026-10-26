# Infraestrutura (`src/Infrastructure/`)

Acesso ao Firebird por PDO direto (parâmetros posicionais, `SET STATEMENT TIMEOUT`, transações curtas) e aos
provedores externos de endereço. Esquema: [dicionário de dados](../../docs/referencia/dicionario-de-dados.md).

| Arquivo ou pasta | Responsabilidade | Referência |
|---|---|---|
| `Database.php` | conexão, consultas, transações, tradução de erros do driver (unicidade, lock, timeout) | [banco e migrações](../../docs/referencia/codigo/infrastructure/banco-e-migracoes.md#database) |
| `Migrator.php` | aplica `database/migrations/` e sincroniza os GRANTs mínimos | [banco e migrações](../../docs/referencia/codigo/infrastructure/banco-e-migracoes.md#migrator) |
| `ViaCepClient.php`, `NominatimGeocoder.php` | rua pelo CEP e ponto pela rua (HTTPS, timeout curto, só CEP/rua saem) | [provedores externos](../../docs/referencia/codigo/infrastructure/provedores-externos.md) |
| `ConcurrencyConflict.php`, `ConstraintViolation.php`, `DatabaseUnavailable.php` | exceções | [erros](../../docs/referencia/codigo/erros.md) |
| [`Repository/`](Repository/README.md) | SQL de cada agregado | [repositórios](../../docs/referencia/codigo/README.md#infrastructure) |
