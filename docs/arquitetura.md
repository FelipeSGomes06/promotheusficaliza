# Arquitetura e referência do código

Monólito modular em PHP 8.5: uma aplicação web, uma CLI e um banco Firebird 5 dedicado. As camadas são pastas de
`src/` com dependências num só sentido: **Http → Application → Domain**, e **Application → Infrastructure** (SQL).
O domínio não conhece HTTP, Twig nem Firebird.

```
Navegador ──HTTPS──► Apache (/promotheusfiscaliza) ──► public/index.php ──► Slim (Http)
                                                                      │
   simulador (CLI) ──► bin/fiscaliza ──► Console ─────────────────────┤
                                                                      ▼
                                              Application (casos de uso, transações)
                                                │                   │
                                         Domain (regras puras)   Infrastructure (PDO_FIREBIRD, SQL)
                                                                      │
                                                                 Firebird 5 (rede interna)
Navegador ──► provedor de tiles (somente imagens do mapa, origem liberada na CSP)
```

## Fluxos principais

**Leitura da visão geral.** `GET /` renderiza no servidor (útil sem JavaScript) com o instantâneo de `CentralQuery`;
o componente Alpine `central` ([`resources/js/pages/central.js`](../resources/js/pages/central.js)) consulta
`GET /api/central` a cada `POLL_INTERVAL_SECONDS` — é o único dono da consulta; pedido novo cancela o anterior e só a
resposta mais recente é aplicada. O instantâneo é lido numa única transação
**SNAPSHOT somente leitura**: recursos, ordens, alertas e indicadores vêm do mesmo instante e do mesmo filtro — busca
por texto e "só o que precisa de atenção" inclusive. A atualização reaproveita as linhas da lista por chave (`x-for`
com `:key`), preserva foco, seleção e posição do mapa e realça só o que mudou; só ações da pessoa movem o mapa.

**Escrita manual.** Formulário → middleware de sessão/CSRF/papel → controlador → serviço de aplicação → validação
(`Input`, esquema do tipo) → transação curta: `SELECT … WITH LOCK` quando há mudança de situação, gravação, evento de
histórico (`History`) e reconciliação de alertas → commit. Conflito de lock vira `ConcurrencyConflict` (409) pelo
timeout de instrução; edição com versão de cadastro antiga também, e a tela de conflito mostra o que mudou
([ADR-0010](adr/0010-versao-do-cadastro-separada-da-telemetria.md)). Tudo o que envolve ordens de manutenção bloqueia
primeiro o equipamento e depois a ordem ([ADR-0009](adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)).

**Simulação.** `sim:run` (contêiner `simulator`) ou `sim:tick` (cron) chama `Simulator::tick()`, que entrega
observações fictícias pelos mesmos casos de uso (`EventIngestor`, `AgentService`), grava no Firebird e reconcilia
alertas. Ciclos numerados + semente = sequência reproduzível.

**Análise preventiva.** `AnalysisService::run()` lê eventos de situação, ordens e alterações de periodicidade; em
cada corte usa só o que tinha sido recebido antes dele, a periodicidade vigente e os equipamentos que existiam e não
estavam arquivados; ajusta o limiar numa janela, avalia em outra e grava a execução.

## Referência dos módulos

### `src/Domain` — regras puras (sem banco, sem HTTP)

