# Repositório de contas e tentativas de login

<!-- fontes: src/Infrastructure/Repository/UserRepository.php -->

Contas de acesso ao sistema (`APP_USER`) — distintas dos agentes operacionais — e o registro de tentativas de login
(`LOGIN_ATTEMPT`, só leitura e inserção para a aplicação). Tabelas no
[dicionário](../../dicionario-de-dados.md#app_user-e-login_attempt).

## `UserRepository`

| Método | Contrato |
|---|---|
| `UserRepository::__construct` | `(Database $db)`. |
| `UserRepository::findByUsername` | `(string $username): ?array` — sem diferenciar maiúsculas (`UPPER(USERNAME) = UPPER(?)`), com o hash (só o `AuthService` o usa). |
| `UserRepository::find` | `(int $id): ?array` — linha completa (inclui `credential_no` e `active`). |
| `UserRepository::all` | `(): list<array>` — lista da tela Contas: usuário, nome, papel, ativa, último acesso, criação, redefinição de senha (instante e nome de quem redefiniu); sem hash. |
| `UserRepository::assignable` | `(): list<array>` — contas **ativas** que podem acompanhar um alerta (operador ou administrador), por nome; lidas a cada tela, então uma conta nova aparece sem mudar código. |
| `UserRepository::insert` | `(string $username, string $displayName, string $hash, string $role, string $createdAt): int` — conta ativa. `UX_APP_USER_USERNAME` (índice único sobre o usuário em maiúsculas) barra repetição. |
| `UserRepository::updateHash` | `(int $id, string $hash): void` — rehash transparente no login. |
| `UserRepository::resetPassword` | `(int $id, string $hash, int $by, string $at): void` — senha nova da **mesma conta**: `CREDENTIAL_NO + 1` (encerra as sessões abertas), `PASSWORD_RESET_AT` e `PASSWORD_RESET_BY`. |
| `UserRepository::touchLogin` | `(int $id, string $at): void` — `LAST_LOGIN_AT`. |
| `UserRepository::setActive` | `(int $id, bool $active): void`. |
| `UserRepository::lockActiveAdmins` | `(): int` — trava (até o fim da transação) e conta os administradores ativos; `WITH LOCK` não aceita agregação, por isso conta as linhas lidas. |
| `UserRepository::reserveAttempt` | `(string $keyHash, string $at): int` — reserva a **próxima vaga numerada** da chave (`SLOT_NO = MAX + 1` na mesma instrução). Duas conexões que calculem a mesma vaga colidem em `UX_LOGIN_ATTEMPT_SLOT`: a segunda espera a primeira terminar e recebe `ConstraintViolation` (deve recontar). A reserva conta como falha até ser quitada. |
| `UserRepository::settleAttempt` | `(int $reservationId, string $at): void` — quita uma reserva (login correto) **acrescentando** uma linha com `SETTLES_ID`: o histórico de tentativas nunca é alterado. |
| `UserRepository::failuresSince` | `(string $keyHash, string $since): int` — falhas e reservas não quitadas na janela (verificações em andamento contam: é isso que torna a cota atômica). |
| `UserRepository::trim` | `private static (?array $row): ?array`. |

`KEY_HASH` é o SHA-256 de `u:<usuário>` ou `ip:<IP>` — o banco não guarda usuário nem IP em claro nas tentativas.

## Relacionados

[Autenticação](../application/autenticacao.md) · [ADR-0011](../../../adr/0011-cota-de-login-atomica.md) ·
[ADR-0023 (redefinição de senha)](../../../adr/0023-revisao-de-logica-e-fluxos.md)
