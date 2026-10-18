# Operação

## Configuração (`.env` ou variáveis de ambiente)

Valores reais ficam só no ambiente; o repositório traz [`.env.example`](../.env.example) com valores fictícios.
`Config::load` lê o `.env` da raiz do projeto e, por cima, as variáveis reais do processo (estas vencem).

| Variável | Finalidade |
|---|---|
| `APP_ENV` | `production` (erros genéricos, cache de templates) · `development` · `test`. |
| `APP_BASE_PATH` | Prefixo público (`/promotheusfiscaliza`). Vazio = aplicação na raiz, **mas só com o Apache configurado para isso** (ver [Publicação](#publicação)). |
| `APP_SECURE_COOKIES` | `true` em HTTPS (cookie `Secure`). |
| `APP_ORIGIN` | Origem pública (`https://…`) para a checagem de `Origin` quando um proxy termina o TLS. |
| `APP_PORT` | Porta local do Compose (só em `127.0.0.1`). |
| `DB_DSN` | `firebird:dbname=<host>/<porta>:<caminho ou alias>;charset=UTF8;dialect=3`. |
| `DB_USER`, `DB_PASSWORD` | Usuário da aplicação (somente DML). |
| `DB_MIGRATION_USER`, `DB_MIGRATION_PASSWORD` | Dono do esquema: migrações, carga de demonstração e testes. |
| `DB_TEST_DSN` | Banco de testes (apagado e recriado pela suíte). Precisa ser **outro** banco: os testes recusam rodar se ele coincidir com `DB_DSN`. |
| `DB_STATEMENT_TIMEOUT_SECONDS` | Limite de espera por lock/instrução (padrão 5). |
| `FIREBIRD_ROOT_PASSWORD` | SYSDBA do contêiner Firebird, usado pela imagem na criação do banco. Nenhum processo da aplicação o usa. |
| `MAP_TILE_URL`, `MAP_TILE_ATTRIBUTION` | Provedor de tiles e atribuição obrigatória; a origem entra na CSP `img-src`. |
| `DEMO_ALLOW_RESET` | `true` só em ambientes de demonstração descartáveis. |
| `DEMO_PROFILE` | `compacto` (padrão: 16 equipamentos e 7 agentes) ou `completo` (48 e 21). Vale para `demo:seed` e `scripts/avaliar-sementes.php` ([ADR-0013](adr/0013-perfil-compacto-de-demonstracao.md)). |
| `SIM_SEED`, `SIM_INTERVAL_SECONDS`, `POLL_INTERVAL_SECONDS` | Semente do cenário, intervalo do simulador e intervalo de consulta da visão geral (independentes). |

### Credenciais por processo

| Processo | Credenciais que carrega |
|---|---|
| Web (`public/index.php`) | Só `DB_USER`/`DB_PASSWORD`. `DB_MIGRATION_*` e `FIREBIRD_ROOT_PASSWORD` são descartadas ao carregar a configuração (`Config::PRIVILEGED`). |
| `bin/fiscaliza migrate` e `demo:seed` | Identidade do dono do esquema. |
| Demais comandos (`sim:*`, `user:create`, `analysis:run`) | Só a identidade da aplicação. |
| Testes, `scripts/avaliar-sementes.php` | Dono do esquema, apenas no banco indicado (testes: `DB_TEST_DSN`). |

No Compose local, `app` e `simulator` não recebem mais o `.env` como variáveis de ambiente; o arquivo, porém, continua
montado e legível dentro do contêiner (o projeto inteiro é montado em `/app`). Isso reduz a exposição (variáveis de
processo, objetos da aplicação), não a elimina. **Em publicação**, o `.env` do servidor web deve conter só as
credenciais da aplicação; migrações e carga rodam num passo administrativo separado, com as credenciais do dono
fornecidas só para esse comando (por exemplo `DB_MIGRATION_USER=… DB_MIGRATION_PASSWORD=… php bin/fiscaliza migrate`
numa sessão de terminal, sem gravá-las no servidor).

## Comandos (`php bin/fiscaliza …`)

| Comando | O que faz |
|---|---|
| `migrate` | Aplica migrações pendentes (identidade de migração) e sincroniza GRANTs mínimos do usuário da aplicação. Nunca roda por HTTP. |
| `demo:seed [--perfil=compacto\|completo]` | Carrega o cenário sintético e 240 dias de histórico; recusa se já houver dados. `--reset` apaga dados de domínio (preserva contas) apenas com `DEMO_ALLOW_RESET=true` e o marcador do conjunto sintético. `--perfil` sobrepõe `DEMO_PROFILE`. Para levar uma demonstração existente de 69 para 23 recursos: `demo:seed --reset` (mesmas proteções; banco sem o marcador sintético é recusado). |
| `user:create <usuário> --role=ADMIN\|OPERADOR --name="…"` | Cria conta com senha aleatória exibida uma vez. |
| `sim:tick` | Um ciclo do simulador. Se outro ciclo do mesmo diretório ainda estiver rodando, este é **ignorado** (trava em `var/simulador.lock`). |
| `sim:run [--interval=5] [--duration=0]` | Simulador contínuo (serviço `simulator` do Compose), com a mesma trava a cada ciclo. Falha de banco: registra e tenta no próximo ciclo. |
| `analysis:run` | Recalcula a análise preventiva (também pelo botão "Recalcular agora", para operadores). |

### Atualizar um banco existente para a migração 0002

`migrate` aplica `0002_invariantes_e_cota_de_login.sql`: índice de intervenção única, cota de login e a classe
`NAO_AVALIADO`. Antes, faça backup e rode a consulta do comentário da migração para encontrar equipamentos com duas
ordens `EM_EXECUCAO` (se houver, o índice não é criado e a migração para). Falhas de login antigas continuam valendo.
Verificado em 18/10/2026: banco com o esquema 0001 e falhas de login antigas atualizado sem erro
([evidência](evidencias/2026-10-18-correcoes-da-auditoria.md)).

### Cron (hospedagem sem processo permanente)

Use caminhos absolutos e entre no diretório do projeto: o `.env` e a trava ficam ali.

```cron
* * * * * cd /srv/promotheus-fiscaliza && /usr/bin/php bin/fiscaliza sim:tick >> var/simulador.log 2>&1
15 3 * * * cd /srv/promotheus-fiscaliza && /usr/bin/php bin/fiscaliza analysis:run >> var/analise.log 2>&1
```

Um ciclo por minuto. Sobreposição: se um ciclo demorar mais que o intervalo, o seguinte encontra a trava e termina com
"Ciclo ignorado" (testado em `OperationsTest`). A trava (`flock`) vale para processos que usam o **mesmo diretório**
`var/` (mesmo servidor ou mesmo volume); dois servidores com cópias diferentes do projeto não se enxergam. Não use
`sim:run --duration` como garantia contra sobreposição: a duração só limita quanto tempo o processo roda.

### Avaliação do método em várias sementes

`php scripts/avaliar-sementes.php [--perfil=compacto|completo] 2027 2028 …` **substitui** os dados de domínio do banco `DB_DSN` (equipamentos,
agentes, ordens, eventos, alertas, análises; contas são preservadas) pelo cenário de cada semente, em sequência, e
imprime as métricas. Exige `DEMO_ALLOW_RESET=true` e, se houver dados, o marcador do conjunto sintético (as mesmas
proteções de `demo:seed --reset`). Ao terminar, fica carregado o cenário da **última** semente; para voltar ao padrão,
`php bin/fiscaliza demo:seed --reset` (usa `SIM_SEED` e `DEMO_PROFILE`).

## Interface: build de assets

Os arquivos que o navegador recebe ficam em `public/assets/build/` e **são versionados**: instalar, testar e publicar
não exigem Node. Só para mudar o visual ou os scripts ([`resources/`](../resources/)):

```bash
npm ci            # versões travadas em package-lock.json (Node 20.19+)
npm run build     # Vite: CSS (Tailwind), módulo ES (Alpine CSP + GSAP), fontes, manifest.json e licencas-de-terceiros.md
# sem Node instalado:
docker run --rm -v "$PWD":/app -w /app node:22-bookworm sh -c "npm ci && npm run build"
```

O PHP lê `public/assets/build/manifest.json` ([`ViteAssets`](../src/Http/ViteAssets.php)); nomes com hash dispensam
limpar cache. O mesmo build serve sob `/promotheusfiscaliza` e na origem dedicada (referências relativas). O CI confere
que o build versionado corresponde ao código-fonte (`git diff --exit-code public/assets/build` depois de
`npm ci && npm run build`).

## Backup e restauração

**Ensaio (o que o script faz).** [`scripts/backup-restore-teste.sh`](../scripts/backup-restore-teste.sh) roda no
projeto Compose padrão e usa **nomes fixos**: faz `gbak -b` de `/var/lib/firebird/data/fiscaliza.fdb` para
`backup-<data-hora>.fbk` e restaura num banco **separado**, `fiscaliza_restaurado.fdb` (apagado e recriado a cada
execução), comparando contagens. Ele não lê `DB_DSN`: se o banco principal tiver outro nome ou alias, o script precisa
ser adaptado. Serve para provar que o backup restaura; não troca o banco em uso. Resultado em
[evidências](evidencias/2026-10-18-backup-restauracao.md).

**Restauração operacional (procedimento manual).**

1. Pare `app` e `simulator` (`docker compose stop app simulator`) para ninguém gravar durante a troca.
2. Restaure o `.fbk` escolhido num arquivo **novo** (`gbak -c … backup.fbk fiscaliza_<data>.fdb`), como no script.
3. Aponte `DB_DSN` para o arquivo novo (ou, com o servidor Firebird parado, renomeie os arquivos).
4. Rode `php bin/fiscaliza migrate` (não aplica nada se o esquema estiver em dia; reaplica os GRANTs).
5. Suba `app` e `simulator` e confira `/saude` e a visão geral.

Nunca copie o `.fdb` com o servidor em uso. Guarde backups fora de diretórios servidos e fora do Git.

## Logs e recuperação

- Erros PHP e exceções inesperadas: `/var/log/fiscaliza/php-error.log` no contêiner `app` (linhas com
  `[fiscaliza <referência>]` — a mesma referência mostrada ao usuário). Acesso Apache: `docker compose logs app`.
- Simulador: `docker compose logs -f simulator` (uma linha JSON por ciclo; "Ciclo ignorado" quando houve sobreposição).
- Banco indisponível: páginas respondem 503 com mensagem genérica; a visão geral mostra "sem conexão" e tenta de novo
  com recuo até 60 s; o simulador continua tentando.
- Lock preso: o timeout de instrução encerra a espera; a operação retorna 409 "registro ocupado, tente novamente".
- Ambiente local do zero: `docker compose down -v` apaga **todos** os dados locais (volume do Firebird).

## Publicação

**Não realizada.** Endereço pretendido: `https://devfelipegomes.com.br/promotheusfiscaliza`. Um subdiretório, sozinho,
**não** isola a aplicação do portal: os dois ficam na mesma origem ([docs/seguranca.md](seguranca.md#publicação-e-limite-de-mesma-origem)).

| Opção | Firebird | Isolamento de origem | Configuração |
|---|---|---|---|
| **A. Origem dedicada** (`https://fiscaliza.devfelipegomes.com.br/`) + redirecionamento do caminho no portal | ✓ (Compose) | ✓ | **Recomendada.** `compose.origem-dedicada.yaml`. |
| B. Proxy reverso do portal com prefixo (`/promotheusfiscaliza` → contêiner) | ✓ | ✗ (mesma origem) | Compose padrão; prefixo mantido. |
| C. Hospedagem compartilhada do portal | Improvável (sem servidor Firebird; `pdo_firebird` incerto) | ✗ | Não recomendada. |

### Opção A — origem dedicada (passo a passo)

1. VPS/contêiner com Docker. DNS `fiscaliza.devfelipegomes.com.br` apontando para ele.
2. `.env` do servidor com `APP_ENV=production`, `APP_SECURE_COOKIES=true`,
   `APP_ORIGIN=https://fiscaliza.devfelipegomes.com.br`, `DEMO_ALLOW_RESET=false` (depois da carga) e senhas novas.
   `APP_BASE_PATH` fica vazio pela sobreposição abaixo.
3. Subir com a sobreposição, que troca o site do Apache por
   [`apache-origem-dedicada.conf`](../docker/php/apache-origem-dedicada.conf) (aplicação na raiz; `/promotheusfiscaliza/…`
   redireciona com 301 para `/…`, preservando caminho e consulta):
   `docker compose -f compose.yaml -f compose.origem-dedicada.yaml up -d --build`.
4. TLS num proxy à frente (Caddy, Traefik ou nginx) encaminhando para `127.0.0.1:${APP_PORT}`; o proxy deve repassar o
   IP real do visitante, senão o limite de login por IP vale para o proxy inteiro.
5. No portal (fora deste repositório, decisão do proprietário), redirecionar o caminho antigo, por exemplo no
   `.htaccess` da raiz: `RedirectMatch 302 ^/promotheusfiscaliza(/.*)?$ https://fiscaliza.devfelipegomes.com.br$1`.
   Não copie configuração, segredos ou dados do portal para este projeto.
6. Conferir: `curl -sI https://fiscaliza…/` (CSP, cookies só no login com `Path=/`), `curl -sI
   https://devfelipegomes.com.br/promotheusfiscaliza/equipamentos` (302 para a nova origem), assets em `/assets/…`.

Verificado localmente em 18/10/2026 (porta 8091): raiz, `/api/central`, `/assets/css/app.css` e `/equipamentos`
respondem 200 (repetido depois do novo build, porta 8092: CSS e módulo em `/assets/build/…`, fonte carregada, módulo
da central carregado sob demanda, ficha abrindo, sem erros 4xx/5xx); `/promotheusfiscaliza/equipamentos?q=radar` responde 301 para `/equipamentos?q=radar`; cookie de sessão
com `path=/`. O DNS, o TLS e o redirecionamento no portal não foram executados.

### Opção B — proxy reverso com prefixo

Mantém `APP_BASE_PATH=/promotheusfiscaliza` e o Apache padrão do contêiner (que só serve a aplicação sob o prefixo).
No servidor do portal (requer `mod_proxy`/`mod_proxy_http`):

```apache
ProxyPass        /promotheusfiscaliza http://127.0.0.1:8080/promotheusfiscaliza
ProxyPassReverse /promotheusfiscaliza http://127.0.0.1:8080/promotheusfiscaliza
```

`APP_ORIGIN=https://devfelipegomes.com.br`, `APP_SECURE_COOKIES=true`. Assets, formulários, `fetch` e redirecionamentos
já usam o prefixo. Para o limite por IP funcionar, habilite `mod_remoteip` no contêiner confiando só no proxy.
**Limite:** mesma origem do portal — aceitar e registrar, ou preferir a opção A.

### Checklist de produção (qualquer opção)

`APP_ENV=production`, `DEMO_ALLOW_RESET=false` depois da carga, senhas novas e exclusivas, `.env` do servidor web só
com as credenciais da aplicação, Firebird sem porta pública, backups agendados, `user:create` executado no servidor,
simulador por serviço ou cron (com a trava), conferência dos cabeçalhos efetivos (`curl -sI`) e da CSP do portal
(não alterada).
