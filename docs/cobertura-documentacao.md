# Cobertura da documentação

Matriz que prova o que esta documentação cobre: pastas, arquivos, símbolos, rotas e comandos, telas e regras críticas,
mais as **divergências** encontradas entre código e documentos e as **pendências**. Gerada e revisada em 26/10/2026 sobre
o commit `c507882` (com o trabalho não versionado da árvore de trabalho).

Como manter: ao mudar código, atualize a página de referência que lista o arquivo em `<!-- fontes: … -->`, regenere o
catálogo e rode a verificação:

```bash
node scripts/documentacao/inventario.mjs --catalogo
```

```bash
node scripts/documentacao/inventario.mjs --verificar
```

`--verificar` falha se faltar página para um arquivo de código, um símbolo na sua página, um README ou linha nesta
matriz para uma pasta, ou se houver link relativo ou âncora quebrados ([inventário](../scripts/documentacao/README.md)).

## Resumo

| Dimensão | Cobertura | Onde |
|---|---|---|
| Arquivos de código próprio (PHP, JS/MJS/CJS, Python, `bin/fiscaliza`) | 198 arquivos, todos com página de referência | [referência de código](referencia/codigo/README.md) |
| Símbolos nomeados (classes, enums, interfaces, funções, métodos — inclusive privados —, objetos com funções, componentes Alpine, macros Twig) | **1867 de 1867** com descrição na página do arquivo | [catálogo de símbolos](referencia/catalogo-simbolos.md) |
| Pastas próprias | todas com `README.md` ou linha abaixo | [pastas](#pastas-próprias) |
| Rotas HTTP | 57 registros de rota em `WebApp` (GET e POST) | [rotas](#rotas-e-comandos) |
| Comandos de CLI | 9 comandos + ajuda | [console](referencia/codigo/cli/console.md) |
| Migrações | 11 de 11 | [dicionário de dados](referencia/dicionario-de-dados.md) |
| Abas e funcionalidades | 9 abas + login, landing, ajuda, alertas, retrospecto, demonstração, dados/ML, vídeo/automações | [guias](guias/README.md) |
| Casos de teste | todos os métodos de teste e auxiliares das 30 classes PHPUnit + 4 casos Python | [casos de teste](referencia/codigo/testes/casos.md) |
| ADRs | 23 de 23 indexadas, nenhuma reescrita | [ADRs](adr/README.md) |
| Evidências | 22 registros indexados com suas pastas | [evidências](evidencias/README.md) |

Status usados: **documentado** (explicado no destino indicado), **agregado em <documento>** (pasta sem README próprio,
explicada no documento responsável), **excluído: <motivo>**.

## Pastas próprias

Pastas com `README.md` próprio (documentado): `src/` e todas as subpastas, `bin/`, `resources/` e subpastas,
`templates/` e subpastas, `tests/` e subpastas (inclusive `tests/Unit/fixtures/`), `scripts/`,
`scripts/documentacao/`, `scripts/verificacao-interface/`, `database/migrations/`, `docker/`, `ml/`, `landing/`,
`video/`, `docs/`, `docs/adr/`, `docs/evidencias/`, `docs/guias/`, `docs/referencia/`, `docs/referencia/codigo/`.

Pastas sem README próprio, com o destino documental e a justificativa:

| Pasta | Tipo | Destino | Justificativa / status |
|---|---|---|---|
| `.github/` | configuração | [implantação e CI](referencia/implantacao-e-ci.md#integração-contínua) | só contém `workflows/`; agregado |
| `.github/workflows/` | configuração | idem | `ci.yml` (testes e assets); agregado — um README ali não é necessário |
| `database/` | esquema | [`database/migrations/README.md`](../database/migrations/README.md) | só contém `migrations/`; agregado |
| `docker/firebird/` | configuração | [`docker/README.md`](../docker/README.md), [implantação](referencia/implantacao-e-ci.md#firebird-dockerfirebird10-fiscaliza-initsh) | um script; agregado |
| `docker/php/` | configuração | idem, [imagem PHP](referencia/implantacao-e-ci.md#imagem-php-dockerphp) | agregado |
| `docker/php/portal-stub/` | conteúdo histórico | idem | página antiga da raiz, mantida para reverter a landing; agregado |
| `public/` | entrada web | [configuração e serviços](referencia/codigo/raiz/configuracao-e-servicos.md#pontos-de-entrada) | **servida pelo Apache**: um README ali seria publicado; agregado |
| `public/assets/` | assets distribuídos | [implantação](referencia/implantacao-e-ci.md#pastas-geradas-ou-locais-não-editar-à-mão) | idem |
| `public/assets/build/` | gerado | idem | saída de `npm run build` (versionada; a CI confere); não editar |
| `public/assets/fonts/` | licença | [licenças e fontes](licencas-e-fontes.md) | só `OFL-overpass.txt` (as fontes em si vão no build) |
| `public/assets/img/` | imagens próprias | [licenças e fontes](licencas-e-fontes.md) | `favicon.svg`, sprite `icons.svg` (usado por `lib/dom.js`) |
| `public/assets/img/autor/` | foto do autor | [licenças e fontes](licencas-e-fontes.md) | fora da licença MIT; cartão da página Sobre |
| `public/assets/img/miniaturas/` | ilustrações próprias | [Sobre](guias/sobre-e-interface.md) | uma por tipo de recurso e função de agente (`about.twig`, ficha do agente) |
| `public/assets/vendor/` | terceiros | [licenças e fontes](licencas-e-fontes.md) | só `leaflet/` |
| `public/assets/vendor/leaflet/` | terceiros | idem | Leaflet 1.9.4 distribuído (BSD-2-Clause, `LICENSE` junto); não alterar |
| `public/assets/vendor/leaflet/images/` | terceiros | idem | imagens do Leaflet |
| `landing/public/` | entrada web da landing | [referência da landing](referencia/codigo/landing/landing.md#servidor-php) | servida na raiz: sem README por segurança |
| `landing/public/landing-assets/` | assets da landing | [`landing/README.md`](../landing/README.md) | agregado |
| `landing/public/landing-assets/promotheus/` | assets da landing | idem | `boot.js`, licença da fonte, subpastas |
| `landing/public/landing-assets/promotheus/build/` | gerado | idem | saída de `npm run build:landing`; a CI confere |
| `landing/public/landing-assets/promotheus/img/` | capturas reais e foto | [landing](referencia/codigo/landing/landing.md#capturas-e-mídia) | capturas do sistema com dados de demonstração; foto © Pedro Monteiro |
| `landing/public/landing-assets/promotheus/video/` | mídia | idem | `apresentacao.mp4`, `capa.webp`, `LEIA-ME.txt` |
| `landing/src/` | fonte da landing | [referência da landing](referencia/codigo/landing/landing.md) | coberta pelo README da landing e pela referência |
| `ml/marilia-setores/` | pacote de dados e treino | [`ml/README.md`](../ml/README.md), [referência](referencia/codigo/ml/treino-e-experimento.md) | **pacote imutável** (26 arquivos conferidos pelo manifesto); `00-COMECE-AQUI.md` faz o papel de README; nada foi acrescentado dentro dele |
| `ml/marilia-setores/dados/` | dados agregados | [`ml/README.md`](../ml/README.md#contrato-dos-dados) | origem, esquema e licença lá |
| `ml/marilia-setores/runs/` | execuções | [referência](referencia/codigo/ml/treino-e-experimento.md#execuções-versionadas-runs) | evidência histórica |
| `ml/marilia-setores/runs/piloto-verificado/` | execução | idem | modo piloto |
| `ml/marilia-setores/runs/primeiro-treino-verificado/` | execução | idem | execução importada por padrão |
| `ml/marilia-setores/tests/` | testes Python | idem | `test_protocolo.py` |
| `ml/marilia-grade-2km/` | experimento | [`ml/README.md`](../ml/README.md), `PROTOCOLO.md`, `RESULTADO.md` | agregado |
| `ml/marilia-grade-2km/dados/` | dados agregados | [`ml/README.md`](../ml/README.md#contrato-dos-dados) | `contagens_grade.csv` |
| `ml/marilia-grade-2km/resultados/` | resultados | [referência](referencia/codigo/ml/treino-e-experimento.md#mlmarilia-grade-2km--experimento-da-grade-20102026) | teste único, não se repete |
| `video/capturas/` | capturas autênticas | [`video/README.md`](../video/README.md), [referência](referencia/codigo/video/video.md) | trabalho em andamento, não versionado |
| `video/midia/` | narração e efeitos | idem | idem |
| `docs/referencia/codigo/application/` | referência | [índice da referência](referencia/codigo/README.md#application) | agregado |
| `docs/referencia/codigo/cli/` | referência | [índice](referencia/codigo/README.md#cli) | agregado |
| `docs/referencia/codigo/demo/` | referência | [índice](referencia/codigo/README.md#demo) | agregado |
| `docs/referencia/codigo/domain/` | referência | [índice](referencia/codigo/README.md#domain) | agregado |
| `docs/referencia/codigo/frontend/` | referência | [índice](referencia/codigo/README.md#frontend-e-telas) | agregado |
| `docs/referencia/codigo/http/` | referência | [índice](referencia/codigo/README.md#http) | agregado |
| `docs/referencia/codigo/infrastructure/` | referência | [índice](referencia/codigo/README.md#infrastructure) | agregado |
| `docs/referencia/codigo/landing/` | referência | [índice](referencia/codigo/README.md#fora-da-aplicação) | agregado |
| `docs/referencia/codigo/ml/` | referência | idem | agregado |
| `docs/referencia/codigo/raiz/` | referência | [índice](referencia/codigo/README.md#raiz-e-erros) | agregado |
| `docs/referencia/codigo/scripts/` | referência | [índice](referencia/codigo/README.md#fora-da-aplicação) | agregado |
| `docs/referencia/codigo/templates/` | referência | [índice](referencia/codigo/README.md#frontend-e-telas) | agregado |
| `docs/referencia/codigo/testes/` | referência | [índice](referencia/codigo/README.md#testes) | agregado |
| `docs/referencia/codigo/video/` | referência | [índice](referencia/codigo/README.md#fora-da-aplicação) | agregado |

Evidências (todas **agregadas em** [`docs/evidencias/README.md`](evidencias/README.md), que liga cada pasta ao seu
registro datado; o conteúdo histórico não é alterado): `docs/evidencias/2026-10-18-identidade-e-motion/`,
`docs/evidencias/2026-10-18-identidade-e-motion/capturas/`, `docs/evidencias/2026-10-18-identidade-e-motion/videos/`,
`docs/evidencias/2026-10-19-cartao-de-autoria/`, `docs/evidencias/2026-10-19-cartao-de-autoria/capturas/`,
`docs/evidencias/2026-10-19-cartao-de-autoria/videos/`, `docs/evidencias/2026-10-19-identidade-com-acesso-por-conta/`,
`docs/evidencias/2026-10-19-identidade-com-acesso-por-conta/capturas/`, `docs/evidencias/2026-10-20-landing-publica/`,
`docs/evidencias/2026-10-20-landing-publica/capturas/`, `docs/evidencias/2026-10-20-landing-publica/videos/`,
`docs/evidencias/2026-10-20-planejar-reforco/`, `docs/evidencias/2026-10-20-planejar-reforco/capturas/`,
`docs/evidencias/2026-10-21-multas/`, `docs/evidencias/2026-10-21-multas/capturas/`,
`docs/evidencias/2026-10-22-landing-atualizada/`, `docs/evidencias/2026-10-23-enfeites-3d/`,
`docs/evidencias/2026-10-23-planos-3d/`, `docs/evidencias/2026-10-24-labels-e-ajuda/`,
`docs/evidencias/2026-10-24-labels-e-ajuda/capturas/`, `docs/evidencias/capturas-2026-10-18/`,
`docs/evidencias/capturas-2026-10-18/antes/`, `docs/evidencias/capturas-2026-10-18/depois/`,
`docs/evidencias/capturas-2026-10-22-lembrete-conferencia/`, `docs/evidencias/capturas-2026-10-22-mapa-de-calor/`,
`docs/evidencias/capturas-2026-10-22-multas-campos/`, `docs/evidencias/capturas-2026-10-23-agente-base/`,
`docs/evidencias/capturas-2026-10-24-acompanhamento-alertas/`, `docs/evidencias/capturas-2026-10-24-revisao-logica-fluxos/`.

### Arquivos da raiz

| Arquivo | Documentado em |
|---|---|
| `README.md`, `LICENSE` | apresentação; licença MIT ([licenças](licencas-e-fontes.md)) |
| `compose.yaml`, `compose.origem-dedicada.yaml` | [implantação](referencia/implantacao-e-ci.md#composeyaml) |
| `composer.json`, `composer.lock`, `package.json`, `package-lock.json` | [versões declaradas](referencia/implantacao-e-ci.md#versões-declaradas) |
| `vite.config.js`, `phpunit.xml`, `phpstan.neon`, `.php-cs-fixer.dist.php` | [ferramentas de qualidade](referencia/implantacao-e-ci.md#ferramentas-de-qualidade) |
| `.gitlab-ci.yml` | [integração contínua](referencia/implantacao-e-ci.md#integração-contínua) |
| `.env.example` | [operação: configuração](operacao.md#configuração-env-ou-variáveis-de-ambiente) |
| `.gitignore`, `.gitattributes` | [pastas geradas e excluídas](#pastas-geradas-de-terceiros-e-excluídas); `.gitattributes` preserva os bytes de `ml/marilia-setores/` |

## Pastas geradas, de terceiros e excluídas

Não são documentadas arquivo a arquivo; aqui fica por quê e como reproduzi-las.

| Pasta | Natureza | Tratamento |
|---|---|---|
| `.git/` | controle de versão | excluído |
| `vendor/` | dependências PHP | reinstalar com `composer install` (versões no `composer.lock`) |
| `node_modules/`, `video/node_modules/` | dependências Node | `npm ci` (raiz, versões no `package-lock.json`); `npm install` em `video/` |
| `public/assets/build/`, `landing/public/landing-assets/promotheus/build/` | builds versionados | regerar com `npm run build` / `npm run build:landing`; a CI falha se divergirem das fontes |
| `public/assets/vendor/leaflet/` | biblioteca distribuída | Leaflet 1.9.4, licença ao lado; não alterar |
| `var/` | local, ignorado | cache de templates e do PHPStan, trava do simulador e arquivos locais de trabalho do autor (inclusive credenciais de teste) — **não lidos nem publicados** por esta documentação |
| `.phpunit.cache/`, `.php-cs-fixer.cache` | caches | regenerados pelas ferramentas |
| `dist/` | pacotes de entrega, ignorado | gerados com `git archive` ([landing](../landing/README.md)) |
| `ml/marilia-setores/.venv/`, `fonte/` | ambiente e arquivo bruto do treino | ignorados; recriados por `TREINAR.cmd` / `preparar_dados.py` |
| `video/saida/`, `video/midia/efeitos.wav`, `video/midia/mix.wav` | saídas do vídeo | ignoradas pelo `.gitignore` da pasta; geradas por `render.mjs` e `sons.mjs` |
| `docs/privado/` | notas privadas, ignorado | excluído: não faz parte da documentação publicada |
| `.claude/` | configuração local de ferramenta de desenvolvimento, ignorada pelo Git | excluído |
| `.playwright-mcp/` | registros locais de ferramenta, ignorados pelo Git | excluído |
| `.env` | configuração real com segredos | **não lido**; documentado só pelo `.env.example` |
| `landingpage/` | pasta vazia na raiz, sem arquivos nem referência no código | registrada como residual; nada a documentar |

## Rotas e comandos

Todas as rotas de `WebApp::create` estão em [HTTP](http.md) (acesso, campos, respostas) e em
[controladores](referencia/codigo/http/controladores.md) (método por rota). Resumo por área:

| Área | Rotas | Guia | Testes |
|---|---|---|---|
| Abertas | `GET/POST /entrar`, `POST /sair`, `GET /saude` | [acesso](guias/acesso-e-contas.md) | `WebSecurityTest`, `LoginQuotaTest` |
| Visão geral | `GET /`, `GET /api/central` | [visão geral](guias/visao-geral.md) | `CentralAndAnalysisTest`, `WebSecurityTest` |
| Endereço | `GET /api/cep/{cep}`, `GET /api/localizar` | [agentes](guias/agentes.md#base-de-trabalho-mapa-cep-e-rua) | `AgentsHttpTest`, `CepLookupTest`, `AddressLocatorTest` |
| Equipamentos | 10 rotas `/equipamentos…` (inclui abrir ordem) | [equipamentos](guias/equipamentos.md) | `InventoryTest`, `EquipmentLocationTest`, `WebSecurityTest` |
| Agentes e alertas de agente | 12 rotas `/agentes…` | [agentes](guias/agentes.md), [alertas](guias/alertas-e-acompanhamento.md) | `AgentBaseTest`, `AlertFollowUpTest`, `AgentsHttpTest` |
| Manutenção e alertas | `GET /manutencao`, `POST /manutencoes/{id}/iniciar\|concluir\|cancelar`, `POST /alertas/{id}/reconhecer` | [manutenção](guias/manutencao.md) | `MaintenanceInvariantsTest`, `OperationsTest` |
| Revisões | `GET /antecipacao`, `POST /antecipacao/recalcular` | [revisões](guias/revisoes-recomendadas.md) | `AttentionModelTest`, `CentralAndAnalysisTest` |
| Multas | 10 rotas `/multas…` | [multas](guias/multas.md) | `FinesTest`, `FinesHttpTest`, `FineRulesTest` |
| Planejar | `GET /planejar`, `GET /planejar/retrospecto`, 3 `POST /planejar/missoes…` | [planejar](guias/planejar-reforco.md) | `PlanningTest`, `PlanningHttpTest` |
| Sobre | `GET /sobre` | [sobre](guias/sobre-e-interface.md) | `WebSecurityTest` (acesso) |
| Contas | `GET/POST /contas`, `POST /contas/{id}/situacao`, `POST /contas/{id}/senha` | [contas](guias/acesso-e-contas.md) | `AccountsTest`, `ReviewFlowsHttpTest` |

Comandos: `migrate`, `demo:seed`, `demo:seed-multas`, `user:create`, `sim:tick`, `sim:run`, `analysis:run`,
`urban:import`, `urban:update` — [console](referencia/codigo/cli/console.md). Scripts fora da CLI:
[scripts](referencia/codigo/scripts/scripts.md).

## Regras críticas

| Regra | Implementação | Documentação | Teste |
|---|---|---|---|
| Uma intervenção em execução por equipamento | `MaintenanceService::start` + `UX_MAINT_ONE_RUNNING` | [manutenção](guias/manutencao.md) | `MaintenanceInvariantsTest::testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne` |
| Uma missão vigente por agente | `PlanningService::confirm` + `UX_URBAN_MISSION_ACTIVE` | [planejar](guias/planejar-reforco.md) | `PlanningTest::testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict` |
| Um alerta aberto por causa | `AlertService::reconcile` + `UQ_ALERT_OPEN_KEY` | [alertas](guias/alertas-e-acompanhamento.md) | `OperationsTest::testConcurrentReconcileNeverDuplicatesAnOpenAlert` |
| Ciência não resolve | `AlertService::acknowledge` | idem | `OperationsTest::testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve` |
| Cota de login atômica | `AuthService::reserve` + `UX_LOGIN_ATTEMPT_SLOT` | [acesso](guias/acesso-e-contas.md) | `LoginQuotaTest::testConcurrentAttemptsNeverExceedTheUserLimit` |
| Último administrador ativo | `AuthService::setActive` | idem | `AccountsTest::testCrossDeactivationAtTheSameTimeKeepsOneActiveAdmin` |
| Redefinição encerra sessões | `AuthService::resetPassword` + `SessionContext` | idem | `ReviewFlowsHttpTest::testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd` |
| Versão do cadastro (sem perda de edição) | `EquipmentService::update`, `AgentService::update`, `FineService` | [equipamentos](guias/equipamentos.md) | `InventoryTest::testStaleFormVersionIsRejectedWithoutLosingTheOtherEdit` |
| Envio repetido de multa não duplica | `FineService::once` + `UQ_FINE_EVENT_UID` | [multas](guias/multas.md) | `FinesTest::testSameCommandSentTwiceAtTheSameTimeIsAReplayNotAConflict` |
| AIT único por órgão | `UX_FINE_AIT` | idem | `FinesTest::testAitIsUniquePerAgencyAndAbsentAitsNeverCollide` |
| RENAVAM com zeros à esquerda | `VARCHAR(11)` + `FineRules` | idem | `FinesTest::testRenavamKeepsLeadingZerosAndMayBeJustifiedlyMissing` |
| Histórico só de acréscimo | `Migrator::grantApplicationAccess` | [dicionário](referencia/dicionario-de-dados.md#privilégios-do-usuário-da-aplicação) | `DriverContractTest::testApplicationIdentityCannotRewriteHistoryOrChangeSchema` |
| Evento atrasado não regride o estado | `EventIngestor` | [demonstração](guias/demonstracao-e-simulacao.md) | `OperationsTest::testLateEventGoesToHistoryWithoutRegressingCurrentState` |
| Análise sem informação do futuro | `AnalysisService::features` | [revisões](guias/revisoes-recomendadas.md) | `CentralAndAnalysisTest::testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff` |
| Mapa, lista e indicadores do mesmo filtro | `CentralQuery::snapshot` | [visão geral](guias/visao-geral.md) | `CentralAndAnalysisTest::testMapListAndIndicatorsComeFromTheSameFilteredSet` |
| Testes nunca no banco principal | `FirebirdTestCase` + `DatabaseSeparation` | [suporte](referencia/codigo/testes/suporte.md) | `DatabaseSeparationTest` |

## Divergências e pendências encontradas

### Corrigidas nesta documentação (só texto e exemplos; nenhuma lógica alterada)

| Onde | Divergência | Correção |
|---|---|---|
| `docs/http.md` | `/planejar` citava `metodo` e `setor`; o controlador lê só `quadrados` e `quadrado` e usa sempre a referência histórica; o retrospecto não lê `metodo` e abre na 45ª semana já revelada | linhas das rotas reescritas |
| `docs/guia-de-uso.md` | retrospecto sempre oculto; botão "Revelar o que foi registrado" | primeira abertura revelada; rótulo atual "Revelar o que aconteceu" |
| `docs/guia-de-uso.md`, `docs/http.md` | resumo da lista de multas chamado "pagamento informado" | a tela diz "pagas" (anotadas como pagas aqui) |
| `docs/operacao.md`, `.env.example` | `INFOSIGA_URL` (lido por `urban:update`) não aparecia | linha acrescentada (no `.env.example`, comentada, com o padrão do código) |
| `.env.example` | comentário dizia 16 equipamentos no perfil compacto | 17 (radar portátil acrescentado em 24/10; `DemoProfileTest` confere 17) |
| `README.md` | "278 testes, 1842 asserções" | última execução registrada: 283/1914 em 24/10/2026, com data |
| `README.md` | "Fora do escopo: modelos 3D, heatmap" | o 3D existe como decoração da landing e há mapa de calor em Planejar; texto ajustado |
| `README.md` | vídeo pendente; endereço público só o "pretendido" | vídeo está na landing; endereços explicados sem afirmar publicação verificada |
| `video/README.md` | dizia que o MP4 final não tinha sido gerado | registra que o vídeo já está na landing |
| `landing/README.md` | padrão de `video.src` e `video.capa` = `null` | valores atuais de `config.php` |
| `scripts/documentacao/*` | o extrator PHP herdava a visibilidade de parâmetros promovidos; o gerador ancorava nomes curtos em qualquer título | corrigidos (ferramenta da documentação) |

### Registradas, não corrigidas

| Onde | Divergência | Por que não foi mudada |
|---|---|---|
| `OperationsController::back` | aceita `voltar=/central`, mas não existe rota `/central` (a Visão geral é `/`); nenhum template envia esse valor | é código; a documentação descreve o comportamento real e não altera lógica |
| `docs/adr/0013-perfil-compacto-de-demonstracao.md` | título "23 recursos"; desde 24/10 são 17 + 7 (o próprio texto da ADR registra a mudança) | ADR é histórica e não é reescrita |
| `docs/verificacao.md` | nenhum registro de execução da suíte depois da auditoria de 25/10 (`35de711`), que acrescentou testes | não foi possível rodar a suíte aqui (ver abaixo) |

### Pendências

1. **Rodar a suíte completa** (`docker compose exec app vendor/bin/phpunit`) e registrar o resultado datado em
   [verificação](verificacao.md): o ambiente desta revisão não tinha o Docker rodando e o PHP local não tem `mbstring`,
   então nem a suíte de unidade pôde ser executada.
2. Conferir os resultados da CI remota (GitHub/GitLab) — não consultados.
3. Conferir o endereço publicado (landing e aplicação) — não consultado; nenhuma afirmação de publicação foi feita.
4. Decidir se `video/` (capturas ~80 MB, narração 30 MB) entra no repositório; a referência já descreve o código.
5. Auditoria formal de acessibilidade e teste com leitor de tela — não realizados (já listados no README).
6. Nenhum commit foi feito: todos os arquivos desta documentação estão na árvore de trabalho.

## Verificações executadas nesta documentação (26/10/2026)

| Verificação | Resultado |
|---|---|
| `node scripts/documentacao/inventario.mjs --catalogo` | 1867 símbolos, 1867 documentados |
| `node scripts/documentacao/inventario.mjs --verificar` | OK: 1867 símbolos documentados, 123 pastas cobertas, links e âncoras de 177 arquivos Markdown conferidos |
| `php -l scripts/documentacao/simbolos-php.php` | sem erros de sintaxe |
| Leitura de capturas usadas no README | conferidas visualmente (`sistema-visao-geral-1280.webp`, `planejar-1280.webp`) |
| `vendor/bin/phpunit --testsuite unidade` (PHP local 8.5.9) | **não executou**: falta a extensão `mbstring` |
| `docker info` | daemon indisponível; nada rodou em contêiner |

Não foram executados: migrações, carga de demonstração, simulador, importação ou atualização urbana, scripts de
verificação da interface, captura ou render de vídeo — por regra desta tarefa e porque escreveriam dados.
