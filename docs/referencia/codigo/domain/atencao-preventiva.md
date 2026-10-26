# Índice de atenção preventiva (`atencao-v1`)

<!-- fontes: src/Domain/AttentionModel.php -->

Regra pura do método das **Revisões recomendadas** (parte 3 do desafio, rota `/antecipacao`). Soma evidências do
histórico de um equipamento numa pontuação de 0 a 100 — **heurística, não probabilidade calibrada** — e calcula as
métricas da avaliação retrospectiva. Os dados e os cortes temporais ficam em
[`AnalysisService`](../application/analise-preventiva.md); metodologia e resultados em
[análise preventiva](../../../analise-preventiva.md); decisão em [ADR-0007](../../../adr/0007-analise-preventiva-heuristica.md).

| Constante | Valor | Papel |
|---|---|---|
| `METHOD` | `atencao-v1` | identifica o método em `ANALYSIS_RUN.METHOD_VERSION` |
| `HORIZON_DAYS` | 7 | horizonte da pergunta ("próximos 7 dias") e do rótulo da avaliação |
| `MIN_HISTORY_DAYS` | 60 | abaixo disso a classe é `INSUFICIENTE` |
| `LONG_WINDOW_DAYS` | 180 | janela da propensão do próprio aparelho |
| `THRESHOLD_GRID` | `[25, 35, 45, 55]` | limiares candidatos para `ALTA`, escolhidos só na janela de ajuste |
| `WEIGHTS` | ver tabela | pontos de cada fator |

| Fator | Pontos |
|---|---|
| Falhas nos últimos 30 dias (`falha_30d`) | 18 cada, máx. 36 |
| Falhas entre 31 e 180 dias (`falha_31_180d`) | 7 cada, máx. 35 |
| Dias desde a última preventiva ÷ intervalo previsto | ≥ 0,8 → 6 · ≥ 1,0 → 15 · ≥ 1,5 → 25 |
| Sem periodicidade e mais de 120 dias sem manutenção concluída (`sem_periodicidade_120d`) | 10 |

## `AttentionModel::assess`

`static assess(array $f, int $highThreshold): array{attention: string, score: ?int, factors: list<array{label, points}>, admin_flags: list<string>, recommendation: string}`

| Chave de `$f` | Significado (calculado por [`AnalysisService::features`](../application/analise-preventiva.md#analysisservicefeatures)) |
|---|---|
| `history_days` | dias entre o cadastro e o corte |
| `status_at` | situação no corte, reconstruída só com eventos já recebidos |
| `failures_30`, `failures_31_180` | falhas conhecidas nas janelas |
| `interval` | periodicidade preventiva vigente no corte (ou `null`) |
| `days_since_preventive` | dias desde a última preventiva concluída (sem nenhuma: desde o cadastro) |
| `days_since_maintenance` | dias desde qualquer manutenção concluída |
| `overdue_preventive_since` | data da preventiva vencida mais antiga, ou `null` |

Etapas:

1. Fora de serviço (`isInService() = false`: não instalado, desativado) → `NAO_AVALIADO`, sem pontuação.
2. Em serviço mas não operacional (falha, manutenção, furto) → `CORRETIVA`, sem pontuação; recomendação de corretiva ou
   de reposição (furto).
3. Operacional: soma os fatores (teto 100). O fator de preventiva usa a razão dias ÷ intervalo; sem periodicidade, vale
   o fator de 120 dias.
4. Histórico < 60 dias → `INSUFICIENTE` (com pontuação, sem classe).
5. Classe: `ALTA` se pontuação ≥ limiar; `MODERADA` se ≥ metade do limiar (divisão inteira); senão `BAIXA`.

Preventiva vencida entra como **sinal administrativo** (`admin_flags`), separado da tendência de falha, e não soma
pontos além do fator de atraso. Recomendações: ALTA "Agendar inspeção preventiva nos próximos 7 dias."; MODERADA
"Incluir na próxima rota de inspeção e acompanhar os sinais."; BAIXA "Manter o plano atual.".

Exemplo: operacional, 400 dias de histórico, 1 falha em 30 dias, intervalo 90 e 100 dias desde a preventiva (razão
1,1), limiar 35 → 18 + 15 = 33 → `MODERADA` (≥ 17). Testes: `AttentionModelTest`.

## `AttentionModel::metrics`

`static metrics(array $pairs): array{pairs, positives, flagged, tp, fp, fn, precision: ?float, recall: ?float, f1: ?float, prevalence: ?float}`

Entrada: pares `{flag, failed}` (equipamento × corte). Calcula direto das contagens, **sem arredondar antes** (a tela
arredonda):

- precisão = TP/(TP+FP), `null` sem nenhum alerta;
- revocação = TP/(TP+FN), `null` sem nenhuma falha;
- F1 = 2TP/(2TP+FP+FN), `null` só sem falhas **e** sem alertas; errar sem acertar dá 0,0 (desempenho nulo ≠ ausência de
  dados — achado OP-05 corrigido em 18/10/2026);
- prevalência = falhas ÷ pares, `null` sem pares.

Testes: `AttentionModelTest` (convenções de `null` e F1 = 0).

## Relacionados

[Guia: Revisões recomendadas](../../../guias/revisoes-recomendadas.md) · [Análise preventiva](../../../analise-preventiva.md)
