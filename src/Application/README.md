# Casos de uso (`src/Application/`)

Cada serviço recebe dados já lidos do formulário ou da CLI, valida na fronteira, executa **uma transação curta** e
grava histórico e reconciliação de alertas na mesma transação. Lança `ValidationFailed` (erros por campo → 422),
`RuleViolation` (regra → 409/aviso), `NotFound` e `ConcurrencyConflict` ([erros](../../docs/referencia/codigo/erros.md)).

| Arquivo | Responsabilidade | Referência |
|---|---|---|
| `EquipmentService.php` | cadastrar, editar (versão otimista), mudar situação, arquivar equipamentos | [equipamentos](../../docs/referencia/codigo/application/equipamentos.md) |
| `History.php` | grava eventos de recurso e calcula diferenças antes/depois | [equipamentos](../../docs/referencia/codigo/application/equipamentos.md#history) |
| `AgentService.php` | cadastrar, editar, mudar situação, arquivar agentes | [agentes](../../docs/referencia/codigo/application/agentes.md) |
| `MaintenanceService.php` | abrir, iniciar, concluir, cancelar ordens; preventiva seguinte | [manutenção](../../docs/referencia/codigo/application/manutencao.md) |
| `AlertService.php` | reconciliar alertas, ciência, responsável, providências | [alertas](../../docs/referencia/codigo/application/alertas.md) |
| `EventIngestor.php` | entregar eventos de situação e posição (idempotente, atrasados só no histórico) | [ingestão](../../docs/referencia/codigo/application/ingestao.md) |
| `CentralQuery.php` | instantâneo da Visão geral (mapa, lista e indicadores do mesmo filtro) | [central](../../docs/referencia/codigo/application/central.md) |
| `AnalysisService.php` | executar e gravar a análise preventiva com cortes temporais | [análise preventiva](../../docs/referencia/codigo/application/analise-preventiva.md) |
| `AuthService.php`, `AuthenticationFailed.php` | login com cota atômica, contas, redefinição de senha | [autenticação](../../docs/referencia/codigo/application/autenticacao.md) |
| `FineService.php`, `FineQuery.php` | escrita e consulta das multas demonstrativas | [multas](../../docs/referencia/codigo/application/multas.md) |
| `PlanningService.php` | semana, candidatos, missões, retrospecto | [planejamento](../../docs/referencia/codigo/application/planejamento.md) |
| `UrbanImporter.php` | importar execução do treino e grade de 2 km | [importação urbana](../../docs/referencia/codigo/application/importacao-urbana.md) |
| `UrbanUpdater.php` | download mensal do Infosiga e previsão semanal | [atualização urbana](../../docs/referencia/codigo/application/atualizacao-urbana.md) |
| `AddressPoint.php`, `AddressLocator.php`, `CepLookup.php`, `CepAddress.php`, `Geocoder.php`, `ProviderUnavailable.php` | ponto × endereço, CEP e geocodificação (interfaces + resultado) | [endereço](../../docs/referencia/codigo/application/endereco.md) |
| `Input.php`, `Filters.php`, `FineFilters.php` | normalização da entrada e filtros de listas | [validação e filtros](../../docs/referencia/codigo/application/validacao-e-filtros.md) |
| `NotFound.php`, `DuplicateEvent.php` | exceções | [erros](../../docs/referencia/codigo/erros.md) |
