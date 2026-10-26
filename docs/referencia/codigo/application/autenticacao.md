# Autenticação e contas de acesso

<!-- fontes: src/Application/AuthService.php -->

Contas de acesso (`APP_USER`, papéis `OPERADOR` e `ADMIN`) — distintas dos **agentes de campo**. Não há cadastro
público: contas nascem pelo administrador (tela **Contas**) ou pela CLI (`user:create`)
([ADR-0014](../../../adr/0014-acesso-somente-com-conta-cadastrada.md)). Senhas com `password_hash` (Argon2id quando o
PHP o oferece, senão o padrão), limite de tentativas atômico ([ADR-0011](../../../adr/0011-cota-de-login-atomica.md))
e mensagens que não revelam se a conta existe.

Rotas: [`AuthController`](../http/controladores.md#authcontroller). Sessão: [`SessionContext`](../http/middlewares.md#sessioncontext).
Guia: [Acesso e Contas](../../../guias/acesso-e-contas.md).

## `AuthService`

| Constante | Valor |
|---|---|
| `MAX_FAILURES_PER_USER` | 5 |
| `MAX_FAILURES_PER_IP` | 20 |
| `WINDOW_MINUTES` | 15 |
| `DUMMY_HASH` | hash Argon2id de uma senha descartável, verificado quando a conta não existe (tempo de resposta uniforme) |

### `AuthService::attempt`

`attempt(string $username, #[SensitiveParameter] string $password, string $ip): array` — devolve a conta (sem o hash).

1. Usuário aparado e cortado em 40; chaves da cota = SHA-256 de `u:<usuário em minúsculas>` e de `ip:<IP>` (o banco
   guarda só hashes).
2. [`reserve`](#authservicereserve): reserva uma vaga de tentativa por usuário e por IP **antes do hashing**; a vaga
   conta como falha até ser quitada.
3. Busca a conta sem diferenciar maiúsculas e verifica a senha (contra `DUMMY_HASH` se não existe).
4. Conta inexistente, senha errada ou conta inativa → `AuthenticationFailed("Usuário ou senha inválidos.")`.
5. Sucesso, numa transação: quita as duas reservas (linhas novas com `SETTLES_ID`; o histórico de tentativas nunca é
   alterado), refaz o hash se o algoritmo mudou (`password_needs_rehash`) e grava `LAST_LOGIN_AT`.

### `AuthService::reserve`

`private reserve(string $userKey, string $ipKey, string $at, string $since): array{0: int, 1: int}` — numa transação
curta: se as falhas não quitadas na janela já atingiram 5 (usuário) ou 20 (IP) → `AuthenticationFailed("Muitas
tentativas. Aguarde 15 minutos e tente novamente.")`; senão reserva a próxima vaga numerada de cada chave
(`UserRepository::reserveAttempt`). Duas tentativas simultâneas que calculem a mesma vaga colidem no índice único
`UX_LOGIN_ATTEMPT_SLOT`: a segunda recomeça a contagem (até 5 voltas; timeout de lock também recomeça). Persistindo a
disputa, recusa como bloqueada. Assim tentativas simultâneas **não ultrapassam a cota** (achado SEC-01 corrigido).

### `AuthService::createUser`

`createUser(string $username, string $displayName, string $role, #[SensitiveParameter] ?string $password = null): array{id: int, password: string}`

| Campo | Regra |
|---|---|
| `username` | 3–40: letras, números, `.`, `-`, `_` (`^[a-zA-Z0-9._-]{3,40}$`); único sem diferenciar maiúsculas (`UX_APP_USER_USERNAME` → "Nome de usuário já existe.") |
| `display_name` | obrigatório, até 80 |
| `role` | `OPERADOR` ou `ADMIN` |
| `password` | opcional; se informada, ≥ 12 caracteres; ausente → gerada (20 caracteres, 120 bits) |

A senha volta **só no retorno**: a tela e a CLI a mostram uma única vez; não vai para sessão, log nem histórico.

### `AuthService::resetPassword`

`resetPassword(int $id, int $actorId): string` — para quem perdeu a senha: **a mesma conta** (ID, usuário, papel e
histórico) recebe senha nova gerada, exibida uma vez. A própria conta → `RuleViolation` ("peça a outro
administrador"). O hash é calculado fora da transação (é lento de propósito); a transação confere a conta (`NotFound`)
e grava hash, `CREDENTIAL_NO + 1`, `PASSWORD_RESET_AT` e `PASSWORD_RESET_BY`. O `CREDENTIAL_NO` novo encerra as sessões
abertas dessa conta na próxima requisição. Não reativa conta desativada
([ADR-0023](../../../adr/0023-revisao-de-logica-e-fluxos.md)).

### `AuthService::setActive`

`setActive(int $id, bool $active, int $actorId): void` — desativar a própria conta → `RuleViolation`; desativar um
administrador ativo quando ele é o último → `RuleViolation` ("É preciso manter ao menos um administrador ativo"). Para
isso trava **todas** as contas de administrador ativas antes de contar (`lockActiveAdmins`): duas desativações cruzadas
disputam as mesmas linhas e a segunda recebe conflito. Conta desativada perde o acesso na requisição seguinte.

| Método | Contrato |
|---|---|
| `AuthService::__construct` | `(Database, UserRepository, Clock)`. |
| `AuthService::hash` | `static (#[SensitiveParameter] string $password): string` — Argon2id ou padrão. |
| `AuthService::activeUser` | `(int $id): ?array` — conta ativa sem o hash, ou `null`. Chamado a cada requisição por `SessionContext`. |
| `AuthService::generatePassword` | `private static (): string` — 15 bytes aleatórios em Base64 URL-safe sem `=` (20 caracteres). |
| `AuthService::users` | `(): list<array>` — todas as contas, com quem redefiniu a senha e quando. |

Testes: `AccountsTest`, `WebSecurityTest` (mensagem única, sessão, CSRF), `LoginQuotaTest` (processos concorrentes,
inclusive HTTP com sessões e CSRF distintos), `ReviewFlowsHttpTest` (redefinição encerra a sessão).

## Relacionados

[Repositório de contas](../infrastructure/repositorio-contas.md) · [Segurança](../../../seguranca.md) ·
[HTTP: contas e login](../../../http.md#alertas-análise-contas-e-login)
