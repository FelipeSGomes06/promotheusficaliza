# Avaliação da análise preventiva em sementes inéditas — 18/10/2026

Comando: `docker compose exec app php scripts/avaliar-sementes.php 2027 2028 2029 2030 2031` (Firebird 5.0.4, PHP 8.5.11).

**Contexto metodológico.** Uma versão preliminar do método (sem a janela de 180 dias) foi avaliada na semente 2026
durante o desenvolvimento: precisão 0,077, revocação 0,071 (1 acerto, 12 falsos alertas, 13 falhas não antecipadas).
Revisar o método depois de ver esse resultado tornou a janela de avaliação da semente 2026 "não cega". Por isso, a
verificação final do método congelado `atencao-v1` foi feita em cinco sementes que não tinham sido usadas.
Em cada semente: limiar de ALTA escolhido só na janela de ajuste (cortes de 150 a 63 dias atrás) e medido na janela
de avaliação (cortes de 56 a 7 dias atrás). Rótulo = ao menos uma falha nos 7 dias seguintes ao corte.

| Semente | Limiar escolhido | Modelo TP/FP/FN | Precisão | Revocação | Referência TP/FP/FN | Precisão | Revocação | Prevalência |
|---|---|---|---|---|---|---|---|---|
| 2027 | 45 | 1/10/11 | 0,091 | 0,083 | 3/43/9 | 0,065 | 0,250 | 0,036 |
| 2028 | 25 | 2/31/12 | 0,061 | 0,143 | 2/30/12 | 0,063 | 0,143 | 0,041 |
| 2029 | 25 | 6/52/11 | 0,103 | 0,353 | 5/48/12 | 0,094 | 0,294 | 0,051 |
| 2030 | 35 | 1/19/10 | 0,050 | 0,091 | 0/38/11 | 0,000 | 0,000 | 0,033 |
| 2031 | 55 | 0/0/12 | — | 0,000 | 3/33/9 | 0,083 | 0,250 | 0,035 |
| **Agregado** | | 10/112/56 | **0,082** | **0,152** | 13/192/53 | **0,063** | **0,197** | **0,039** |

Referência = "falhou nos últimos 30 dias". 1.681 pares (equipamento × corte), 66 com falha.

## Leitura honesta

- O índice concentra falhas cerca de **2×** acima do acaso (0,082 vs prevalência 0,039) e um pouco acima da referência
  simples, mas **perde a maioria das falhas** (revocação de 15%).
- O limiar escolhido varia muito entre sementes (25 a 55): com ~13 falhas por janela, a amostra é pequena e o ajuste instável.
- Utilidade: priorizar uma rota semanal de inspeção, não garantir prevenção. Não é probabilidade calibrada.
- Tudo é cenário sintético gerado por regras conhecidas (`DemoSeeder`): o resultado mede coerência do método com
  esse gerador, **não** acurácia em equipamentos reais.

## Reavaliação após as correções OP-04 e OP-05 (18/10/2026)

Mesmo comando e mesmas sementes, em Compose isolado (projeto Compose separado, banco de demonstração descartável),
PHP 8.5.11 e Firebird 5.0.4, depois de: cortes que só conhecem eventos **recebidos** antes deles, periodicidade
vigente reconstruída pelo histórico, população que inclui quem foi arquivado depois do corte, métricas calculadas
direto das contagens e "não avaliado" para fora de serviço. O método `atencao-v1` (pesos, janelas, grade de limiares)
não mudou.

| Semente | Limiar escolhido | Modelo TP/FP/FN | Precisão | Revocação | Referência TP/FP/FN | Precisão | Revocação | Prevalência |
|---|---|---|---|---|---|---|---|---|
| 2027 | 45 | 1/10/11 | 0,091 | 0,083 | 3/42/9 | 0,067 | 0,250 | 0,036 |
| 2028 | 25 | 2/31/12 | 0,061 | 0,143 | 2/32/12 | 0,059 | 0,143 | 0,041 |
| 2029 | 25 | 7/52/9 | 0,119 | 0,438 | 5/46/11 | 0,098 | 0,312 | 0,047 |
| 2030 | 35 | 1/19/10 | 0,050 | 0,091 | 0/39/11 | 0,000 | 0,000 | 0,032 |
| 2031 | 55 | 0/0/13 | — (nenhum alerta) | 0,000 | 4/33/9 | 0,108 | 0,308 | 0,038 |
| **Agregado** | | 11/112/55 | **0,089** | **0,167** | 14/192/52 | **0,068** | **0,212** | **0,039** |

1.696 pares (equipamento × corte), 66 com falha. O script agora imprime "—" para precisão indefinida (antes mostrava
0,000 por usar `?? 0`). Ao terminar, o script deixa carregada a semente 2031; o cenário padrão foi recarregado em
seguida com `demo:seed --reset`.

Leitura: mesma conclusão de antes — cerca de 2× o acaso, um pouco acima da referência em precisão, abaixo em
revocação, limiar instável entre sementes. A diferença em relação à primeira avaliação vem só da reconstrução
temporal; nenhum parâmetro foi ajustado para melhorar o número.

## Perfis compacto e completo (18/10/2026)

Depois da [ADR-0013](../adr/0013-perfil-compacto-de-demonstracao.md), as mesmas sementes foram avaliadas nos dois
perfis, no ambiente isolado (`php scripts/avaliar-sementes.php --perfil=… 2027 2028 2029 2030 2031`). O método não
mudou; o cenário mudou em três pontos que entram no histórico de ordens: três preventivas abertas passaram a ter data
fixa (vencida, próxima e futura), o que altera alguns cortes desses equipamentos também no perfil completo.

| Perfil | Pares (falhas) | Índice TP/FP/FN | Precisão | Revocação | Referência TP/FP/FN | Precisão | Revocação | Prevalência |
|---|---|---|---|---|---|---|---|---|
| Completo (48) | 1.697 (67) | 11/118/56 | **0,085** | **0,164** | 15/190/52 | 0,073 | 0,224 | 0,039 |
| Compacto (16) | 526 (30) | 4/54/26 | **0,069** | **0,133** | 8/94/22 | 0,078 | 0,267 | 0,057 |

Por semente, completo — limiar · índice TP/FP/FN · referência TP/FP/FN: 2027 45 · 1/7/9 · 2/29/8; 2028 25 · 3/36/12 ·
3/37/12; 2029 25 · 7/62/11 · 5/52/13; 2030 35 · 0/13/11 · 1/38/10; 2031 55 · 0/0/13 (nenhum alerta) · 4/34/9.
Compacto: 2027 25 · 0/19/5 · 1/16/4; 2028 25 · 1/9/3 · 1/10/3; 2029 55 · 1/3/4 · 2/25/3; 2030 35 · 0/8/6 · 2/21/4;
2031 25 · 2/15/8 · 2/22/8.

Leitura: no completo a conclusão se mantém (≈2× o acaso, um pouco acima da referência em precisão, abaixo em
revocação). No compacto, com ~6 falhas por semente, o índice **não** supera a referência — a amostra é pequena demais
para avaliar o método, por isso a metodologia continua medida no perfil completo e a página "Revisões recomendadas"
mostra os dois resultados. Nenhum parâmetro foi ajustado. Ao final, o cenário padrão (compacto, semente 2026) foi
recarregado com `demo:seed --reset`.

