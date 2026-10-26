# Implantação, configuração e CI

Referência dos arquivos de infraestrutura: contêineres, servidor web, PHP, ferramentas de qualidade e pipelines. O
passo a passo de instalar, atualizar, fazer backup e publicar está em [operação](../operacao.md); as decisões, nas ADRs
0001 (PHP e Firebird em contêiner) e 0006 (origem dedicada) — [índice](../adr/README.md).

## Versões declaradas

Conferidas nos manifestos e lockfiles em 26/10/2026 (não são medições de um ambiente em execução).

| Componente | Onde está declarado | Versão |
|---|---|---|
| PHP (imagem web e simulador) | `docker/php/Dockerfile` | `php:8.5.11-apache-trixie` |
| PHP (CI) | `.github/workflows/ci.yml`, `.gitlab-ci.yml` | `php:8.5.11-cli-trixie` |
| PHP mínimo | `composer.json` (`require.php`) | `>=8.4`, com `ext-pdo`, `ext-pdo_firebird`, `ext-zip`, `ext-mbstring` |
| Firebird | `compose.yaml`, CI | `firebirdsql/firebird:5.0.4-trixie` |
| Composer (na imagem) | `Dockerfile` | `composer:2.10` |
| Slim · Slim PSR-7 · Twig | `composer.lock` | 4.15.3 · 1.8.0 · 3.30.0 |
| PHPUnit · PHPStan · PHP-CS-Fixer | `composer.lock` (dev) | 12.5.40 · 2.3.1 · 3.95.27 |
| Node | `package.json` (`engines`) · CI | `>=20.19` · 22 |
| Vite · Tailwind CSS · @tailwindcss/vite | `package-lock.json` | 8.3.2 · 4.3.3 · 4.3.3 |
| Alpine (build CSP) · GSAP · three.js | `package-lock.json` | 3.17.4 · 3.15.0 · 0.186.1 |
| Overpass (fonte) | `package-lock.json` | `@fontsource-variable/overpass` 5.3.0 |
| Leaflet | arquivo distribuído em `public/assets/vendor/leaflet/` | 1.9.4 (cabeçalho do `leaflet.js`) |
| Playwright (verificação da interface e vídeo) | `scripts/verificacao-interface/README.md`, `video/package.json` | 1.56.1 (instalado à parte, sem `--save`) |
| Python (treino, fora da aplicação) | `ml/marilia-setores/00-COMECE-AQUI.md`, `requirements.txt` | ≥ 3.12 (3.14.6 citado pelo pacote); numpy 2.5.4, scikit-learn 1.9.1 |

## `compose.yaml`

Projeto `promotheus-fiscaliza`, rede interna `backend`, volume `fbdata`.

| Serviço | Imagem / comando | Papel | Notas |
|---|---|---|---|
| `firebird` | `firebirdsql/firebird:5.0.4-trixie` | banco `fiscaliza.fdb` UTF8 | **sem porta publicada**; dono = `DB_MIGRATION_USER`; roda `docker/firebird/10-fiscaliza-init.sh` na criação; *healthcheck* por `isql` |
| `app` | build de `docker/php/Dockerfile` | Apache + PHP: landing em `/`, aplicação em `/promotheusfiscaliza` | porta só em `127.0.0.1:${APP_PORT:-8080}`; projeto montado em `/app`; não recebe o `.env` como variáveis (lê o arquivo, e a web descarta as credenciais privilegiadas) |
| `simulator` | `php bin/fiscaliza sim:run` como `www-data` | eventos fictícios contínuos | perfil `simulador`; `restart: unless-stopped` |
| `atualizador` | laço `urban:update` a cada 6 h (`memory_limit=512M`) | download mensal do Infosiga-SP e previsão semanal | perfil `atualizador`; idempotente |

`compose.origem-dedicada.yaml` (sobreposição): `APP_BASE_PATH` vazio e troca o site do Apache por
`docker/php/apache-origem-dedicada.conf` — aplicação na raiz do host, `/promotheusfiscaliza/...` redireciona (301) para
a raiz. Uso: `docker compose -f compose.yaml -f compose.origem-dedicada.yaml up -d`.

## Imagem PHP (`docker/php/`)

