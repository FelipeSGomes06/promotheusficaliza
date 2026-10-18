# ADR-0007 — Análise preventiva heurística com separação temporal

Data: 18/10/2026 · Situação: aceita

**Contexto.** A parte 3 pede antecipação útil e explicável; o histórico é sintético.
**Decisão.** Índice aditivo de evidências (falhas recentes, propensão em 180 dias, atraso de preventiva) com classes
compreensíveis, limiar escolhido numa janela de ajuste e medido em janela posterior, contra uma referência simples.
Revisão do método após ver a primeira avaliação foi declarada e a verificação final usou sementes inéditas.
**Consequências.** Resultado honesto (≈2× o acaso, revocação 15%); nada de probabilidade calibrada nem acurácia real.
**Alternativas.** Regressão/árvores (mais opacas, sem dados reais para justificar); só regras administrativas (sem
antecipação de falha).

**Revisão (18/10/2026, achados OP-04 e OP-05).** A "separação temporal" não era rigorosa: os cortes usavam a
periodicidade cadastrada hoje, os equipamentos não arquivados hoje e eventos pela data de ocorrência mesmo que
recebidos depois do corte. Agora cada corte usa só o que o sistema já sabia (`RECEIVED_AT` antes do corte), a
periodicidade vigente naquele instante (reconstruída pelo histórico de alterações) e os equipamentos que existiam e
não estavam arquivados; o rótulo continua pela ocorrência. F1, precisão e revocação saem das contagens sem
arredondamento intermediário; `null` só quando o denominador é zero. Fora de serviço passa a "não avaliado" (antes
aparecia como "baixa"). As cinco sementes inéditas foram reavaliadas: precisão agregada 8,9% (antes 8,2%) e revocação
16,7% (antes 15,2%) — ver [evidência](../evidencias/2026-10-18-avaliacao-sementes.md).
