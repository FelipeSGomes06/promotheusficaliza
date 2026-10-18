# ADR-0007 — Análise preventiva heurística com separação temporal

Data: 18/10/2026 · Situação: aceita

**Contexto.** A parte 3 pede antecipação útil e explicável; o histórico é sintético.
**Decisão.** Índice aditivo de evidências (falhas recentes, propensão em 180 dias, atraso de preventiva) com classes
compreensíveis, limiar escolhido numa janela de ajuste e medido em janela posterior, contra uma referência simples.
Revisão do método após ver a primeira avaliação foi declarada e a verificação final usou sementes inéditas.
**Consequências.** Resultado honesto (≈2× o acaso, revocação 15%); nada de probabilidade calibrada nem acurácia real.
**Alternativas.** Regressão/árvores (mais opacas, sem dados reais para justificar); só regras administrativas (sem
antecipação de falha).
