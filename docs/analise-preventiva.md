# Análise preventiva (parte 3)

**Pergunta operacional:** quais equipamentos merecem revisão nos próximos **7 dias**, e por quê?

**Saída:** classe de atenção (alta, moderada, baixa, *insuficiente*, *corretiva*), índice 0–100, fatores com pontos,
sinais administrativos, ação recomendada, dias de histórico, data do cálculo, método e parâmetros
(`/antecipacao` e ficha do equipamento). É apoio à decisão: nada muda automaticamente.

> O índice é **heurístico, não probabilidade calibrada**. O histórico é **sintético**. A avaliação mede coerência do
> método no cenário gerado — não acurácia em equipamentos reais.

## Dados usados

Somente o que estava registrado **antes** do instante de cálculo: eventos de situação aplicados (falhas e retornos),
ordens de manutenção (preventivas concluídas, preventivas abertas vencidas), periodicidade cadastrada e data de
cadastro. Eventos marcados como `HISTORICO` (atrasados/sobrepostos) não entram na linha do tempo.

## Método `atencao-v1`

| Fator | Pontos |
|---|---|
| Falhas nos últimos 30 dias (recorrência recente) | 18 cada, máx. 36 |
| Falhas entre 31 e 180 dias (propensão do próprio aparelho) | 7 cada, máx. 35 |
| Dias desde a última preventiva ÷ intervalo previsto | ≥ 0,8 → 6 · ≥ 1,0 → 15 · ≥ 1,5 → 25 |
| Sem periodicidade e > 120 dias sem manutenção concluída | 10 |

- **Alta** ≥ limiar ajustado; **moderada** ≥ metade dele; **baixa** abaixo.
- **Insuficiente:** menos de 60 dias de histórico — a pontuação aparece, sem classificação.
- **Corretiva:** já em falha ou manutenção — tratar como corretiva, não é antecipação.
- **Ordem preventiva vencida** aparece como sinal administrativo separado (não soma pontos além do fator de atraso).

Implementação: [`AttentionModel`](../src/Domain/AttentionModel.php) (regras puras, testadas) e
[`AnalysisService`](../src/Application/AnalysisService.php) (dados, cortes, gravação).

## Separação temporal e avaliação

- Cortes semanais. **Ajuste:** de 150 a 63 dias atrás — só aqui o limiar de "alta" é escolhido entre {25, 35, 45, 55}
  (maior F1; empate: menor limiar). **Avaliação:** de 56 a 7 dias atrás, com o limiar já fixado. A janela de rótulo
  do último corte de ajuste termina quando começa a avaliação.
- Rótulo: houve ao menos uma falha nos 7 dias seguintes ao corte. Entram só pares classificáveis (operacional no
  corte e com histórico mínimo).
- Referência simples: "falhou nos últimos 30 dias". Também é mostrada a prevalência (o que um sorteio acertaria).
- Cada execução grava parâmetros, período, quantidade de eventos e a avaliação (`ANALYSIS_RUN`), e é reproduzível:
  mesmos dados + mesmo instante → mesmo resultado (teste `CentralAndAnalysisTest`).

## Resultado honesto

Uma versão preliminar (sem a janela de 180 dias) foi avaliada na semente 2026 durante o desenvolvimento: precisão
0,077 e revocação 0,071. Como o método foi revisto depois de ver esse resultado, a verificação final do método
congelado foi feita em **cinco sementes inéditas** ([evidência](evidencias/2026-10-18-avaliacao-sementes.md)):

| | Precisão | Revocação |
|---|---|---|
| Índice (alta) | **8,2%** (10/122) | **15,2%** (10/66) |
| Referência "falhou nos últimos 30 dias" | 6,3% (13/205) | 19,7% (13/66) |
| Prevalência (acaso) | 3,9% | — |

Leitura: o índice concentra falhas ~2× acima do acaso com menos alertas que a referência, mas perde a maioria das
falhas e o limiar escolhido varia entre cenários (25 a 55) — amostras pequenas (~13 falhas por janela). Serve para
**priorizar uma rota semanal de inspeção**, não para garantir prevenção.

## Gerador sintético (o que ele assume)

[`DemoSeeder`](../src/Demo/DemoSeeder.php): risco diário de falha = 0,4% × propensão do aparelho (0,6–2,9, oculta para
a análise) × (1 + 2 × atraso relativo da preventiva) × (1 + 0,6 × falhas nos últimos 30 dias). 28% das preventivas
atrasam de 8 a 30 dias. Esses mecanismos são plausíveis, mas **inventados**: um resultado bom aqui não prova nada sobre
um parque real. Com dados reais, seria preciso recalibrar pesos e limiar com janelas temporais maiores, medir
estabilidade entre períodos e comparar com o processo atual de inspeção.

## Limitações

Sem dados de clima, tráfego, idade real, fabricante ou intervenções externas; sem calibração probabilística; histórico
curto para equipamentos recentes; um só tipo de problema (falha do equipamento). Não há previsão de crimes, ranking de
pessoas ou inferência sobre bairros.
