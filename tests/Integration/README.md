# Testes de integração (Firebird real)

Suíte `integracao-firebird`. Todas as classes estendem
[`FirebirdTestCase`](../../docs/referencia/codigo/testes/suporte.md#firebirdtestcase): esquema recriado pelas migrações
uma vez por execução, tabelas vazias a cada teste, casos de uso com o usuário da aplicação. Concorrência real em
processos separados (`Parallel`) ou ordem controlada em Fibers (`Interleaving`). Catálogo:
[casos de teste — integração](../../docs/referencia/codigo/testes/casos.md#suíte-integracao-firebird-testsintegration).

| Arquivo | Tema |
|---|---|
| `AccountsTest.php` | último administrador ativo, inclusive sob concorrência |
| `AgentBaseTest.php` | base de trabalho do agente (ponto, endereço, bairro) |
| `AlertFollowUpTest.php` | ciência, responsável, providências, encerramento com motivo |
| `CentralAndAnalysisTest.php` | Visão geral filtrada e cortes temporais da análise preventiva |
| `DemoProfileTest.php` | perfis compacto e completo da demonstração |
| `DriverContractTest.php` | contrato com o PDO_FIREBIRD e privilégios do usuário da aplicação |
| `EquipmentLocationTest.php` | local do equipamento (ponto × endereço) |
| `FinesTest.php` | multas: registro, origem, conferências, andamento, idempotência, concorrência |
| `InventoryTest.php` | cadastro, séries, versões, transições, arquivamento, base × posição |
| `MaintenanceInvariantsTest.php` | uma intervenção em execução por equipamento, arquivamento, concorrência |
| `OperationsTest.php` | ingestão (duplicados, atrasos), alertas, manutenção, trava do simulador |
| `PlanningTest.php` | importação, sugestão de agente, missões, retrospecto |
| `UrbanUpdaterTest.php` | atualização mensal do Infosiga e previsão semanal (ZIPs montados no teste) |
