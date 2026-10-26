# Repositórios (`src/Infrastructure/Repository/`)

Todo o SQL da aplicação. Não decidem regra: leem e gravam o que os serviços pedem, com bloqueio (`WITH LOCK`) e versão
otimista quando o serviço exige. Colunas e restrições: [dicionário de dados](../../../docs/referencia/dicionario-de-dados.md).

| Arquivo | Tabelas | Referência |
|---|---|---|
| `EquipmentRepository.php` | `EQUIPMENT` | [inventário](../../../docs/referencia/codigo/infrastructure/repositorios-inventario.md#equipmentrepository) |
| `AgentRepository.php` | `AGENT` | [inventário](../../../docs/referencia/codigo/infrastructure/repositorios-inventario.md#agentrepository) |
| `ReferenceRepository.php` | `AGENCY`, `REGION` | [inventário](../../../docs/referencia/codigo/infrastructure/repositorios-inventario.md#referencerepository) |
| `MaintenanceRepository.php` | `MAINTENANCE_ORDER` | [operação](../../../docs/referencia/codigo/infrastructure/repositorios-operacao.md#maintenancerepository) |
| `AlertRepository.php` | `ALERT`, `ALERT_FOLLOWUP` | [operação](../../../docs/referencia/codigo/infrastructure/repositorios-operacao.md#alertrepository) |
| `EventRepository.php` | `RESOURCE_EVENT` | [operação](../../../docs/referencia/codigo/infrastructure/repositorios-operacao.md#eventrepository) |
| `MetaRepository.php` | `APP_META` | [operação](../../../docs/referencia/codigo/infrastructure/repositorios-operacao.md#metarepository) |
| `UserRepository.php` | `APP_USER`, `LOGIN_ATTEMPT` | [contas](../../../docs/referencia/codigo/infrastructure/repositorio-contas.md) |
| `AnalysisRepository.php` | `ANALYSIS_RUN`, `ANALYSIS_RESULT` | [análise](../../../docs/referencia/codigo/infrastructure/repositorio-analise.md) |
| `FineRepository.php` | `FINE`, `FINE_DOCUMENT_CHECK`, `FINE_EVENT` | [multas](../../../docs/referencia/codigo/infrastructure/repositorios-multas.md) |
| `UrbanRepository.php` | `URBAN_RUN`, `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_CELL`, `URBAN_SNAPSHOT`, `URBAN_DAILY` | [planejamento](../../../docs/referencia/codigo/infrastructure/repositorios-planejamento.md#urbanrepository) |
| `MissionRepository.php` | `URBAN_MISSION`, `URBAN_MISSION_LOG` | [planejamento](../../../docs/referencia/codigo/infrastructure/repositorios-planejamento.md#missionrepository) |
