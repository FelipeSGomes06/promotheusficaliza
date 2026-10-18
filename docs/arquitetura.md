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

**Leitura da central.** `GET /` renderiza no servidor (útil sem JavaScript) com o instantâneo de `CentralQuery`;
`central.js` consulta `GET /api/central` a cada `POLL_INTERVAL_SECONDS`. O instantâneo é lido numa única transação
**SNAPSHOT somente leitura**: recursos, ordens, alertas e indicadores vêm do mesmo instante e do mesmo filtro.

**Escrita manual.** Formulário → middleware de sessão/CSRF/papel → controlador → serviço de aplicação → validação
(`Input`, esquema do tipo) → transação curta: `SELECT … WITH LOCK` quando há mudança de situação, gravação, evento de
histórico (`History`) e reconciliação de alertas → commit. Conflito de lock vira `ConcurrencyConflict` (409) pelo
timeout de instrução; edição com versão antiga também.

**Simulação.** `sim:run` (contêiner `simulator`) ou `sim:tick` (cron) chama `Simulator::tick()`, que entrega
observações fictícias pelos mesmos casos de uso (`EventIngestor`, `AgentService`), grava no Firebird e reconcilia
alertas. Ciclos numerados + semente = sequência reproduzível.

**Análise preventiva.** `AnalysisService::run()` lê eventos de situação e ordens, calcula características por
equipamento só com dados anteriores a cada corte, ajusta o limiar numa janela e avalia em outra, grava a execução.

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
| `Time.php`, `Clock.php` | Convenção UTC no banco / America/Sao_Paulo na tela; relógio injetável para testes e geração de histórico. | `DomainRulesTest` |
| `ValidationFailed.php`, `RuleViolation.php` | Erros de campo (422) e de regra (409). | — |

### `src/Application` — casos de uso

| Arquivo | Entradas → saídas | Observações |
|---|---|---|
| `Input.php` | dados crus → valores normalizados + erros por campo | Lê só as chaves pedidas; CEP, coordenadas na área de Marília, datas, controle de tamanho. |
| `Filters.php` | query string → filtros válidos | Descarta valores desconhecidos; decide se equipamentos/agentes entram no escopo. |
| `EquipmentService.php` | formulário → id / alteração / transição / arquivamento | Traduz restrições do Firebird em erros de campo; histórico antes/depois. |
| `AgentService.php` | idem para agentes | Base ≠ posição observada. |
| `MaintenanceService.php` | abrir, iniciar, concluir, cancelar ordens | Iniciar põe em manutenção; concluir exige situação resultante; preventiva periódica agenda a próxima. |
| `EventIngestor.php` | observação (uid, instante) → APLICADO / HISTORICO / DUPLICADO | Duplicata por `EVENT_UID`; atrasado não regride; estado administrativo prevalece; posição só avança. |
| `AlertService.php` | estado atual → alertas abertos/resolvidos | Idempotente; reconhecer ≠ resolver. |
| `CentralQuery.php` | filtros → instantâneo da central | Transação SNAPSHOT; resumo por bairro; listas de manutenção; dados de sincronização. |
| `AnalysisService.php` | histórico → execução da análise + avaliação | Separação temporal ajuste/avaliação. |
| `AuthService.php` | credenciais → conta | Argon2id, limite por usuário/IP, contas só por CLI/admin. |
| `History.php` | registro de eventos manuais/sistema | Também calcula diferença campo a campo. |

### `src/Infrastructure` — Firebird

| Arquivo | Finalidade |
|---|---|
| `Database.php` | Conexão PDO_FIREBIRD (UTF8, dialeto 3), `SET STATEMENT TIMEOUT`, transações (inclusive SNAPSHOT somente leitura), parâmetros posicionais, tradução de erros (`ConstraintViolation`, `ConcurrencyConflict`, `DatabaseUnavailable`) sem expor DSN/SQL. |
| `Migrator.php` | Aplica `database/migrations/*.sql` uma vez (tabela `SCHEMA_MIGRATION`) com a identidade de migração e concede ao usuário da aplicação apenas DML (somente leitura+inserção nas tabelas de histórico). |
| `Repository/*.php` | Todo o SQL: equipamentos, agentes, manutenção, eventos, alertas, contas, metadados, referência e análise. Colunas graváveis e ordenações vêm de listas fechadas. |

### `src/Http` — entrada web

| Arquivo | Finalidade |
|---|---|
| `WebApp.php` | Monta o Slim: rotas, papéis por grupo, ordem dos middlewares, tratamento de erros com código de referência. |
| `Middleware/SecurityHeaders.php` | CSP sem `unsafe-inline`, nosniff, bloqueio de enquadramento, referência, permissões, COOP/CORP, `no-store`. |
| `Middleware/SessionContext.php` | Retoma sessão só se há cookie, expira por inatividade (30 min) e tempo absoluto (8 h), carrega conta ativa. |
| `Middleware/CsrfGuard.php` | Token de sincronização em toda escrita + verificação de `Origin`. |
| `Middleware/RequireRole.php` | OPERADOR/ADMIN no servidor; visitante vai ao login (GET) ou recebe 401. |
| `Session.php`, `View.php`, `HttpError.php` | Sessão própria com Path restrito; Twig com escape automático e filtros de data/porcentagem. |
| `Controller/*.php` | Central, equipamentos, agentes, operações (manutenção, alertas, análise), autenticação/contas, páginas. |

### `src/Demo` e `src/Cli`

| Arquivo | Finalidade |
|---|---|
| `Demo/DemoScenario.php` | Dados sintéticos: órgãos, bairros, 48 equipamentos, bases e 21 agentes fictícios. |
| `Demo/DemoSeeder.php` | Carga + 240 dias de histórico reproduzível (falhas, corretivas, preventivas com atraso). |
| `Demo/Simulator.php` | Ciclo ao vivo: sinais, falhas/recuperações, deslocamentos, duplicatas e atrasos intencionais. |
| `Cli/Console.php` | `migrate`, `demo:seed [--reset]`, `user:create`, `sim:tick`, `sim:run`, `analysis:run`. |
| `Services.php` | Raiz de composição: cria cada dependência uma vez; troca de identidade (aplicação × migração). |

### Apresentação

| Caminho | Finalidade |
|---|---|
| `templates/*.twig` | Layout, central, inventário, fichas, formulários, manutenção, antecipação, sobre, login, contas, erro. |
| `public/assets/js/central.js` | Mapa, marcadores, ficha lateral, abas acessíveis, consulta periódica com pausa/recuo, anúncios `aria-live`. |
| `public/assets/js/app.js` | Preferência de movimento, campos por tipo, seletor de coordenadas, confirmação de ações. |
| `public/assets/css/app.css` | Identidade visual, estados (cor + forma + texto), responsivo, movimento reduzido. |
| `public/assets/img/` | Sprite de ícones, miniaturas por categoria e favicon (próprios). |

## Decisões

Ver [docs/adr](adr/). Convenções: identificadores SQL sem aspas (maiúsculos), chaves PHP em minúsculas
(`PDO::CASE_LOWER`), instantes em UTC, datas de manutenção locais, coordenadas `(lat, lng)` WGS84 com 6 casas.
