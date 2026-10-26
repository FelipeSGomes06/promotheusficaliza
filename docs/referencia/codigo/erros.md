# Erros e como viram respostas

<!-- fontes: src/Domain/ValidationFailed.php src/Domain/RuleViolation.php src/Application/NotFound.php src/Application/AuthenticationFailed.php src/Application/DuplicateEvent.php src/Application/ProviderUnavailable.php src/Infrastructure/ConcurrencyConflict.php src/Infrastructure/ConstraintViolation.php src/Infrastructure/DatabaseUnavailable.php src/Http/HttpError.php -->

Página canônica das exceções do projeto: quem lança, o que significam e em que se transformam na web, na CLI e no
simulador. Todas estendem `\RuntimeException`. Nenhuma mensagem pública contém SQL, DSN, caminho ou pilha.

| Classe | Camada | Significado | HTTP (padrão) |
|---|---|---|---|
| `ValidationFailed` | Domain | Entrada inválida: erros **por campo** (`$errors`, `campo => mensagem`); mensagem geral "Revise os campos destacados." | 422 |
| `RuleViolation` | Domain | Operação bem formada, mas proibida pela regra (transição não permitida, ordem já em execução, alerta já resolvido…) | 409 |
| `NotFound` | Application | Recurso inexistente | 404 |
| `AuthenticationFailed` | Application | Falha de login com mensagem pública genérica ("Usuário ou senha inválidos." ou "Muitas tentativas…") | 401 (o controlador renderiza o login) |
| `DuplicateEvent` | Application | Corrida entre duas entregas do mesmo `EVENT_UID`: a transação perdedora é desfeita e o evento conta como duplicata | — (tratada pelo simulador) |
| `ProviderUnavailable` | Application | ViaCEP ou Nominatim não respondeu a tempo, falhou ou respondeu fora do formato; mensagem só para o log | 503 nas rotas `/api/cep`, `/api/localizar` |
| `ConcurrencyConflict` | Infrastructure | Timeout de lock, conflito de atualização concorrente ou **versão otimista desatualizada** | 409 |
| `ConstraintViolation` | Infrastructure | Violação de UNIQUE/FK/CHECK; `$constraint` traz o nome da restrição ou índice (`UX_EQUIPMENT_SERIAL`…) | traduzida pelo serviço; sem tradução → 500 |
| `DatabaseUnavailable` | Infrastructure | Banco inacessível (conexão recusada, credencial inválida); a causa do driver fica em `previous` | 503 |
| `HttpError` | Http | Erro HTTP com mensagem segura escolhida pelo código (`$status`) | o próprio `status` |

## Membros

| Símbolo | Contrato |
|---|---|
| `ValidationFailed::__construct` | `(array $errors, string $message = 'Revise os campos destacados.')` — `$errors` público e somente leitura. |
| `ConstraintViolation::__construct` | `(string $constraint, ?\Throwable $previous = null)` — mensagem "Restrição violada: NOME". Criada por [`Database::translate`](infrastructure/banco-e-migracoes.md#databasetranslate). |
| `HttpError::__construct` | `(int $status, string $publicMessage)` — `$status` público. |

`RuleViolation`, `NotFound`, `AuthenticationFailed`, `DuplicateEvent`, `ProviderUnavailable`, `ConcurrencyConflict` e
`DatabaseUnavailable` não têm membros próprios (usam o construtor de `RuntimeException`).

## Onde cada erro é tratado

1. **Serviços de aplicação** traduzem `ConstraintViolation` conhecidas em erros compreensíveis:
   `UX_EQUIPMENT_SERIAL` → erro no campo série; `UQ_EQUIPMENT_ASSET_TAG` → patrimônio; `UQ_EQUIPMENT_CODE` e
   `UQ_AGENT_CODE` → `ConcurrencyConflict` ("envie novamente"); `UX_MAINT_ONE_RUNNING` → `RuleViolation` (já há
   intervenção); `UX_FINE_AIT` → erro no campo AIT; `UX_APP_USER_USERNAME` → usuário já existe;
   `UX_URBAN_MISSION_ACTIVE` → `RuleViolation` (outra pessoa reservou o agente); `UQ_URBAN_RUN_BUNDLE` e `PK_URBAN_CELL`
   → importação idempotente; `UX_LOGIN_ATTEMPT_SLOT` → reconta a cota; `UQ_RESOURCE_EVENT_UID` e `UQ_ALERT_OPEN_KEY` →
   duplicata ignorada.
2. **Controladores** capturam o que a tela sabe explicar: 422 reapresenta o formulário com tudo o que foi digitado e o
   resumo de erros; 409 de versão mostra a tela de conflito ("Salvo agora × Sua versão"); ações de ficha redirecionam
   (303) com aviso. Ver cada controlador em [controladores](http/controladores.md).
3. **[`WebApp::renderError`](http/aplicacao-web.md#webapprendererror)** converte o resto: `HttpError` → seu status;
   `HttpNotFoundException`/`NotFound` → 404; `HttpMethodNotAllowedException` → 405; `ValidationFailed` → 422;
   `RuleViolation`/`ConcurrencyConflict` → 409; `DatabaseUnavailable` → 503; qualquer outra → 500 "Erro inesperado".
   Gera um **código de referência** (12 hex) mostrado ao usuário; para status ≥ 500 grava no log privado
   `[fiscaliza <referência>] MÉTODO caminho: Classe: mensagem @ arquivo:linha | causa: …` (sem corpo nem cookies).
   Rotas `/api/` ou `Accept: application/json` recebem `{"error", "reference"}`.
4. **CLI** ([`Console::run`](cli/console.md#consolerun)): `ValidationFailed` imprime os erros por campo; outras
   `RuntimeException`/`InvalidArgumentException` imprimem "Erro: …" (e a causa do driver fora de produção). Código de
   saída 1.
5. **Simulador**: `DuplicateEvent` conta como duplicata; falha de banco é registrada e o ciclo seguinte tenta de novo.

Testes: `WebSecurityTest` (códigos 401/403/404/409/422, referência), `DriverContractTest` (tradução das mensagens do
Firebird), testes de cada serviço.

## Relacionados

[HTTP: códigos](../../http.md) · [Banco](infrastructure/banco-e-migracoes.md) · [Segurança](../../seguranca.md)