| Arquivo | Finalidade | Testes |
|---|---|---|
| `EquipmentStatus.php` | Situações do equipamento, transições permitidas, em serviço/indisponível, estados administrativos, glifo textual. | `DomainRulesTest` |
| `AgentStatus.php`, `AgentRole.php` | Situação e função do agente; função vira categoria de filtro `AG_<papel>`. | `InputAndSpecsTest` |
| `EquipmentType.php`, `EquipmentCatalog.php` | Tipos e esquema das características (int/bool/enum/multi/text, faixas, obrigatoriedade condicional); valida e descreve `SPECS`. | `InputAndSpecsTest`, `InventoryTest` |
| `Freshness.php` | Atualidade da observação: atual (≤10 min sinal / ≤5 min posição), atrasada, sem informação, não se aplica. | `DomainRulesTest` |
| `MaintenanceUrgency.php` | Imediata, vencida, próxima (até 7 dias), futura — comparando datas locais. | `DomainRulesTest` |
| `Indicators.php` | Fórmulas dos cartões da central e denominadores. | `DomainRulesTest`, `CentralAndAnalysisTest` |
| `AlertRules.php` | Condições que mantêm alertas abertos (furto, falha, sem comunicação, manutenção imediata/vencida, agente sem posição). | `DomainRulesTest`, `OperationsTest` |
| `AttentionModel.php` | Índice heurístico de atenção preventiva e métricas de avaliação. | `AttentionModelTest` |
| `FineRules.php`, `FineDocumentStatus.php` | Multas: vocabulário, código interno × AIT, placa (formato), campos mínimos para registrar, coerência das velocidades, motivos de "A conferir", avisos de andamento; seleção da conferência que vale (escopo, versão do sujeito, instante). | `FineRulesTest`, `FinesTest` |
| `NearestRegion.php` | Bairro de centro mais próximo de um ponto: "perto de …" do Planejar e bairro derivado da base do agente ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). | `DomainRulesTest`, `AgentBaseTest` |
| `SectorForecast.php` | Setores e métodos do planejamento por setores, ordem com desempate, métricas do retrospecto (orçamento de 1 setor) e distância em linha reta ao retângulo. | `UrbanImportTest`, `PlanningTest` |
| `Time.php`, `Clock.php` | Convenção UTC no banco / America/Sao_Paulo na tela; relógio injetável para testes e geração de histórico. | `DomainRulesTest` |
| `ValidationFailed.php`, `RuleViolation.php` | Erros de campo (422) e de regra (409). | — |

### `src/Application` — casos de uso

| Arquivo | Entradas → saídas | Observações |
|---|---|---|
| `Input.php` | dados crus → valores normalizados + erros por campo | Lê só as chaves pedidas; CEP, coordenadas na área de Marília, datas, controle de tamanho. |
| `Filters.php` | query string → filtros válidos | Descarta valores desconhecidos; decide se equipamentos/agentes entram no escopo. |
| `EquipmentService.php` | formulário → id / alteração / transição / arquivamento | Traduz restrições do Firebird em erros de campo; histórico antes/depois; recusa mudar situação ou arquivar com intervenção em execução; cancela ordens agendadas no arquivamento só com confirmação; `pendingChanges` para a tela de conflito. |
| `AgentService.php` | idem para agentes | Base ≠ posição observada. Ponto do mapa ou, sem ele, do endereço; bairro derivado do ponto ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). |
| `AddressLocator.php`, `CepLookup.php`, `Geocoder.php` | CEP → rua; rua (ou rua do CEP) → ponto | Interfaces pequenas para os provedores externos, injetadas em `Services`; falsos nos testes. |
| `MaintenanceService.php` | abrir, iniciar, concluir, cancelar ordens | Uma intervenção em execução por equipamento (serviço + índice parcial); iniciar põe em manutenção; concluir exige situação resultante; preventiva periódica agenda a próxima. |
| `EventIngestor.php` | observação (uid, instante) → APLICADO / HISTORICO / DUPLICADO | Duplicata por `EVENT_UID`; atrasado não regride; estado administrativo prevalece; posição só avança. |
| `AlertService.php` | estado atual → alertas abertos/resolvidos | Idempotente; reconhecer ≠ resolver. |
| `CentralQuery.php` | filtros → instantâneo da visão geral | Transação SNAPSHOT; título "tipo · endereço"; motivos de atenção em linguagem comum; busca sem acentos; resumo por bairro; listas de manutenção; dados de sincronização. |
| `AnalysisService.php` | histórico → execução da análise + avaliação | Separação temporal ajuste/avaliação por disponibilidade da informação; `load()` monta os índices usados por `features()`. |
| `UrbanImporter.php` | pasta de uma execução + GeoJSON + manifesto → execução gravada (ou a existente) | Valida tudo antes da transação (contrato v1, métricas recalculadas); limites de tamanho/profundidade; SHA-256 do conjunto. Só pela CLI. |
| `UrbanUpdater.php` | download mensal → contagens por quadrado e dia (12 meses); segunda-feira → execução da semana | curl só https, limite de tamanho; mesmos filtros do treino; idempotente por SHA-256 e por semana; média e Poisson em PHP com o modelo da referência. |
| `PlanningService.php` | execução importada + agentes → semana, candidatos, missão, retrospecto | Ordem pelo método escolhido sem alterar a execução; explicação pelas evidências; reserva com trava do agente + índice único; período encerrado recusado. |
| `FineService.php` | formulários de multa → rascunho, edição/retificação, registro, conferência, andamento, cancelamento | Uma transação por comando: trava multa → equipamento → agente, confere versão e estado, grava projeção + constatação + evento; chave de comando idempotente com impressão digital; snapshot da origem no registro; nova versão do sujeito quando muda a identidade ([ADR-0017](adr/0017-aba-multas.md)). |
| `FineQuery.php`, `FineFilters.php` | query string → lista + três resumos do mesmo filtro; ficha | Leitura SNAPSHOT; filtros de listas fechadas; período local inclusivo → intervalo UTC semiaberto; "voltar" reconstruído. |
| `AuthService.php` | credenciais → conta | Argon2id; vaga de tentativa reservada antes do hashing (cota atômica por usuário/IP, [ADR-0011](adr/0011-cota-de-login-atomica.md)); contas só por CLI/admin. |
| `History.php` | registro de eventos manuais/sistema | Também calcula diferença campo a campo. |