| Arquivo | Conteúdo |
|---|---|
| `Dockerfile` | instala `firebird-dev`, `libfbclient2`, `libzip-dev`; extensões `pdo_firebird` e `zip`; módulos `rewrite`, `headers`, `deflate`; cria `/var/lib/fiscaliza/sessions` (0700) e `/var/log/fiscaliza` para `www-data`; copia `php.ini`, o site do Apache e o `portal-stub`. |
| `php.ini` (`zz-fiscaliza.ini`) | erros só no log (`/var/log/fiscaliza/php-error.log`), `expose_php=Off`, fuso **UTC**, `memory_limit=256M`, `max_execution_time=30`, `post_max_size=1M`, **uploads desligados**, `allow_url_fopen=Off`; sessão estrita, só cookie, `HttpOnly`, guardada em `/var/lib/fiscaliza/sessions`, 8 h. |
| `apache-site.conf` | `DocumentRoot /app/landing/public` (landing); `Alias /promotheusfiscaliza /app/public` com *front controller* (`RewriteRule ^ index.php`); `/app` inteiro negado exceto `public/` e `landing/public/`; estáticos com `nosniff`, `CORP same-origin`, cache de 7 dias e CSP própria; `DEFLATE` para HTML/CSS/JS/JSON/SVG; `ServerTokens Prod`. |
| `apache-origem-dedicada.conf` | `DocumentRoot /app/public` e o redirecionamento 301 do prefixo antigo. |
| `portal-stub/index.html` | antiga página da raiz ("Portal simulado", 641 bytes), mantida na imagem em `/var/www/portal-stub` para reverter a landing ([`landing/README.md`](../../landing/README.md#voltar-à-página-anterior)). |

`public/.htaccess` é o equivalente para hospedagens Apache em que `public/` é servido sob `/promotheusfiscaliza`
(exige `AllowOverride`); no Docker não é usado (`AllowOverride None`).

## Firebird (`docker/firebird/10-fiscaliza-init.sh`)

Executado pela imagem oficial **só na criação** do banco principal: cria o usuário da aplicação com a senha de
`FISCALIZA_APP_DB_PASSWORD` (aspas simples escapadas), **revoga o papel `ADMIN`** que a imagem dá ao dono e cria o banco
de testes `fiscaliza_test.fdb` com o mesmo dono. Os privilégios do usuário da aplicação vêm depois, das migrações
([dicionário](dicionario-de-dados.md#privilégios-do-usuário-da-aplicação)).

## Configuração

Variáveis lidas pelo código, com finalidade: [operação — configuração](../operacao.md#configuração-env-ou-variáveis-de-ambiente).
Modelo com valores fictícios: [`.env.example`](../../.env.example). Carregamento, precedência (variáveis do processo
vencem o `.env`) e descarte das credenciais privilegiadas (`Config::PRIVILEGED` = `DB_MIGRATION_USER`,
`DB_MIGRATION_PASSWORD`, `FIREBIRD_ROOT_PASSWORD`): [`Config`](codigo/raiz/configuracao-e-servicos.md). O `.env` real
nunca é versionado (`.gitignore`) e não é lido por esta documentação.

## Ferramentas de qualidade

| Arquivo | Configuração |
|---|---|
| `phpunit.xml` | suítes `unidade`, `integracao-firebird`, `http`; falha em aviso, risco e depreciação; `APP_ENV=test`, `APP_BASE_PATH=/promotheusfiscaliza` — [suporte dos testes](codigo/testes/suporte.md) |
| `phpstan.neon` | nível 6 em `src`, `bin/fiscaliza` e `public/index.php`; cache em `var/cache/phpstan` |
| `.php-cs-fixer.dist.php` | `@PER-CS` + `declare_strict_types` + `no_unused_imports` em `src`, `tests`, `public`, `scripts` e `bin/fiscaliza` |
| `composer.json` (scripts) | `test`, `stan`, `style` (só confere), `style:fix`, `check` (= estilo + análise + testes) |
| `vite.config.js` | build do sistema: entradas `resources/css/app.css` e `resources/js/app.js` → `public/assets/build` com `manifest.json`, `base: './'`, `es2022`, avisos de licença e `licencas-de-terceiros.md` — [frontend](codigo/frontend/entrada-e-bibliotecas.md) |
| `landing/vite.config.js` | build da landing — [landing](codigo/landing/landing.md) |
| `package.json` (scripts) | `build`, `dev:watch`, `build:landing` |

## Integração contínua

As duas configurações fazem o mesmo, **só com credenciais descartáveis de teste** e sem segredo de publicação.

| Job | GitHub (`.github/workflows/ci.yml`, em todo push e PR) | GitLab (`.gitlab-ci.yml`) |
|---|---|---|
| Testes | contêiner `php:8.5.11-cli-trixie` + serviço Firebird 5.0.4; instala `pdo_firebird`/`zip` e Composer; `composer install`, `composer validate --strict`, `composer audit`, estilo (`--dry-run`), PHPStan, `php scripts/ci-preparar-banco.php`, PHPUnit | job `qualidade-e-testes`, mesmos passos; inclui o template de *Secret Detection* do GitLab |
| Assets | Node 22: `npm ci`, `npm run build` e falha se `public/assets/build` mudar; o mesmo para `npm run build:landing` e o build da landing | job `assets` em `node:22-bookworm`, mesmos passos |

Sem banco principal na CI: só `DB_TEST_DSN`, que os testes apagam e recriam. O resultado das execuções na
GitHub/GitLab não é registrado nesta documentação; as execuções locais datadas estão em [verificação](../verificacao.md).

## Pastas geradas ou locais (não editar à mão)

| Pasta | Gerada por | Versionada? |
|---|---|---|
| `public/assets/build/` | `npm run build` | sim (produção sem Node); a CI confere |
| `landing/public/landing-assets/promotheus/build/` | `npm run build:landing` | sim; a CI confere |
| `vendor/`, `node_modules/` | `composer install`, `npm ci` | não |
| `var/` (cache de templates, cache do PHPStan, trava do simulador) | aplicação e ferramentas | não |
| `.phpunit.cache/` | PHPUnit | não |
| `dist/` | pacotes de entrega (`git archive`) | não |

Inventário completo, inclusive o que fica de fora desta documentação e por quê:
[matriz de cobertura](../cobertura-documentacao.md#pastas-geradas-de-terceiros-e-excluídas).
