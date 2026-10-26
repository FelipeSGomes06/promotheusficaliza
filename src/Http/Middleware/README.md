# Middlewares (`src/Http/Middleware/`)

Quatro middlewares PSR-15, na ordem em que a requisição os atravessa. Contratos:
[middlewares](../../../docs/referencia/codigo/http/middlewares.md).

| Arquivo | O que garante |
|---|---|
| `SecurityHeaders.php` | CSP sem `unsafe-inline` (tiles do mapa na `img-src`), `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `COOP` e `CORP` same-origin, `Cache-Control: no-store, private` quando a resposta não define outro |
| `SessionContext.php` | retoma a sessão, aplica 30 min parada / 8 h, relê a conta ativa do banco a cada requisição e encerra a sessão se a senha foi redefinida (`credential_no`) |
| `CsrfGuard.php` | token de sincronização em toda escrita (`_csrf` ou `X-CSRF-Token`) e `Origin` igual a `APP_ORIGIN` ou à da requisição |
| `RequireRole.php` | exige conta (página → `302 /entrar?voltar=…`; API → 401) e o papel do grupo (Contas → administrador); banco fora → 503 |

Testes: `WebSecurityTest`, `LoginQuotaTest`, `ReviewFlowsHttpTest` ([catálogo](../../../docs/referencia/codigo/testes/casos.md)).