### `src/Infrastructure` — Firebird e provedores externos

| Arquivo | Finalidade |
|---|---|
| `Database.php` | Conexão PDO_FIREBIRD (UTF8, dialeto 3), `SET STATEMENT TIMEOUT`, transações (inclusive SNAPSHOT somente leitura), parâmetros posicionais, tradução de erros (`ConstraintViolation`, `ConcurrencyConflict`, `DatabaseUnavailable`) sem expor DSN/SQL. |
| `Migrator.php` | Aplica `database/migrations/*.sql` uma vez (tabela `SCHEMA_MIGRATION`) com a identidade de migração e concede ao usuário da aplicação apenas DML (somente leitura+inserção nas tabelas de histórico). |
| `ViaCepClient.php`, `NominatimGeocoder.php` | Únicas chamadas externas durante uma requisição web: curl só https, sem redirecionamento, 3 s; o Nominatim espaçado a 1 consulta/s ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). |
| `Repository/*.php` | Todo o SQL: equipamentos, agentes, manutenção, eventos, alertas, contas, metadados, referência, análise, planejamento e multas (`FineRepository`: projeção, constatações e eventos). Colunas graváveis e ordenações vêm de listas fechadas. |

### `src/Http` — entrada web

| Arquivo | Finalidade |
|---|---|
| `WebApp.php` | Monta o Slim: rotas, papéis por grupo, ordem dos middlewares, tratamento de erros com código de referência. |
| `Middleware/SecurityHeaders.php` | CSP sem `unsafe-inline`, nosniff, bloqueio de enquadramento, referência, permissões, COOP/CORP, `no-store`. |
| `Middleware/SessionContext.php` | Retoma sessão só se há cookie, expira por inatividade (30 min) e tempo absoluto (8 h), carrega conta ativa. |
| `Middleware/CsrfGuard.php` | Token de sincronização em toda escrita + verificação de `Origin`. |
| `Middleware/RequireRole.php` | Conta obrigatória em todas as rotas exceto login/logout/saúde ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)); OPERADOR/ADMIN no servidor; visitante vai ao login (GET) ou recebe 401; sessão com banco fora → 503. |
| `Session.php`, `View.php`, `HttpError.php` | Sessão própria com Path restrito; Twig com escape automático e filtros de data/porcentagem. |
| `Controller/*.php` | Central, equipamentos, agentes, endereço (`/api/cep`, `/api/localizar`), operações (manutenção, alertas, análise), planejamento por setores, autenticação/contas, páginas. |

### `src/Demo` e `src/Cli`

