# Aplicação web: montagem, sessão, views e assets

<!-- fontes: src/Http/WebApp.php src/Http/Session.php src/Http/View.php src/Http/ViteAssets.php -->

Como uma requisição HTTP vira resposta. O Slim 4 é montado por [`WebApp::create`](#webappcreate): rotas, papéis por
grupo, ordem dos middlewares e o tratador de erros. A tabela completa de rotas e contratos é canônica em
[http.md](../../../http.md); controladores em [controladores](controladores.md); middlewares em [middlewares](middlewares.md).

```mermaid
sequenceDiagram
  participant N as Navegador
  participant SH as SecurityHeaders
  participant SC as SessionContext
  participant E as Tratador de erros
  participant B as BodyParsing
  participant R as Roteamento
  participant C as CsrfGuard
  participant RR as RequireRole (grupo)
  participant CT as Controlador
  N->>SH: requisição
  SH->>SC: retoma sessão (se há cookie), carrega a conta
  SC->>E: 
  E->>B: 
  B->>R: corpo de formulário/JSON lido
  R->>C: rota encontrada (404/405 se não)
  C->>RR: POST exige token e Origin
  RR->>CT: conta exigida; ADMIN em Contas
  CT-->>N: HTML, 303 com aviso, ou JSON
  Note over SH,N: cabeçalhos de segurança acrescentados na saída
```

## `WebApp`

### `WebApp::create`

`static create(Services $services, ?Session $session = null): App`

1. Lê `APP_BASE_PATH` (`Config::basePath`) e cria a sessão `PFSESSID` com `Path = prefixo/` e `Secure` conforme
   `APP_SECURE_COOKIES`.
2. Cria a [`View`](#view) (cache de templates em `var/cache/twig` só em produção).
3. `AppFactory::create()` com `setBasePath(prefixo)`; instancia os nove controladores e dois `RequireRole`.
4. Rotas abertas: `GET/POST /entrar`, `POST /sair`, `GET /saude`.
5. Grupo de **consulta** (qualquer conta ativa): `/`, `/api/central`, `/api/cep/{cep}`, `/api/localizar`, listas e
   fichas de equipamentos, agentes e multas, `/manutencao`, `/antecipacao`, `/planejar`, `/planejar/retrospecto`,
   `/sobre`.
6. Grupo de **escrita** (operador — inclui administrador): formulários e POSTs de equipamentos, agentes,
   acompanhamento de alertas de agente, multas, manutenção, ciência de alerta, recálculo da análise e missões.
7. Grupo **Contas** (administrador): `GET/POST /contas`, `POST /contas/{id}/situacao`, `POST /contas/{id}/senha`.
8. Middlewares da aplicação, de fora para dentro: `SecurityHeaders` → `SessionContext` → tratador de erros →
   `BodyParsing` → roteamento → `CsrfGuard` → (por grupo) `RequireRole` → rota. `{id}` aceita só dígitos.

Observação: os dois grupos de consulta e escrita usam o mesmo `RequireRole('OPERADOR')`; qualquer conta ativa
(operador ou administrador) consulta **e** escreve. Não existe papel somente leitura.

### `WebApp::renderError`

`static renderError(ServerRequestInterface $request, \Throwable $e, View $view, bool $production): ResponseInterface` —
mapeia exceções para status e mensagem segura (tabela em [erros](../erros.md#onde-cada-erro-é-tratado)), gera um código
de referência de 12 hexadecimais, grava no log privado os erros ≥ 500 (classe, mensagem, arquivo e linha, causa; sem
corpo nem cookies) e responde JSON `{"error", "reference"}` (rotas `/api/` ou `Accept: application/json`, com
`Cache-Control: no-store`) ou a página `error.twig`.

## `Session`

Sessão PHP no servidor com nome próprio (`PFSESSID`), cookie `HttpOnly`, `SameSite=Lax`, `Secure` em HTTPS e `Path`
restrito ao prefixo, sem `Domain`; modo estrito e só cookies. **Visitante levado ao login não recebe cookie**: a sessão
só começa quando um formulário (o de login, inclusive) precisa de token CSRF. O `Path` restrito reduz envio acidental,
mas não é fronteira entre aplicações da mesma origem ([segurança](../../../seguranca.md#publicação-e-limite-de-mesma-origem)).

| Símbolo | Contrato |
|---|---|
| `Session::IDLE_SECONDS` | 1800 (30 min sem uso) |
| `Session::ABSOLUTE_SECONDS` | 28800 (8 h desde o login) |
| `Session::__construct` | `(string $name, string $cookiePath, bool $secure)`. |
| `Session::resumeIfPresent` | `(array $cookies): void` — inicia só se o navegador já tem o cookie. |
| `Session::start` | `(): void` — configura o cookie e `session_start` (uma vez). |
| `Session::isStarted` | `(): bool`. |
| `Session::get` | `(string $key, mixed $default = null): mixed`. |
| `Session::set` | `(string $key, mixed $value): void` — inicia se preciso. |
| `Session::remove` | `(string $key): void`. |
| `Session::regenerate` | `(): void` — novo identificador (após login e ao expirar: impede fixação). |
| `Session::destroy` | `(): void` — apaga dados, expira o cookie e destrói a sessão (logout). |
| `Session::csrfToken` | `(): string` — token de 64 hex por sessão, criado sob demanda. |
| `Session::validCsrf` | `(mixed $token): bool` — sessão iniciada e `hash_equals` com o token guardado. |
| `Session::flash` | `(string $type, string $message): void` — aviso para a próxima página (`success`, `error`, `warning`). |
| `Session::takeFlash` | `(): list<array{type, message}>` — lê e remove os avisos. |

Chaves usadas: `user_id`, `auth_at`, `last_activity`, `credential_no`, `_csrf`, `_flash`. Nos testes HTTP, `ArraySession`
substitui a sessão do PHP ([suporte de testes](../testes/suporte.md)).

## `View`

Twig com **escape automático de HTML**; texto do banco é tratado como não confiável e templates nunca usam `|raw` com
dado externo (o único `|raw` injeta JSON já escapado com `JSON_HEX_*`). `strict_variables` fora de produção;
`auto_reload` sempre — com cache, um template alterado por `git pull` é recompilado.

| Símbolo | Contrato |
|---|---|
| `View::__construct` | `(Config $config, string $templates, string $publicDir, ?string $cache)` — registra as funções e filtros abaixo. |
| `View::render` | `(ServerRequestInterface $request, ResponseInterface $response, string $template, array $data = [], int $status = 200): ResponseInterface` — acrescenta `app` aos dados: `base` (prefixo), `path` (caminho sem prefixo), `user` (conta ou `null`), `flash` (avisos lidos da sessão), `csrf` (token, quando há sessão, conta ou `needs_csrf`), `map` (`MAP_TILE_URL` e atribuição), `poll_seconds` (≥ 3) e `sim_seconds` (≥ 2). |

Funções Twig: `url(path, query)` (prefixo + caminho + query sem vazios, RFC 3986); `asset(path)` (URL em
`/assets/…` com `?v=` derivado do `filemtime`); `asset_dir_version(dir)` (versão de uma pasta pelo arquivo mais recente,
para as miniaturas que o navegador monta); `vite_styles()` e `vite_script()` (tags do build). Filtros: `local_dt(formato)`
(instante UTC → hora de Marília; "—" se vazio), `br_date` (`AAAA-MM-DD` → `DD/MM/AAAA`), `pct` (fração → "88,4%" ou "não
se aplica"). O Twig também oferece `enum_cases()`, usado pela ajuda para listar situações e funções.

Teste: `ViewCacheTest` (cache em produção recompila template alterado).

## `ViteAssets`

Lê `public/assets/build/manifest.json` (gerado por `npm run build`) e escreve as tags do CSS e do módulo de entrada.
Nomes com hash de conteúdo; o prefixo vem de `APP_BASE_PATH`, então o mesmo build serve em `/promotheusfiscaliza` e na
origem dedicada. Produção só lê arquivos estáticos: não há servidor Node ([ADR-0012](../../../adr/0012-identidade-motion-e-build-de-assets.md)).

| Símbolo | Contrato |
|---|---|
| `ViteAssets::STYLES` / `ViteAssets::SCRIPT` | entradas `resources/css/app.css` e `resources/js/app.js` |
| `ViteAssets::__construct` | `(string $buildDir, string $publicUrl)`. |
| `ViteAssets::styles` | `(): string` — `<link rel="stylesheet">` do CSS e dos CSS importados pelo módulo. |
| `ViteAssets::script` | `(): string` — `<script type="module" src="…">`. |
| `ViteAssets::entry` | `private (string $name): array` — entrada do manifesto; ausente → `RuntimeException` "Rode npm run build". |
| `ViteAssets::load` | `private (): array` — manifesto ausente ou inválido → `RuntimeException`. |
| `ViteAssets::link` | `private (mixed $file): string`. |
| `ViteAssets::url` | `private (mixed $file): string` — só nomes `[A-Za-z0-9._/-]` sem `..`, escapados. |

Teste: `ViteAssetsTest`.

## Relacionados

[Middlewares](middlewares.md) · [Controladores](controladores.md) · [Erros](../erros.md) · [`src/Http/README.md`](../../../../src/Http/README.md)
