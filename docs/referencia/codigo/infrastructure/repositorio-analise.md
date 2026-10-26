# Repositório da análise preventiva

<!-- fontes: src/Infrastructure/Repository/AnalysisRepository.php -->

Execuções da análise (`ANALYSIS_RUN`) e resultados por equipamento (`ANALYSIS_RESULT`); ver
[dicionário](../../dicionario-de-dados.md#analysis_run-e-analysis_result).

## `AnalysisRepository`

| Método | Contrato |
|---|---|
| `AnalysisRepository::__construct` | `(Database $db)`. |
| `AnalysisRepository::insertRun` | `(array $run): int` — `method`, `as_of`, `horizon`, `data_from`, `data_to`, `events_used`, `params` e `evaluation` (JSON), `created_at`, `created_by` (ou `null` pela CLI). |
| `AnalysisRepository::insertResult` | `(int $runId, array $r): void` — `equipment_id`, `attention`, `score` (ou `null`), fatores e sinais administrativos em JSON (`{fatores, administrativo}`), recomendação e dias de histórico. Único por execução e equipamento. |
| `AnalysisRepository::latestRun` | `(): ?array` — a execução mais recente, com o nome de quem recalculou e `params`/`evaluation` decodificados. |
| `AnalysisRepository::results` | `(int $runId): list<array>` — resultados com código, tipo, situação, marca, modelo, rua e bairro, ordenados ALTA, MODERADA, CORRETIVA, INSUFICIENTE, demais; depois pontuação decrescente e código. |
| `AnalysisRepository::latestForEquipment` | `(int $equipmentId): ?array` — resultado mais recente de um equipamento, com o instante da execução (bloco "Revisão recomendada" da ficha). |

## Relacionados

[Análise preventiva (serviço)](../application/analise-preventiva.md) · [Guia: Revisões recomendadas](../../../guias/revisoes-recomendadas.md)