| Arquivo | Finalidade |
|---|---|
| `Demo/DemoScenario.php` | Dados sintéticos: órgãos, bairros, bases e os perfis **compacto** (16 equipamentos e 7 agentes, padrão) e **completo** (48 e 21) — [ADR-0013](adr/0013-perfil-compacto-de-demonstracao.md). |
| `Demo/DemoSeeder.php` | Carga + 240 dias de histórico reproduzível (falhas, corretivas, preventivas com atraso). |
| `Demo/FineScenario.php` | Cenário de multas (12 casos curados ou 40), gravado pelos casos de uso; também pelo `demo:seed-multas`, sem apagar nada. |
| `Demo/Simulator.php` | Ciclo ao vivo: sinais, falhas/recuperações, deslocamentos, duplicatas e atrasos intencionais. |
| `Cli/Console.php` | `migrate`, `demo:seed [--reset] [--perfil=compacto\|completo]`, `user:create`, `sim:tick`, `sim:run` (trava `var/simulador.lock` por ciclo), `analysis:run`, `urban:import`, `demo:seed-multas`. |
| `Services.php` | Raiz de composição: cria cada dependência uma vez; troca de identidade (aplicação × migração). |

### Apresentação

| Caminho | Finalidade |
|---|---|
| `templates/*.twig` | Layout de aplicação (trilho de navegação/gaveta, barra superior, aviso de demonstração, região de avisos), visão geral com chips de filtro e ficha sobre o mapa, inventários, fichas, formulários em etapas, manutenção, revisões, sobre, login, contas, erro. Sem JavaScript tudo continua navegável por links e formulários. |
| `resources/css/app.css` | Tailwind 4: tokens (`@theme`, paleta fechada), reset, componentes; ajustes do Leaflet fora de camada; preferência de movimento ([ADR-0012](adr/0012-identidade-motion-e-build-de-assets.md)). |
| `resources/js/app.js` | Entrada única: Alpine (build CSP), GSAP + Flip, gaveta do menu, botão de animações, avisos, formulários (carregando/sem envio duplicado), painéis sobrepostos, seletor de local; carrega o módulo da página. |
| `resources/js/pages/central.js` | Componente `central`: filtros, lista reativa (Flip em mudança de filtro), mapa e agrupamento de marcadores, ficha interrompível, polling com cancelamento e recuo, realce do que mudou, ações com o servidor. |
| `resources/js/pages/sobre.js` | Cartão de autoria da página Sobre: entrada com SplitText, foto, mascote e nuvem seguindo o mouse (quickTo), tremor (CustomWiggle) e expansão até o portal do autor (Flip + clip-path), com Esc, bfcache e movimento reduzido. Os personagens 3D (CSS) ficam em `templates/_autoria.twig` ([ADR-0012, adendo](adr/0012-identidade-motion-e-build-de-assets.md#adendo-19102026--cartão-de-autoria-na-página-sobre)). |
| `resources/js/pages/planejar.js` | Mapa do Planejar reforço: retângulos dos setores (classe CSS por posição), rótulos curtos por `textContent`, pontos dos agentes livres; a lista renderizada no servidor tem os mesmos links. |
| `resources/js/lib/` | `motion` (preferência), `toasts` (avisos com deduplicação), `forms`, `popovers`, `shell` (gaveta), `picker` (mapa nos formulários), `dom`. |
| `vite.config.js` · `src/Http/ViteAssets.php` | Build para `public/assets/build/` (versionado; produção sem Node) e tags a partir do manifesto respeitando `APP_BASE_PATH`. |
| `public/assets/img/` | Sprite de ícones (inclui a marca `i-brand`), miniaturas por categoria e favicon (próprios); `autor/` tem a foto do autor (fora da licença MIT). |
| `scripts/capturar-telas.mjs` | Verificação visual sem dependências (Chrome headless via DevTools Protocol): capturas, medidas e erros de console. |

## Decisões

Ver [docs/adr](adr/). Convenções: identificadores SQL sem aspas (maiúsculos), chaves PHP em minúsculas
(`PDO::CASE_LOWER`), instantes em UTC, datas de manutenção locais, coordenadas `(lat, lng)` WGS84 com 6 casas.
