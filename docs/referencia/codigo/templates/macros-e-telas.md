# Templates Twig: macros e telas

<!-- fontes: templates/_ui.twig templates/_ajuda.twig templates/_autoria.twig templates/central.twig templates/fines/_fine.twig templates/planning/index.twig -->

As telas são renderizadas no servidor pelo Twig ([`View`](../http/aplicacao-web.md#view)) com escape automático; todas
herdam `layout.twig`. Esta página documenta as **macros** (as "funções" dos templates) e, ao fim, o mapa
tela → template → controlador. Variáveis comuns a todo template: `app.base`, `app.path`, `app.user`, `app.flash`,
`app.csrf`, `app.map`, `app.poll_seconds`, `app.sim_seconds`. Estrutura das pastas: [`templates/README.md`](../../../../templates/README.md).

## `_ui.twig`

Macros de interface reutilizadas em todas as telas. Todo valor é escapado; nenhuma macro usa `|raw` com dado externo.

| Macro | Contrato |
|---|---|
| `_ui.icon` | `icon(id, label = null, cls = 'icon')` — ícone do sprite; com `label`, `role="img"`, senão `aria-hidden`. |
| `_ui.status` | `status(code, label, glyph = null)` — selo `st st--CODE` com símbolo + texto (cor nunca é o único indicador). |
| `_ui.equipment_status` | `equipment_status(code)` — selo com rótulo e símbolo de `EquipmentStatus`. |
| `_ui.agent_status` | `agent_status(code)` — idem para `AgentStatus`. |
| `_ui.freshness` | `freshness(code, at, what = 'Sinal')` — "Sinal atual · 26/10 14:05", "… atrasado · recebido em…", "Sem informação recente (último em …) — não indica falha" ou "não se aplica". |
| `_ui.order_advice` | `order_advice(o, href, today, attention, windowEnd)` — revisão recomendada × ordem já existente: "Já existe a ordem #N …; use-a em vez de agendar outra inspeção" ou, se a ordem `ALTA` estiver marcada para depois da janela de 7 dias, sugere iniciá-la antes da data. |
| `_ui.origin_real` / `_ui.origin_demo` | selos de origem dos dados: "◆ Real agregado · Infosiga-SP" e "◇ Fictício". |
| `_ui.help` | `help(id, title, body)` — ajuda contextual "?": `<details data-popover data-help>` com `<summary aria-label="Ajuda sobre …" aria-describedby>` e a caixinha `role="tooltip"` **fora** do `<details>` (o Chrome não lê como descrição o texto de um `<details>` fechado). Sem script, o `<details>` abre pelo CSS. |
| `_ui.field_error` | `field_error(errors, name)` — mensagem de erro do campo (`id="err-<campo>"`). |
| `_ui.input` | `input(name, label, values, errors, opts = {})` — campo com cabeçalho comum (rótulo, `*` ou "(opcional)", "?" opcional) e corpo (controle + erro). `opts`: `type` (inclui `textarea`), `max`, `required`, `mark` (marca obrigatório sem `required`, quando depende de outra escolha), `placeholder`, `inputmode`, `autocomplete`, `readonly`, `class`, `fieldName`, `help_tip`. Liga `aria-invalid` e `aria-describedby` (erro e ajuda). |
| `_ui.select` | `select(name, label, options, selected, errors, opts = {})` — idem; "(opcional)" só com `opts.empty` (sem opção vazia o select sempre envia valor); `dataAttr`. |
| `_ui.error_summary` | `error_summary(errors, labels, specPrefix = 's-')` — resumo no topo ("Corrija os N campos abaixo. O que você digitou foi mantido.") com links para cada campo (`specs.*` → `#s-<TIPO>-<campo>`); recebe foco pelo `forms.js`. |
| `_ui.pagination` | `pagination(page, pages, base_query, path)` — "Página anterior / Próxima página" preservando os filtros. |
| `_ui.csrf` | `csrf(token)` — campo oculto `_csrf`. |

A estrutura "cabeçalho comum + explicação no '?'" foi adotada em 24/10/2026 para alinhar os controles
([evidência](../../../evidencias/2026-10-24-labels-e-ajuda.md)); `tests/Unit/HelpTipTest.php` garante que `input`/`select`
sem `help_tip` geram o mesmo HTML de antes.

## `_ajuda.twig`

**Todos os textos da ajuda "?"** num lugar só. Cada macro devolve o corpo da caixinha: uma frase de "para que serve" e,
quando ajuda, exemplos de Marília. Rótulos, transições e características vêm do domínio (`EquipmentStatus`,
`AgentRole`, `AgentStatus`, `EquipmentCatalog`, `FineRules`): aqui fica só a explicação. Usa a função
`enum_cases()` do Twig para listar situações e funções.

| Macro | Onde aparece / o que explica |
|---|---|
| `_ajuda.ex` | `ex(items)` — linha "Exemplos: …" com `<code>`. |
| `_ajuda.address_ref` | número ou referência ("181", "oposto ao número 181", "KM 203", "S/N", "em frente ao Mercado do Tio"). |
| `_ajuda.cep` | 8 números; a rua é preenchida sozinha. |
| `_ajuda.street` | rua sem o número. |
| `_ajuda.region` | bairro vem do ponto marcado. |
| `_ajuda.map_point` | `map_point(what = 'o local')` — o ponto guarda as coordenadas; não se digitam números. |
| `_ajuda.location_kind` | local de instalação × planejado × base, e a restrição pela situação. |
| `_ajuda.equipment_type` | `equipment_type(types)` — descrição de cada tipo do catálogo. |
| `_ajuda.equipment_status` | uma linha por situação, com o efeito na disponibilidade. |
| `_ajuda.agency` · `_ajuda.serial` · `_ajuda.asset_tag` · `_ajuda.maint_interval` · `_ajuda.installed_on` | órgão, série do fabricante (única por marca), patrimônio (único), periodicidade 7–730 dias (concluir agenda a próxima), data de instalação/entrada em operação. |
| `_ajuda.spec` | `spec(f)` — característica do catálogo: `tip`, `help`, uma linha por opção (`optionTips`) e a faixa aceita. |
| `_ajuda.status_change` | `status_change(status)` — só as transições possíveis a partir da situação atual e o efeito de cada uma. |
| `_ajuda.reason` | por que o motivo é obrigatório (histórico). |
| `_ajuda.maint_kind` · `_ajuda.maint_priority` · `_ajuda.maint_due` · `_ajuda.maint_result` | corretiva × preventiva; imediata × programada (vencida vira urgente); data programada; resultado da conclusão (concluir não prova que voltou a funcionar). |
| `_ajuda.stolen_decision` | `stolen_decision(mobile)` — repor (não instalado, mesmo ponto/base) ou dar baixa (desativado). |
| `_ajuda.agent_role` · `_ajuda.agent_status` · `_ajuda.agent_status_change` | funções; situações (em turno ou não); sair de turno faz o alerta "deixar de se aplicar" sem indicar que a comunicação voltou. |
| `_ajuda.ack_note` · `_ajuda.assignee` · `_ajuda.next_check` · `_ajuda.followup_note` · `_ajuda.agent_reason` | acompanhamento do alerta de agente: nota da ciência, responsável (contas ativas), próxima conferência (até 7 dias), relato (contato confirmado não atualiza a localização), motivo da situação. |
| `_ajuda.call_sign` · `_ajuda.base_name` | nome de exibição fictício; base de trabalho × posição atual. |
| `_ajuda.fine_record` · `_ajuda.fine_source` · `_ajuda.occurred_at` | rascunho/registrado/retificação; origem; data da ocorrência (`occurred_at(minFact = null)`: na edição, mostra o limite dos fatos já anotados). |
| `_ajuda.infraction_kind` · `_ajuda.fine_agency` · `_ajuda.legal_reference` · `_ajuda.ait` · `_ajuda.plate` · `_ajuda.renavam` · `_ajuda.missing_reason` · `_ajuda.vehicle_species` | tipo de infração (não é o catálogo nacional); órgão autuador; enquadramento transcrito; AIT × código interno; placa (antiga ou Mercosul, obrigatória para radar); RENAVAM com 11 dígitos e zeros; `missing_reason(what, examples)` para AIT/RENAVAM ausentes; espécie. |
| `_ajuda.fine_description` · `_ajuda.fine_notes` · `_ajuda.fine_radar` · `_ajuda.fine_operator` · `_ajuda.fine_agent` · `_ajuda.evidence_reference` · `_ajuda.source_note` · `_ajuda.speeds` | descrição (5–240, sugerida); relato; radar (arquivados no fim); operador do portátil; agente da constatação (não é a conta); referência da evidência (texto, não arquivo); observação sobre a origem; regulamentada × medida × considerada. |
| `_ajuda.fine_stage` · `_ajuda.fine_agent_filter` · `_ajuda.fine_documents` | etapas; filtro de agente (constatou ou operou); documentação na ocorrência (o sistema não consulta o Detran). |
| `_ajuda.check_effective` · `_ajuda.source_reference` · `_ajuda.pending_items` · `_ajuda.check_reason` · `_ajuda.doc_review` · `_ajuda.review_on` · `_ajuda.progress_reason` | conferência documental e lembrete (vale a última anotada; não é prazo legal); motivo da correção do andamento. |
| `_ajuda.attention` · `_ajuda.kpi_operational` | "precisa de atenção"; resumo "operacionais / em serviço · N com sinal" (sinal nos últimos 10 min). |
| `_ajuda.score` · `_ajuda.precision` · `_ajuda.recall` · `_ajuda.f1` | prioridade sugerida (não é chance de quebrar) e métricas do retrospecto. |
| `_ajuda.expected` · `_ajuda.method` · `_ajuda.grid` · `_ajuda.data_through` · `_ajuda.hit_rate` · `_ajuda.mae` · `_ajuda.justification` · `_ajuda.support_point` | Planejar: registros esperados (não é chance de acidente); método = média simples (o modelo testado fica fora da tela); quadrados de 2 km; dados até (dois meses mais recentes ficam de fora); acerto (acaso = 25%); erro médio; justificativa; ponto de apoio (o centro do quadrado não é local seguro de parada). |
| `_ajuda.username` · `_ajuda.account_role` | Contas: usuário (3–40, sem espaço) e papel. |

## `_autoria.twig`

Personagens 3D do cartão de autoria (página Sobre), em CSS 3D; decorativos (`aria-hidden`). A pose de repouso vem do
CSS e o GSAP só gira e anima por cima ([`pages/sobre.js`](../frontend/paginas.md#pagessobrejs-página-sobre)).

| Macro | Contrato |
|---|---|
| `_autoria.caixa` | `caixa(cls, olhos = false)` — caixa 3D de seis faces (`.vx`), com olhos opcionais. |
| `_autoria.mascote` | `mascote()` — mascote do Claude Code em blocos (corpo, braços, quatro pernas, sombra). |
| `_autoria.codex` | `codex()` — símbolo do Codex: nuvem extrudada em sete camadas, face em degradê e ">_". |
| `_autoria.defs` | `defs()` — desenhos compartilhados (`#pf-nuvem`, `#pf-nuvem-degrade`) fora do cartão, para não duplicar ids. |

As marcas são da Anthropic e da OpenAI; o desenho é do projeto e o uso é nominativo
([licenças](../../../licencas-e-fontes.md)).

## `central.twig`

| Macro | Contrato |
|---|---|
| `central.kpis` | `kpis(k, snapshot, cls)` — os três resumos da Visão geral: "operacionais / em serviço · N com sinal" (com "?"), "precisam de atenção" (link que liga o filtro; sem JS, `?atencao=1`) e "manutenções urgentes" (link para `/manutencao#urgentes`). Usado duas vezes (celular e desktop) com classes diferentes. Os números têm `x-text` para o Alpine atualizar. |

O resto do template — barra de sincronização, filtros, lista renderizada no servidor (`data-ssr`), mapa, legenda, ficha
resumida e o JSON `#snapshot-data` — está descrito no [guia da Visão geral](../../../guias/visao-geral.md) e em
[`pages/central.js`](../frontend/central.md).

## `fines/_fine.twig`

| Macro | Contrato |
|---|---|
| `fines/_fine.state` | `state(code)` — selo Rascunho (✎), Registrado (✓), Registro cancelado (–). |
| `fines/_fine.doc` | `doc(code, review_on = null, today = null)` — selo da documentação; com lembrete vencido (`review_on ≤ hoje`), "↻ Documentos: precisam ser conferidos de novo". |
| `fines/_fine.money` | `money(cents)` — "R$ 1.234,56" ou "não informado". |
| `fines/_fine.money_input` | `money_input(cents)` — valor para o campo de formulário. |
| `fines/_fine.diff_value` | `diff_value(v)` — valor legível no "antes e depois" do histórico (listas em JSON; vazio vira "—"). |

## `planning/index.twig`

| Macro | Contrato |
|---|---|
| `planning/index.place` | `place(c)` — bairros de referência do quadrado ou "Perto de …". |

## Mapa das telas

| Tela | Template | Controlador → método |
|---|---|---|
| Login | `auth/login.twig` | `AuthController::loginForm`/`login` |
| Erro (401…500) | `error.twig` | `WebApp::renderError` |
| Visão geral | `central.twig` | `CentralController::page` |
| Equipamentos: lista, ficha, formulário | `equipment/index.twig`, `show.twig`, `form.twig` | `EquipmentController` |
| Agentes: lista, ficha, formulário | `agents/index.twig`, `show.twig`, `form.twig` | `AgentController` |
| Multas: lista, ficha, formulário, perguntas frequentes | `fines/index.twig`, `show.twig`, `form.twig`, `_faq.twig` | `FineController` |
| Manutenção | `maintenance/index.twig` | `OperationsController::maintenance` |
| Revisões recomendadas | `analysis/index.twig` | `OperationsController::analysis` |
| Planejar reforço e retrospecto | `planning/index.twig`, `replay.twig` | `PlanningController::page`/`replay` |
| Sobre | `about.twig` (+ `_autoria.twig`) | `PageController::about` |
| Contas | `accounts/index.twig` | `AuthController::accounts` |
| Moldura (menu, barra, aviso de demonstração, avisos) | `layout.twig` | todos |

## Relacionados

[Guias das telas](../../../guias/README.md) · [Frontend](../frontend/entrada-e-bibliotecas.md) · [Interface para iniciantes (ADR-0008)](../../../adr/0008-interface-para-iniciantes.md)
