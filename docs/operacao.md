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
| `CEP_LOOKUP_URL` | Provedor da consulta de CEP do cadastro de agente (padrão `https://viacep.com.br/ws`; só `https`). Exige saída HTTPS do contêiner `app`; fora do ar, a tela pede a rua ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). |
| `GEOCODER_URL` | Provedor do ponto pela rua, para quem não consegue marcar a base no mapa (padrão `https://nominatim.openstreetmap.org`; só `https`; no máximo 1 consulta/s, espaçadas pelo servidor). Exige saída HTTPS do contêiner `app`; fora do ar, o caminho sem mapa para e a tela explica. |
| `INFOSIGA_URL` | Arquivo mensal do Infosiga-SP lido por `urban:update` (padrão `https://infosiga.detran.sp.gov.br/painel/download/file/dados_infosiga.zip`, `UrbanUpdater::SOURCE_URL`). Exige saída HTTPS do processo que roda o comando (serviço `atualizador` ou cron). |
| `DEMO_ALLOW_RESET` | `true` só em ambientes de demonstração descartáveis. |
| `DEMO_PROFILE` | `compacto` (padrão: 17 equipamentos e 7 agentes) ou `completo` (48 e 21). Vale para `demo:seed` e `scripts/avaliar-sementes.php` ([ADR-0013](adr/0013-perfil-compacto-de-demonstracao.md)). |
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
| `demo:seed-multas` | Acrescenta o cenário de multas (12 no perfil compacto, 40 no completo) a uma demonstração **já carregada**, sem apagar nada: exige o marcador do conjunto sintético; a mesma versão não é carregada duas vezes; carga de outra versão ou parcial é recusada sem tocar em nada; a carga `multas-v1` (primeira versão publicada) é **atualizada no lugar** para `multas-v2` — relatos descritivos e placa lida no rascunho de radar —, com cada mudança no histórico do registro. Roda com a identidade de migração. `demo:seed` já inclui as multas. |
| `user:create <usuário> --role=ADMIN\|OPERADOR --name="…"` | Cria conta com senha aleatória exibida uma vez. |
| `sim:tick` | Um ciclo do simulador. Se outro ciclo do mesmo diretório ainda estiver rodando, este é **ignorado** (trava em `var/simulador.lock`). |
| `sim:run [--interval=5] [--duration=0]` | Simulador contínuo (serviço `simulator` do Compose), com a mesma trava a cada ciclo. Falha de banco: registra e tenta no próximo ciclo. |
| `analysis:run` | Recalcula a análise preventiva (também pelo botão "Recalcular agora", para operadores). |
| `urban:update [--forcar-download] [--arquivo=…] [--semana=AAAA-MM-DD]` | Rotina agendável: download mensal do Infosiga-SP (guarda só 12 meses de contagens de Marília por quadrado e dia; apaga o ZIP) e previsão da semana. Idempotente. Serviço `atualizador` do Compose (`docker compose --profile atualizador up -d atualizador`, a cada 6 h) ou cron do host: `0 */6 * * * cd /srv/promotheus-fiscaliza && docker compose exec -T app php -d memory_limit=512M bin/fiscaliza urban:update >> var/atualizador.log 2>&1`. Saída 1 se o download falhar (a previsão semanal roda mesmo assim). Detalhes: [planejamento-por-setores.md](planejamento-por-setores.md#atualização-automática-mensal-e-semanal). |
| `urban:import [--dir=…] [--setores=…] [--manifesto=…] [--grade=…\|--sem-grade]` | Importa uma execução **completa** do treino por setores (padrão: `ml/marilia-setores/runs/primeiro-treino-verificado`) e os quadrados de 2 km (padrão: `ml/marilia-grade-2km/dados/contagens_grade.csv`; uma execução já importada recebe os quadrados que faltam). Valida tudo antes de gravar; os mesmos bytes não duplicam; recusa referência com mais de 180 dias. Roda com a identidade da aplicação. Treino e avaliação ficam fora da web: [planejamento-por-setores.md](planejamento-por-setores.md#comandos). |

### Atualizar um banco existente para a migração 0003

`migrate` aplica `0003_planejamento_por_setores.sql` (somente `CREATE`, sem risco para dados existentes) e concede ao
usuário da aplicação só leitura e inserção nas tabelas importadas e no histórico das missões. Depois, rode
`urban:import` uma vez. A migração `0004_quadrados_de_2km.sql` (somente `CREATE`) acrescenta `URBAN_CELL`; depois dela,
rodar `urban:import` de novo grava os quadrados na execução existente. A migração `0005_atualizacao_mensal_infosiga.sql`
(somente `CREATE`) acrescenta `URBAN_SNAPSHOT` e `URBAN_DAILY`. **A imagem PHP precisa ser reconstruída**
(`docker compose up -d --build`): ela ganhou a extensão `zip`. A migração `0006_missao_por_quadrado.sql` só acrescenta a
coluna anulável `CELL_NO` em `URBAN_MISSION`. Depois, `urban:update` uma vez e o serviço `atualizador`. `demo:seed --reset` apaga as missões fictícias junto com os agentes, mas mantém as execuções
importadas (dados reais).

### Atualizar um banco existente para a migração 0007 (multas)

`migrate` aplica `0007_multas.sql` (somente `CREATE` de `FINE`, `FINE_DOCUMENT_CHECK` e `FINE_EVENT`; nada muda em
recursos, contas ou histórico) e concede ao usuário da aplicação só leitura e inserção nas duas tabelas de histórico,
revogando `UPDATE`/`DELETE` se tiverem sido concedidos antes. A aba fica vazia até haver cadastros; numa demonstração,
`php bin/fiscaliza demo:seed-multas` acrescenta os casos sem apagar nada. Não use `demo:seed --reset` como procedimento
de atualização. Verificado em 21/10/2026 num banco populado pela versão anterior
([evidência](evidencias/2026-10-21-multas.md#migração-de-banco-existente)).

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

Feita pelo proprietário, fora deste repositório (nenhum dado da hospedagem fica aqui). Endereço pretendido: `https://devfelipegomes.com.br/promotheusfiscaliza`. Um subdiretório, sozinho,
**não** isola a aplicação do portal: os dois ficam na mesma origem ([docs/seguranca.md](seguranca.md#publicação-e-limite-de-mesma-origem)).

| Opção | Firebird | Isolamento de origem | Configuração |
|---|---|---|---|
| **A. Origem dedicada** (`https://fiscaliza.devfelipegomes.com.br/`) + redirecionamento do caminho no portal | ✓ (Compose) | ✓ | **Recomendada.** `compose.origem-dedicada.yaml`. |
| B. Proxy reverso do portal com prefixo (`/promotheusfiscaliza` → contêiner) | ✓ | ✗ (mesma origem) | Compose padrão; prefixo mantido. |
| C. Hospedagem compartilhada do portal | Improvável (sem servidor Firebird; `pdo_firebird` incerto) | ✗ | Não recomendada. |

### Landing pública na raiz do contêiner

Com o `compose.yaml` padrão, o Apache do contêiner serve a landing (`landing/public`) em `/` e a aplicação em
`/promotheusfiscaliza`. Publicar, conferir e voltar à página anterior: [landing/README.md](../landing/README.md#publicação-no-servidor-atual).
A troca de `DocumentRoot` está na imagem, então a primeira atualização depois dela pede `docker compose up -d --build`.

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

### Atualizar uma instalação publicada

Para levar uma instalação que já está no ar (por exemplo, a versão anterior à identidade nova e ao acesso só com
conta) para a versão atual. Rode na pasta do projeto no servidor, com os mesmos `-f`/`-p` usados na instalação (na
opção A, `-f compose.yaml -f compose.origem-dedicada.yaml` em todos os comandos, ou `COMPOSE_FILE` no ambiente).
Node não é necessário: o build da interface já vem versionado em `public/assets/build/`.

1. **Backup antes de tudo** (fica no volume do Firebird, fora do alcance HTTP):
   ```bash
   docker compose exec -T firebird sh -c 'gbak -b -g -user SYSDBA -password "$FIREBIRD_ROOT_PASSWORD" localhost:/var/lib/firebird/data/fiscaliza.fdb /var/lib/firebird/data/antes-da-atualizacao.fbk'
   ```
2. Código novo: `git status` precisa estar limpo (o `.env` e `vendor/` não entram no Git); depois `git pull` na
   branch publicada.
3. Contêineres, dependências e esquema:
   ```bash
   docker compose up -d --build
   docker compose exec app composer install --no-dev --optimize-autoloader
   docker compose exec app php bin/fiscaliza migrate
   docker compose --profile simulador up -d --force-recreate simulator
   docker compose exec app rm -rf var/cache/twig
   ```
   O simulador é um processo contínuo: sem o `--force-recreate` ele continuaria com o código antigo. A última linha
   apaga os templates compilados em produção: versões anteriores a 19/10/2026 não recompilavam um template alterado
   e continuavam mostrando a tela antiga (página sem estilo, com "Entrar para editar"); a partir desta versão o
   Twig recompila sozinho o que mudou, e apagar o cache continua inofensivo.
4. **Acesso só com conta** ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)): a partir desta versão nada
   abre sem login. As contas existentes continuam valendo. Se ainda não houver administrador, crie um (a senha aparece
   uma única vez): `docker compose exec app php bin/fiscaliza user:create nome --role=ADMIN --name="Nome"`. As demais
   pessoas são cadastradas por ele em **Contas**.
5. Opcional — **cenário compacto** (17 equipamentos e 7 agentes, [ADR-0013](adr/0013-perfil-compacto-de-demonstracao.md)):
   um banco já carregado mantém o cenário que tem. Trocar **apaga e recarrega os dados de demonstração** (as contas de
   acesso são preservadas) e só é aceito no conjunto sintético: com `DEMO_ALLOW_RESET=true` no `.env`, rode
   `docker compose exec app php bin/fiscaliza demo:seed --reset --perfil=compacto` e volte `DEMO_ALLOW_RESET=false`.
6. Conferir: sem conta, `curl -sI https://…/promotheusfiscaliza/` responde 302 para `/entrar` e `/api/central`
   responde 401; `/saude` responde 200; a tela de login diz "Acesso restrito", com o visual novo (se aparecer sem
   estilo e com "Entrar para editar", o cache de templates do passo 3 não foi apagado); depois do login, a visão
   geral abre com a marca nova. Os assets têm o hash no
   nome e as miniaturas levam versão, então ninguém precisa limpar o cache do navegador.

Para voltar atrás: `git checkout` da versão anterior, os mesmos comandos do passo 3 e, se necessário, a restauração
do backup do passo 1 ([Backup e restauração](#backup-e-restauração)).

### Checklist de produção (qualquer opção)

`APP_ENV=production`, `DEMO_ALLOW_RESET=false` depois da carga, senhas novas e exclusivas, `.env` do servidor web só
com as credenciais da aplicação, Firebird sem porta pública, backups agendados, `user:create` executado no servidor,
simulador por serviço ou cron (com a trava), conferência dos cabeçalhos efetivos (`curl -sI`) e da CSP do portal
(não alterada), saída HTTPS do contêiner `app` para o ViaCEP e o Nominatim (comandos de conferência na
[ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md#saída-para-a-internet-no-contêiner-publicado); sem ela,
o cadastro de agente funciona só pelo mapa).
