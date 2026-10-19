# Segurança e privacidade

Código aberto, dados sintéticos, escrita autenticada e infraestrutura própria. Segurança não depende de esconder a
implementação. Este documento descreve controles **implementados e testados**; não é laudo de auditoria.

## Controles implementados

| Área | Controle | Onde / teste |
|---|---|---|
| Acesso | **Nada sem conta**: telas, mapa e API exigem conta ativa cadastrada pelo **administrador** (ou pelo `user:create` no servidor); abertas só `/entrar`, `/sair` e `/saude` (sem dados). Escrita para **operador**; contas só para **administrador**. Conta desativada perde o acesso na requisição seguinte. Verificado no servidor em toda rota, inclusive chamadas diretas. Sem cadastro público ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)). | `WebApp`, `RequireRole` · `WebSecurityTest` |
| Contas | `password_hash` Argon2id, rehash automático, senha gerada aleatória exibida uma vez, mínimo 12 caracteres, não desativar a si mesmo nem o último admin. | `AuthService` |
| Login | Mensagem única para usuário inexistente/senha errada; hash fictício verificado para tempo uniforme; limite de 5 falhas/usuário e 20/IP em 15 min (guardados como SHA-256), **reservado antes do hashing**, de modo que tentativas simultâneas não ultrapassam a cota ([ADR-0011](adr/0011-cota-de-login-atomica.md)). | `WebSecurityTest`, `LoginQuotaTest` (processos concorrentes, inclusive HTTP com sessões e CSRF distintos) |
| Sessão | Nome próprio (`PFSESSID`), `HttpOnly`, `SameSite=Lax`, `Secure` em HTTPS (`APP_SECURE_COOKIES`), `Path` = prefixo, sem `Domain`; modo estrito; regenerada no login; destruída no logout; expira por inatividade (30 min) e tempo absoluto (8 h); arquivos fora do alcance HTTP. Visitante levado ao login **não recebe cookie** (a sessão começa no formulário de login, por causa do token CSRF). | `Session`, `SessionContext` |
| CSRF | Token de sincronização em todo `POST` + checagem de `Origin` (configurável por `APP_ORIGIN` atrás de proxy). GET nunca altera estado. | `CsrfGuard` · `WebSecurityTest` |
| Entrada | Validação de formato, faixa, tamanho, listas fechadas, transições e combinações no servidor; campos não esperados ignorados; caracteres de controle removidos; filtros e ordenação desconhecidos descartados. | `Input`, `Filters`, `EquipmentType` |
| SQL | Sempre parâmetros; nomes de coluna e ordenações de listas fechadas; restrições no banco; timeout de instrução contra espera indefinida. | Repositórios · `DriverContractTest` |
| Banco | Usuário da aplicação só com `SELECT/INSERT/UPDATE/DELETE` (histórico e tentativas de login: só `SELECT/INSERT`), sem DDL; migrações com o dono do esquema; SYSDBA nunca usado pela aplicação; dono sem papel ADMIN; Firebird sem porta publicada. | `Migrator`, `10-fiscaliza-init.sh` · `DriverContractTest` |
| Saída | Twig com escape automático; nenhum `|raw` com dado externo; JSON com `JSON_HEX_TAG/AMP`; no navegador, texto do servidor entra por `x-text`/`textContent` (Alpine) e nunca por `innerHTML`; marcadores do mapa só recebem códigos validados (`^[A-Z_]+$`); tooltips e popups montados com nós DOM. | `WebSecurityTest::testUntrustedTextIsEscapedInHtmlAndJson` |
| Interface sob CSP | Alpine no **build CSP** (analisador próprio, sem `eval`/`new Function`; acesso a `document`/globais bloqueado nas expressões); GSAP escreve estilos pelo CSSOM; o Flip é usado **sem** `nested: true` (essa opção usa `setAttribute("style")`, que a CSP bloquearia). Scripts e CSS gerados pelo Vite, servidos de `'self'`; a CSP não mudou. Zero violações em 34 carregamentos no Chromium com os cabeçalhos reais ([evidência](evidencias/2026-10-18-identidade-e-motion.md)). | `WebSecurityTest::testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode` |
| Cabeçalhos | CSP `default-src 'none'`, `script-src/style-src 'self'` (sem `unsafe-inline`, sem nonce), `img-src` só com a origem dos tiles configurada, `frame-ancestors 'none'`, `base-uri 'none'`, `form-action 'self'`; `nosniff`; `X-Frame-Options: DENY`; `Referrer-Policy`; `Permissions-Policy` (geolocalização desligada); COOP/CORP; `no-store`. Estáticos com `nosniff` no Apache. **HSTS não é emitido pela subpasta** (é decisão do domínio inteiro). | `SecurityHeaders`, `apache-site.conf` |
| Erros | Mensagem útil + código de referência; log privado com classe, mensagem e origem (sem corpo, cookies ou senhas); `display_errors=Off`; sem `X-Powered-By`; `ServerTokens Prod`. | `WebApp::renderError`, `docker/php/php.ini` |
| Operação | Sem endpoint público de migração, reset, criação de admin ou simulação global; `demo:seed --reset` **e** `scripts/avaliar-sementes.php` exigem `DEMO_ALLOW_RESET=true` e o marcador do conjunto sintético; os testes destrutivos recusam rodar se `DB_TEST_DSN` coincidir com `DB_DSN` (comparando o DSN e, se o principal estiver acessível, o arquivo físico informado pelo Firebird); `/saude` sem versões. | `Console`, `FirebirdTestCase`, `DatabaseSeparation` · `CentralAndAnalysisTest`, `DatabaseSeparationTest` |
| Privacidade | Só dados sintéticos; sem CPF, contatos, placas reais ou localização de visitantes; formulários avisam para não usar dados reais. | `DemoScenario`, formulários |
| Segredos | `.env` e bancos/backups fora do Git (`.gitignore`); exemplo com valores fictícios; credenciais locais em `var/` (ignorado). Web e simulador descartam as credenciais de dono e SYSDBA ao carregar a configuração, e o Compose não as passa mais como variáveis de ambiente desses processos ([credenciais por processo](operacao.md#credenciais-por-processo)). | `.gitignore`, `.env.example`, `Config::PRIVILEGED`, `compose.yaml` |

## Publicação e limite de mesma origem

Uma subpasta (`https://devfelipegomes.com.br/promotheusfiscaliza`) **compartilha a origem** do portal (esquema + host +
porta). Cookie com `Path` restrito e processo PHP separado **não** isolam as aplicações no navegador: um script
comprometido numa delas pode fazer requisições à outra com os cookies do usuário e ler as respostas
([MDN — mesma origem](https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Same-origin_policy)). Se o
navegador do proprietário estiver autenticado no painel do portal, isso importa.

Mitigações já presentes no desafio: CSP sem scripts inline nem externos, escape sistemático, nenhum HTML vindo de
usuários. Elas reduzem a chance de script injetado, mas não substituem origem separada.

**Proposta concreta (decisão do proprietário, não aplicada):** servir o desafio numa **origem dedicada**, por exemplo
`https://fiscaliza.devfelipegomes.com.br`, num VPS/contêiner com o `compose.yaml` deste repositório (Firebird em
rede interna, Apache/Caddy com TLS) e manter `/promotheusfiscaliza` no portal apenas como **redirecionamento 302**
para essa origem. Resolve, de uma vez, o isolamento de navegador e a falta de Firebird na hospedagem compartilhada.
Se o endereço precisar continuar sendo a subpasta, a alternativa é um proxy reverso do portal para o contêiner,
aceitando e documentando o limite de mesma origem. Antes de qualquer opção: conferir cookies do portal (escopo
`Path=/`), service workers e a CSP efetiva da raiz no ambiente real.

## Achados da auditoria de 18/10/2026

| Item | Natureza | Situação |
|---|---|---|
| SEC-01 — limite de login não atômico (gravidade baixa, CWE-362): dois processos levavam 4 falhas a 6 | Vulnerabilidade explorável de baixa gravidade (sem acesso indevido demonstrado) | **Corrigido**: reserva antes do hashing + índice único parcial; testado com processos concorrentes no serviço e no HTTP. |
| Script de avaliação de sementes sem o marcador sintético | Proteção operacional (erro de operação, não ataque) | Corrigido: mesmas proteções do `demo:seed --reset`. |
| Testes destrutivos sem checar se o banco de testes é o principal | Proteção operacional | Corrigido: recusa com evidência de separação; a CI deixou de definir `DB_DSN` igual a `DB_TEST_DSN`. |
| Credenciais privilegiadas disponíveis em web e simulador | Redução de exposição | Parcial: fora do ambiente e da configuração desses processos; o `.env` continua legível no contêiner local montado (ver abaixo). |

## Riscos restantes

- Mesma origem, se a publicação ficar na subpasta (acima).
- No Compose local, o projeto (inclusive o `.env` com as credenciais do dono) é montado nos contêineres `app` e
  `simulator`; quem executar código nesses contêineres pode ler o arquivo. Em publicação, o servidor web deve ter um
  `.env` só com as credenciais da aplicação.
- Limite de tentativas por IP depende de `REMOTE_ADDR` correto atrás de proxy (configurar o proxy para repassar o IP real).
- Sem 2FA: as contas são de avaliação/demonstração. Para uso real, exigir segundo fator.
- O contêiner oficial do Firebird roda o servidor como `root` (arquivos de dados do usuário `firebird`).
- Tiles de terceiros revelam ao provedor o IP e a região visualizada pelo visitante (política do provedor).

## Antes do primeiro push público

1. Conferir `git status`/`git ls-files` (nada de `.env`, `var/`, `docs/privado/`, `*.fdb`, `*.fbk`).
2. Varredura de segredos no conteúdo **e no histórico** (ex.: gitleaks) antes de enviar.
3. Na CI, usar apenas credenciais descartáveis de teste; credenciais de deploy só em variáveis protegidas e nunca
   em pipelines de contribuições externas.
4. Se algo vazar: revogar/rotacionar a credencial; apagar o arquivo não basta.
