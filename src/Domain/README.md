# Regras de domínio (`src/Domain/`)

Regras **puras**: sem banco, sem HTTP, sem relógio implícito (o instante entra por parâmetro ou por `Clock`). São a única
fonte das políticas que as telas e os serviços aplicam; os testes de unidade as exercitam sem Firebird.

| Arquivo | Responsabilidade | Referência |
|---|---|---|
| `EquipmentStatus.php`, `AgentStatus.php`, `AgentRole.php` | situações, rótulos, transições permitidas, em serviço/indisponível, bloqueio de início de manutenção | [situações e atualidade](../../docs/referencia/codigo/domain/situacoes-e-atualidade.md) |
| `Freshness.php` | atual / atrasado / sem informação (sinal 10/60 min; posição 5/30 min) | idem |
| `MaintenanceUrgency.php` | imediata, vencida, próxima (7 dias), programada — por data local | idem |
| `EquipmentCatalog.php`, `EquipmentType.php` | tipos, prefixos de código e características validadas por tipo | [catálogo](../../docs/referencia/codigo/domain/catalogo-de-equipamentos.md) |
| `Indicators.php` | disponibilidade e contagens do conjunto filtrado | [indicadores e alertas](../../docs/referencia/codigo/domain/indicadores-e-alertas.md) |
| `AlertRules.php` | condições de alerta e motivo de encerramento | idem |
| `AlertFollowUp.php` | próxima ação do acompanhamento de alerta de agente | idem |
| `AttentionModel.php` | heurística `atencao-v1` e métricas | [atenção preventiva](../../docs/referencia/codigo/domain/atencao-preventiva.md) |
| `FineRules.php`, `FineDocumentStatus.php` | placa, AIT, valores, datas, velocidade considerada, "a conferir", conferência e lembrete | [multas](../../docs/referencia/codigo/domain/multas.md) |
| `SectorForecast.php`, `NearestRegion.php` | ranking, grade de 2 km, corte de informação, Poisson portátil, bairro mais próximo | [setores e regiões](../../docs/referencia/codigo/domain/setores-e-regioes.md) |
| `Time.php`, `Clock.php` | UTC no banco, Marília na tela, datas locais | [tempo](../../docs/referencia/codigo/domain/tempo.md) |
| `RuleViolation.php`, `ValidationFailed.php` | exceções de regra e de validação | [erros](../../docs/referencia/codigo/erros.md) |

Explicação para quem não programa: [domínio](../../docs/dominio.md).
