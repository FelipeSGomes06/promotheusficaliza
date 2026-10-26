# Middlewares

<!-- fontes: src/Http/Middleware/SecurityHeaders.php src/Http/Middleware/SessionContext.php src/Http/Middleware/CsrfGuard.php src/Http/Middleware/RequireRole.php -->

Os quatro middlewares PSR-15 próprios, na ordem em que a requisição os atravessa
([`WebApp::create`](aplicacao-web.md#webappcreate)). Controles e testes de segurança: [segurança](../../../seguranca.md).

## `SecurityHeaders`

Cabeçalhos emitidos pela própria aplicação (valem atrás de qualquer servidor ou proxy), acrescentados na **saída**.

| Cabeçalho | Valor |
|---|---|
| `Content-Security-Policy` | `default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self' data: <origem dos tiles>; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'; manifest-src 'none'; worker-src 'none'` — sem `unsafe-inline` nem nonce: todo script e estilo é arquivo local |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()` |
| `Cross-Origin-Opener-Policy` / `Cross-Origin-Resource-Policy` | `same-origin` |
| `Cache-Control` | `no-store, private`, salvo se a rota já definiu |

HSTS não é emitido: vale para o host inteiro e é decisão do servidor do domínio.

| Método | Contrato |
|---|---|
| `SecurityHeaders::__construct` | `(string $tileUrl)` — `MAP_TILE_URL`. |
| `SecurityHeaders::process` | PSR-15: delega e acrescenta os cabeçalhos. |
| `SecurityHeaders::tileOrigin` | `static (string $url): ?string` — origem `esquema://host[:porta]` do provedor de tiles (troca `{s}`, `{z}`… por valores de exemplo); com `{s}` vira `*.dominio`; `null` se não for http(s) com host. É a única origem externa liberada, e só em `img-src`. |

## `SessionContext`

Retoma a sessão quando o navegador já tem o cookie, aplica a expiração e carrega a **conta ativa do banco a cada
requisição** (desativação, troca de papel e redefinição de senha valem imediatamente).

`SessionContext::process`:

1. `resumeIfPresent(cookies)`.
2. Com `user_id` na sessão: inativa há mais de 30 min ou logada há mais de 8 h → sem conta; senão
   `AuthService::activeUser`. Se o `credential_no` da conta for diferente do guardado no login → senha redefinida →
   sem conta.
3. Banco fora do ar ao conferir a conta → segue com `database_unavailable = true` (o `RequireRole` mostra a página de
   indisponibilidade em vez do login).
4. Sem conta depois de ter tido: remove `user_id`, regenera a sessão e deixa o aviso "Sua sessão expirou…" ou "A senha
   desta conta foi redefinida pelo administrador. Entre com a senha nova.". Com conta: atualiza `last_activity`.
5. Repassa os atributos `session` e `user` à requisição.

| Método | Contrato |
|---|---|
| `SessionContext::__construct` | `(Session $session, AuthService $auth)`. |
| `SessionContext::process` | ver acima. |

## `CsrfGuard`

Toda requisição que altera estado (tudo que não é `GET`, `HEAD`, `OPTIONS`) exige o **token de sincronização** da
sessão, no campo `_csrf` do corpo ou no cabeçalho `X-CSRF-Token` (usado pelo `fetch` da central). Se houver cabeçalho
`Origin`, ele precisa ser `APP_ORIGIN` (atrás de proxy que termina TLS) ou a própria origem da requisição. `SameSite=Lax`
é defesa complementar, não substituto. GET nunca altera estado.

| Método | Contrato |
|---|---|
| `CsrfGuard::__construct` | `(string $origin = '')` — `APP_ORIGIN`. |
| `CsrfGuard::process` | `Origin` estranho → `HttpError(403, "Origem da requisição não permitida.")`; token ausente ou inválido → `HttpError(403, "Formulário expirado ou inválido. Recarregue a página e tente de novo.")`. |
| `CsrfGuard::expectedOrigin` | `private static (ServerRequestInterface $request): string` — `esquema://host[:porta]` da requisição. |

## `RequireRole`

Autorização **no servidor** por grupo de rotas ([ADR-0014](../../../adr/0014-acesso-somente-com-conta-cadastrada.md)).

| Método | Contrato |
|---|---|
| `RequireRole::__construct` | `(string $role, string $basePath)` — `OPERADOR` ou `ADMIN`. |
| `RequireRole::process` | Sem conta: banco indisponível → `DatabaseUnavailable` (503); `GET` de página (não `/api/`) → **302** para `/entrar?voltar=<caminho e query>`; demais (API, POST) → `HttpError(401)`. Com conta: grupo `ADMIN` e conta não administradora → `HttpError(403, "Esta área é restrita a administradores.")`. |

O menu também esconde **Contas** de quem não é administrador, mas a regra que vale é a do servidor (testado com chamadas
diretas).

Testes: `WebSecurityTest` (visitante → login/401, CSRF, `Origin`, papel, cabeçalhos, escape), `LoginQuotaTest`,
`ReviewFlowsHttpTest` (sessão encerrada pela redefinição).

## Relacionados

[Aplicação web](aplicacao-web.md) · [Autenticação](../application/autenticacao.md) · [HTTP](../../../http.md)
