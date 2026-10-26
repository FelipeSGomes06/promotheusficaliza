# Controladores

<!-- fontes: src/Http/Controller/Controller.php src/Http/Controller/CentralController.php src/Http/Controller/PageController.php src/Http/Controller/AddressController.php src/Http/Controller/AuthController.php src/Http/Controller/EquipmentController.php src/Http/Controller/AgentController.php src/Http/Controller/OperationsController.php src/Http/Controller/FineController.php src/Http/Controller/PlanningController.php -->

Os controladores traduzem HTTP em chamadas aos serviços e escolhem a resposta: página renderizada, redirecionamento
**303** com aviso (padrão POST → 303 → GET, funciona sem JavaScript) ou JSON. Não contêm regra de negócio. Cada método
público é uma rota (tabela completa, acesso e contratos de campo em [http.md](../../../http.md)); a permissão é aplicada
pelo grupo em [`WebApp`](aplicacao-web.md#webappcreate).

Padrão de erro dos formulários: `ValidationFailed` → **422** com o formulário reapresentado e tudo o que foi digitado;
`RuleViolation` → **409** (no formulário) ou aviso vermelho (nas ações da ficha); `ConcurrencyConflict` → tela de
conflito com "Salvo agora × Sua versão" ou aviso.

## `Controller` (base abstrata)

| Método | Contrato |
|---|---|
| `Controller::__construct` | `(Services $services, View $view)`. |
| `Controller::render` | `protected (request, response, string $template, array $data = [], int $status = 200)` — delega à `View`. |
| `Controller::redirect` | `protected (response, string $path, int $status = 303)` — sempre sob o prefixo; nunca URL externa. |
| `Controller::json` | `protected (response, array $data, int $status = 200)` — JSON com `JSON_HEX_TAG/AMP` e `Cache-Control: no-store`. |
| `Controller::session` | `protected (request): Session`. |
| `Controller::body` | `protected (request): array` — corpo lido ou `[]`. |
| `Controller::userId` | `protected (request): int` — conta da sessão; sem conta → `HttpError(401)`. |
| `Controller::id` | `protected static (array $args, string $key = 'id'): int` — inteiro ≥ 1 ou `HttpError(404)`. |
| `Controller::pageNumber` | `protected static (array $query): int` — `pagina` entre 1 e 10000, padrão 1. |

## `CentralController`

| Método | Rota | Contrato |
|---|---|---|
| `CentralController::page` | `GET /` | Renderiza `central.twig` com o instantâneo ([`CentralQuery::snapshot`](../application/central.md#centralquerysnapshot)), os filtros aplicados, `view` (`mapa` ou `lista` por `?visao=lista`) e as opções de filtro. O instantâneo vai também como JSON embutido (`#snapshot-data`) para o componente Alpine; sem JavaScript, a página já é útil. `?selecionar=EQUIPAMENTO:<id>` é lido pelo JavaScript. |
| `CentralController::snapshot` | `GET /api/central` | JSON do instantâneo com os mesmos filtros (`no-store`). |
| `CentralController::filterOptions` | — | `static (array $regions, array $agencies): array` — categorias (tipos de equipamento + funções de agente), situações (dos dois grupos), bairros e órgãos para os menus da central e das listas. |

## `PageController`

| Método | Rota | Contrato |
|---|---|---|
| `PageController::about` | `GET /sobre` | `about.twig` com os tipos do catálogo. |
| `PageController::health` | `GET /saude` (aberta) | `SELECT 1 FROM RDB$DATABASE`: `{"status":"ok"}` 200 ou `{"status":"indisponivel"}` 503. Sem versões, caminhos ou dados. |

## `AddressController`

Endereço nos formulários ([ADR-0021](../../../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)): o navegador só fala
com este servidor; daqui sai só o CEP ou o nome da rua, **validados antes** de qualquer chamada. Toda resposta tem as
mesmas chaves e uma mensagem pronta para a tela.

| Método | Rota | Contrato |
|---|---|---|
| `AddressController::cep` | `GET /api/cep/{cep}` | CEP inválido → 422 `INVALIDO` sem chamada externa; provedor fora → 503 `INDISPONIVEL` (motivo só no log); inexistente → 404 `NAO_ENCONTRADO`; outra cidade → 200 `OUTRA_CIDADE` (a mensagem cita a cidade); CEP geral sem rua → 200 `SEM_RUA`; achado → 200 `ENCONTRADO` com `rua` e `bairro`. Corpo `{cep, situacao, rua, bairro, mensagem}`. |
| `AddressController::locate` | `GET /api/localizar?rua=&cep=&numero=` | Sem rua nem CEP ou algum inválido → 422 `INVALIDO`; senão [`AddressLocator::locate`](../application/endereco.md#addresslocatorlocate): `ENCONTRADO` 200, `SEM_RUA` 200, `NAO_ENCONTRADO` 404, `INDISPONIVEL` 503. Corpo `{situacao, lat, lng, rua, caixa, mensagem}`. |
| `AddressController::answer` | — | `private` — monta o JSON do CEP. |

## `AuthController`

| Método | Rota | Contrato |
|---|---|---|
| `AuthController::loginForm` | `GET /entrar` | Já logado → 303 para `/`. Senão `auth/login.twig` com `needs_csrf` (a sessão começa aqui) e o `voltar` validado. |
| `AuthController::login` | `POST /entrar` | `AuthService::attempt(usuario, senha, REMOTE_ADDR)`; falha → 401 com o login e a mensagem única (usuário mantido, até 40). Sucesso: **regenera a sessão**, grava `user_id`, `auth_at`, `credential_no`, `last_activity`, **token CSRF novo**, aviso "Olá, …" e 303 para o `voltar`. |
| `AuthController::logout` | `POST /sair` | Destrói a sessão e vai ao login. |
| `AuthController::accounts` | `GET /contas` | `accounts/index.twig` com as contas. |
| `AuthController::createAccount` | `POST /contas` | Cria; erro → 422 com a digitação; sucesso → **201** com a senha gerada na própria resposta (uma única vez). |
| `AuthController::toggleAccount` | `POST /contas/{id}/situacao` | `active` = `1` reativa, outro valor desativa; regra violada → aviso vermelho; 303 para `/contas`. |
| `AuthController::resetPassword` | `POST /contas/{id}/senha` | Senha nova da mesma conta, mostrada uma vez (200); própria conta ou inexistente → aviso e 303. |
| `AuthController::safeBack` | — | `private` — destino pós-login só se for caminho interno do prefixo (`^/(?!/)[A-Za-z0-9/_\-?=&%.]*$` sem `..`); senão `/`. Evita redirecionamento aberto. |
| `AuthController::clientIp` | — | `private` — `REMOTE_ADDR` (atrás de proxy, configurar o repasse do IP real; ver [segurança](../../../seguranca.md#riscos-restantes)). |

## `EquipmentController`

| Método | Rota | Contrato |
|---|---|---|
| `EquipmentController::index` | `GET /equipamentos` | Lista paginada (20) com `Filters`, `q` (≤ 60), `ordem`, `arquivados=1`; atualidade do sinal calculada por linha. |
| `EquipmentController::show` | `GET /equipamentos/{id}` | Ficha: equipamento, atualidade do sinal e da posição, tipo, situação e transições, características legíveis, histórico (40), ordens (pendentes + 30 encerradas) e total de encerradas, alertas, revisão recomendada mais recente, hoje local. 404 se não existe. |
| `EquipmentController::createForm` | `GET /equipamentos/novo` | Formulário com `?tipo=` (padrão `RADAR`), situação `NAO_INSTALADO`, local `PLANEJADO`. |
| `EquipmentController::store` | `POST /equipamentos` | `EquipmentService::create`; 422/409 reapresentam; sucesso → 303 para a ficha com "Equipamento cadastrado.". |
| `EquipmentController::editForm` | `GET /equipamentos/{id}/editar` | Arquivado → `HttpError(409)`; carrega `version`. |
| `EquipmentController::update` | `POST /equipamentos/{id}` | 422/409 reapresentam; `ConcurrencyConflict` → [`conflict`](#equipmentcontroller); sucesso → 303 "Alterações salvas.". |
| `EquipmentController::changeStatus` | `POST /equipamentos/{id}/situacao` | `status`, `note`; resultado como aviso e 303 para a ficha. |
| `EquipmentController::archive` | `POST /equipamentos/{id}/arquivar` | `reason`, `cancelar_agendadas=1`; o aviso lista as ordens canceladas. |
| `EquipmentController::simulateFailure` | `POST /equipamentos/{id}/simular-falha` | Demonstração: evento `FALHA` pelo [`EventIngestor`](../application/ingestao.md#eventingestorequipmentstatus) com UID `demo-<hex>`, origem `SIMULADOR` e autor; reconcilia alertas. `APLICADO` → sucesso; senão "Evento registrado só no histórico…". Com `Accept: application/json` (central) → `{ok, message}` 200/409. |
| `EquipmentController::conflict` | — | `private` — conflito de edição: arquivado no meio → aviso e ficha; senão o formulário com a digitação, a **versão atual** (salvar de novo = manter a própria versão), a tabela de diferenças (`pendingChanges`) e a última `ALTERACAO`/`SITUACAO` registrada. |
| `EquipmentController::form` | — | `private` — renderiza `equipment/form.twig` com tipos, situações, órgãos e bairros. |

## `AgentController`

| Método | Rota | Contrato |
|---|---|---|
| `AgentController::index` | `GET /agentes` | Lista paginada; atualidade da posição (só em turno). |
| `AgentController::show` | `GET /agentes/{id}` | [`page`](#agentcontroller). |
| `AgentController::acknowledgeAlert` | `POST /agentes/{id}/alertas/{alert}/ciencia` | Ciência com nota opcional. Se outra pessoa já registrou a ciência, a ficha volta (409) com a nota levada ao formulário de **providência**. |
| `AgentController::assignAlert` | `POST /agentes/{id}/alertas/{alert}/responsavel` | `assignee`, `version`; mensagem "atribuído/reatribuído a <nome>". |
| `AgentController::recordFollowUp` | `POST /agentes/{id}/alertas/{alert}/providencias` | `action`, `note`, `next_check`, `version`. |
| `AgentController::simulatePosition` | `POST /agentes/{id}/simular-posicao` | Demonstração: posição fictícia até ~200 m da base (±0,002°) pelo `EventIngestor` com histórico ("recebimento simulado pela demonstração"), reconciliação. Mensagem diz se o alerta foi encerrado por posição recente (é um recebimento pontual: se o rádio simulado seguir sem transmitir, um alerta novo abre depois de 30 min) ou que fora de turno a posição não aparece no mapa. Arquivado → aviso. |
| `AgentController::createForm` | `GET /agentes/novo` | Função `TRANSITO`, situação `FORA_DE_SERVICO`. |
| `AgentController::store` | `POST /agentes` | Como em equipamentos. |
| `AgentController::editForm` | `GET /agentes/{id}/editar` | Arquivado → 409. |
| `AgentController::update` | `POST /agentes/{id}` | Conflito de versão → formulário com a digitação e a versão atual ("Confira a ficha atual antes de salvar de novo"). |
| `AgentController::changeStatus` | `POST /agentes/{id}/situacao` | `status`, `note` (obrigatório); erro → ficha com o formulário aberto (422/409). Se a mudança encerrou o alerta, o aviso diz que ele "deixou de se aplicar com a saída de turno; isso não indica que a comunicação voltou". |
| `AgentController::archive` | `POST /agentes/{id}/arquivar` | `reason`. |
| `AgentController::alertAction` | — | `private` — confere que o alerta é **deste agente** (senão 404), executa a ação e trata 422/409 reapresentando a ficha com o formulário aberto e o digitado; sucesso → 303 para `#acompanhamento`. |
| `AgentController::page` | — | `private` — ficha: próxima ação ([`AlertFollowUp::nextAction`](../domain/indicadores-e-alertas.md#alertfollowupnextaction)), alertas pendentes (sem limite) e acompanhamento, encerrados (20) e total, contas atribuíveis, missão vigente, histórico (com `readableChange`), `now_local` e `max_local` (+7 dias) para o campo de próxima conferência. |
| `AgentController::form` | — | `private` — `agents/form.twig`. |
| `AgentController::readableChange` | — | `private static` — refaz o texto de alterações antigas com rótulos ([`AgentService::changeSummary`](../application/agentes.md#agentservicechangesummary)). |

## `OperationsController`

| Método | Rota | Contrato |
|---|---|---|
| `OperationsController::maintenance` | `GET /manutencao` | Fila por andamento e urgência: `EM_EXECUCAO`, `URGENTE` (imediata ou vencida), `PROXIMA`, `FUTURA`; cada ordem com urgência e `start_blocker`; mapa equipamento → ordem em execução ("Aguardando: a ordem #N está em execução"); encerradas recentes (20). |
| `OperationsController::open` | `POST /equipamentos/{id}/manutencoes` | Abre ordem; volta a `#manutencao` da ficha. |
| `OperationsController::start` | `POST /manutencoes/{id}/iniciar` | Inicia; volta ao `voltar`. |
| `OperationsController::complete` | `POST /manutencoes/{id}/concluir` | `resolution`, `result_status`. |
| `OperationsController::cancel` | `POST /manutencoes/{id}/cancelar` | `reason`. |
| `OperationsController::acknowledge` | `POST /alertas/{id}/reconhecer` | Ciência; com `Accept: application/json` → `{ok, message}` 200/409; senão aviso e 303 para o `voltar`. |
| `OperationsController::analysis` | `GET /antecipacao` | Última execução e resultados; ordem que já cuida de cada equipamento (a em execução ou a primeira da fila), lida agora. |
| `OperationsController::recalculate` | `POST /antecipacao/recalcular` | `AnalysisService::run(conta)`; aviso "Análise recalculada (execução #N)". |
| `OperationsController::back` | — | `private` — `voltar` só se casar `^/(manutencao|central|equipamentos/\d+)(#[a-z-]+)?$`; senão `/manutencao`. |
| `OperationsController::attempt` | — | `private` — executa a ação, converte erros em aviso vermelho e redireciona (303). |

Divergência observada: `back()` aceita `/central`, mas não existe rota `/central` (a central é `/`). Nenhum template
envia `voltar=/central`; a ciência pela central usa JSON e não redireciona. Registrado em
[cobertura](../../../cobertura-documentacao.md#divergências-e-pendências-encontradas).

## `FineController`

| Método | Rota | Contrato |
|---|---|---|
| `FineController::index` | `GET /multas` | `FineFilters::fromQuery`; [`FineQuery::list`](../application/multas.md#finequery); **422** se algum filtro de data foi recusado (o resto é aplicado). |
| `FineController::show` | `GET /multas/{id}` | [`page`](#finecontroller). |
| `FineController::createForm` | `GET /multas/novo` | `?radar=<id>` (se for radar) ou `?agente=<id>` (se for de trânsito) **só pré-selecionam** a origem e o órgão; nada é gravado. Emite `command_uid` novo. |
| `FineController::store` | `POST /multas` | Cria rascunho; 422 reapresenta; conflito → formulário com chave nova; sucesso → 303 "Rascunho salvo. Complete o que faltar e registre após a conferência.". |
| `FineController::editForm` | `GET /multas/{id}/editar` | Cancelado → 409; carrega `FineService::formValues`, versão e chave nova. |
| `FineController::update` | `POST /multas/{id}` | 422 reapresenta; `RuleViolation` → aviso na ficha; conflito → formulário com a versão atual, chave nova e a tabela de diferenças (cancelado no meio → aviso na ficha). Sucesso: "Retificação salva; o original continua no histórico." ou "Rascunho atualizado.". |
| `FineController::register` | `POST /multas/{id}/registrar` | "Registrado após conferência. Isto não emite um auto oficial.". |
| `FineController::addCheck` | `POST /multas/{id}/conferencias` | "Conferência documental registrada (informação manual).". |
| `FineController::progress` | `POST /multas/{id}/andamento` | Avisos de sequência incomum viram avisos amarelos ("Aviso: …"). |
| `FineController::cancel` | `POST /multas/{id}/cancelar` | "Registro cancelado internamente…". |
| `FineController::action` | — | `private` — ações da ficha: 422/409 reapresentam a ficha com o formulário aberto e o digitado (no conflito, com chave nova); sucesso → 303 com a mensagem e os avisos. |
| `FineController::page` | — | `private` — ficha: [`FineQuery::detail`](../application/multas.md#finequery), página do histórico (`eventos_pagina`), chave de comando, filtros de volta **reconstruídos** de `voltar` (nunca uma URL recebida) e `now_local`. |
| `FineController::form` | — | `private` — `fines/form.twig` com órgãos, bairros, origens e a tabela de diferenças do conflito. |
| `FineController::vocabulary` | — | `private static` — listas fechadas e rótulos para os templates (tipos, origens, estados, etapas, pagamentos, contestações, UFs, documentação, fontes, escopos, lembretes, ordenações, regra da velocidade). |

## `PlanningController`

| Método | Rota | Contrato |
|---|---|---|
| `PlanningController::page` | `GET /planejar` | Delega a `show` com a query. |
| `PlanningController::show` | — | `private` — sempre `PlanningService::week('baseline')` (o Poisson experimental fica fora da tela); `quadrados` (1–4, padrão 4) limita os prioritários; `quadrado` (posição 1–N, padrão 1) escolhe o alvo; sem quadrados importados, o alvo é o setor de maior estimativa. Monta os candidatos para o alvo, quais quadrados já têm agente designado (missões confirmadas da mesma execução) e o JSON do mapa (`#plan-data`): grade inteira com rótulos e os prioritários clicáveis (`href` com `#agente`). |
| `PlanningController::replay` | `GET /planejar/retrospecto` | `?semana=AAAA-MM-DD`; a **primeira abertura** (sem semana) já vem **revelada**; navegando por semana, os registros só entram com `revelar=1`. Método sempre `baseline`. |
| `PlanningController::confirm` | `POST /planejar/missoes` | `PlanningService::confirm`; 422 → a tela volta no passo 3 com o erro junto do campo e o digitado (status 422); regra/conflito → aviso e 303 para o quadrado; sucesso → 303 para `#missoes` com "Missão fictícia #N confirmada. Nenhuma mensagem foi enviada". |
| `PlanningController::complete` | `POST /planejar/missoes/{id}/concluir` | `reason`. |
| `PlanningController::cancel` | `POST /planejar/missoes/{id}/cancelar` | `reason`. |
| `PlanningController::close` | — | `private` — chama `PlanningService::close` e volta a `#missoes` com a mensagem. |
| `PlanningController::param` | — | `private static` — parâmetro texto ou `null`. |
| `PlanningController::intParam` | — | `private static` — inteiro em `[1, max]` ou `null`. |
| `PlanningController::scalar` | — | `private static` — escalar do corpo como texto. |

Divergência em relação a versões anteriores de [http.md](../../../http.md): a rota `/planejar` não lê `metodo` nem
`setor` (lê `quadrado` e `quadrados`), e o retrospecto ignora `metodo`. O contrato foi atualizado em http.md.

## Relacionados

[Aplicação web](aplicacao-web.md) · [Middlewares](middlewares.md) · [Templates](../templates/macros-e-telas.md) ·
[`src/Http/Controller/README.md`](../../../../src/Http/Controller/README.md)
