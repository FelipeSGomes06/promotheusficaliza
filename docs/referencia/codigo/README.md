# Referência de código

Contratos de cada classe, função, método e macro do código próprio: assinatura, parâmetros, retorno, validações,
transação e concorrência, efeitos, erros, chamadores e testes. Cada página declara no topo os arquivos que cobre
(`<!-- fontes: … -->`); a lista completa de símbolos, com o status de cada um, está no
[catálogo de símbolos](../catalogo-simbolos.md), gerado por `node scripts/documentacao/inventario.mjs --catalogo`.

Para entender **o que** cada parte faz para quem usa, comece pelos [guias](../../guias/README.md); para o esquema, pelo
[dicionário de dados](../dicionario-de-dados.md).

## Raiz e erros

- [Configuração, serviços e pontos de entrada](raiz/configuracao-e-servicos.md) — `Config`, `Services`, `public/index.php`, `bin/fiscaliza`
- [Erros e exceções](erros.md)

## Domain

- [Situações e atualidade](domain/situacoes-e-atualidade.md) — `EquipmentStatus`, `AgentStatus`, `AgentRole`, `Freshness`, `MaintenanceUrgency`
- [Catálogo de equipamentos](domain/catalogo-de-equipamentos.md) — `EquipmentCatalog`, `EquipmentType`
- [Tempo](domain/tempo.md) — `Time`, `Clock`
- [Indicadores e alertas](domain/indicadores-e-alertas.md) — `Indicators`, `AlertRules`, `AlertFollowUp`
- [Atenção preventiva](domain/atencao-preventiva.md) — `AttentionModel`
- [Multas](domain/multas.md) — `FineRules`, `FineDocumentStatus`
- [Setores e regiões](domain/setores-e-regioes.md) — `SectorForecast`, `NearestRegion`

## Application

- [Validação e filtros](application/validacao-e-filtros.md) — `Input`, `Filters`, `FineFilters`
- [Endereço](application/endereco.md) — `AddressPoint`, `AddressLocator`, `CepLookup`, `Geocoder`, `CepAddress`
- [Equipamentos](application/equipamentos.md) — `EquipmentService`, `History`
- [Agentes](application/agentes.md) — `AgentService`
- [Manutenção](application/manutencao.md) — `MaintenanceService`
- [Alertas](application/alertas.md) — `AlertService`
- [Ingestão](application/ingestao.md) — `EventIngestor`
- [Central](application/central.md) — `CentralQuery`
- [Autenticação](application/autenticacao.md) — `AuthService`
- [Análise preventiva](application/analise-preventiva.md) — `AnalysisService`
- [Multas](application/multas.md) — `FineService`, `FineQuery`
- [Planejamento](application/planejamento.md) — `PlanningService`
- [Importação urbana](application/importacao-urbana.md) — `UrbanImporter`
- [Atualização urbana](application/atualizacao-urbana.md) — `UrbanUpdater`

## Infrastructure

- [Banco e migrações](infrastructure/banco-e-migracoes.md) — `Database`, `Migrator`
- [Provedores externos](infrastructure/provedores-externos.md) — `ViaCepClient`, `NominatimGeocoder`
- Repositórios: [inventário](infrastructure/repositorios-inventario.md), [operação](infrastructure/repositorios-operacao.md),
  [contas](infrastructure/repositorio-contas.md), [análise](infrastructure/repositorio-analise.md),
  [multas](infrastructure/repositorios-multas.md), [planejamento](infrastructure/repositorios-planejamento.md)

## Http

- [Aplicação web](http/aplicacao-web.md) — `WebApp`, `Session`, `View`, `ViteAssets`
- [Middlewares](http/middlewares.md)
- [Controladores](http/controladores.md)

## CLI

- [Console](cli/console.md) — `bin/fiscaliza`

## Demo

- [Cenário e carga](demo/cenario-e-carga.md) — `DemoScenario`, `DemoSeeder`
- [Multas de demonstração](demo/multas-de-demonstracao.md) — `FineScenario`
- [Simulador](demo/simulador.md) — `Simulator`

## Frontend e telas

- [Entrada e bibliotecas](frontend/entrada-e-bibliotecas.md) — `app.js`, `lib/*`
- [Central](frontend/central.md) — componente da Visão geral
- [Páginas](frontend/paginas.md) — Multas, Planejar, Sobre
- [Macros e telas Twig](templates/macros-e-telas.md)

## Testes

- [Suporte](testes/suporte.md) — `FirebirdTestCase`, falsos, concorrência
- [Casos de teste](testes/casos.md) — todos os casos e o que provam

## Fora da aplicação

- [Scripts de apoio](scripts/scripts.md) — CI, backup, sondagem, avaliação, verificação da interface, inventário
- [Treino e experimento (ML)](ml/treino-e-experimento.md)
- [Landing pública](landing/landing.md)
- [Vídeo de apresentação](video/video.md)

Também: [implantação, configuração e CI](../implantacao-e-ci.md) e [dicionário de dados](../dicionario-de-dados.md).
