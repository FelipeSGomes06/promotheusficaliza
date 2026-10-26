# Camada web (`src/Http/`)

Aplicação Slim 4 com Twig 3: monta middlewares e rotas, guarda a sessão, renderiza telas e serve as tags dos assets do
Vite. Contratos de rota e campo: [HTTP](../../docs/http.md); segurança: [segurança](../../docs/seguranca.md).

| Arquivo ou pasta | Responsabilidade | Referência |
|---|---|---|
| `WebApp.php` | ordem dos middlewares, grupos de rotas (aberto, consulta, escrita, administrador), página de erro com código de referência | [aplicação web](../../docs/referencia/codigo/http/aplicacao-web.md#webapp) |
| `Session.php` | sessão PHP com cookie `PFSESSID` sob o prefixo, CSRF, avisos e expiração | [aplicação web](../../docs/referencia/codigo/http/aplicacao-web.md#session) |
| `View.php` | ambiente Twig (autoescape, cache em produção com recompilação, funções `url`, `asset`, `vite_styles`, `vite_script`, filtros `local_dt`, `br_date`, `pct`…; os templates também usam `enum_cases`, nativa do Twig 3) | [aplicação web](../../docs/referencia/codigo/http/aplicacao-web.md#view) |
| `ViteAssets.php` | lê `public/assets/build/manifest.json` e gera as tags com hash | [aplicação web](../../docs/referencia/codigo/http/aplicacao-web.md#viteassets) |
| `HttpError.php` | erro HTTP com status (401, 404, 409…) | [erros](../../docs/referencia/codigo/erros.md) |
| [`Controller/`](Controller/README.md) | um controlador por área; cada método público é uma rota | [controladores](../../docs/referencia/codigo/http/controladores.md) |
| [`Middleware/`](Middleware/README.md) | cabeçalhos de segurança, sessão/conta, CSRF e papel | [middlewares](../../docs/referencia/codigo/http/middlewares.md) |

Ordem de uma requisição: `SecurityHeaders` → `SessionContext` → tratamento de erro → leitura do corpo → roteamento →
`CsrfGuard` → `RequireRole` do grupo → controlador.
