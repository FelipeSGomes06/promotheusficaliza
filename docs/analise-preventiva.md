# Análise preventiva (parte 3)

**Pergunta operacional:** quais equipamentos merecem revisão nos próximos **7 dias**, e por quê?

**Saída:** classe, índice 0–100 ("prioridade sugerida" na tela), fatores com pontos, sinais administrativos, ação
recomendada, dias de histórico, data do cálculo, método e parâmetros (tela "Revisões recomendadas", rota
`/antecipacao`, e ficha do equipamento). É apoio à decisão: nada muda automaticamente.

| Classe (banco) | Na tela | Quando |
|---|---|---|
| `ALTA` | Revisar primeiro | Operacional, histórico ≥ 60 dias, índice ≥ limiar ajustado. |
| `MODERADA` | Acompanhar | Operacional, histórico ≥ 60 dias, índice ≥ metade do limiar. |
| `BAIXA` | Sem destaque | Operacional, histórico ≥ 60 dias, índice abaixo disso. |
| `INSUFICIENTE` | Histórico curto | Operacional com menos de 60 dias de histórico: pontua, não classifica. |
| `CORRETIVA` | Já indisponível | Mau funcionamento, manutenção **ou furto** (em serviço, mas indisponível): a ação é corretiva ou reposição, não antecipação. Sem índice. |
| `NAO_AVALIADO` | Não avaliado | Fora de serviço (não instalado ou desativado). Sem índice. Antes de 18/10/2026 aparecia, por engano, como `BAIXA`. |

Só as três primeiras entram nas métricas de avaliação.

> O índice é **heurístico, não probabilidade calibrada**. O histórico é **sintético**. A avaliação mede coerência do
> método no cenário gerado — não acurácia em equipamentos reais.

## Dados usados e separação temporal

Em cada instante de cálculo (o "agora" ou um corte do passado), as características usam só o que o sistema **já
sabia** naquele instante:

- **Eventos de situação** aplicados (falhas e retornos) com `RECEIVED_AT` anterior ao corte. Um evento que *ocorreu*
  antes do corte mas foi *recebido* depois não entra (o sistema ainda não sabia dele). Dentro do que já era conhecido,
  a ordem é a da ocorrência (`OCCURRED_AT`). Eventos `HISTORICO` (atrasados ou sobrepostos) não mudaram o estado e não
  entram na linha do tempo.
- **Ordens de manutenção** pelos instantes do sistema (abertura e encerramento são gravados quando acontecem).
- **Periodicidade preventiva vigente no corte**, reconstruída pelo histórico: o valor "antes" da primeira alteração
  registrada depois do corte; sem alteração posterior, o valor atual. Toda alteração passa por
  `EquipmentService::update`, que grava antes/depois.
- **População do corte:** equipamentos cadastrados antes dele e não arquivados até ele (um arquivamento posterior não
  apaga o equipamento dos cortes antigos).
- **Rótulo** (para avaliar): houve falha nos 7 dias seguintes ao corte, pela **ocorrência** — no momento da avaliação
  tudo isso já foi recebido.

Limites que permanecem: alterações feitas fora da aplicação (SQL direto do dono do esquema) não deixam histórico e não
são reconstruídas; só a periodicidade e a data de cadastro do cadastro entram no cálculo (outros campos não são usados);
no cenário sintético, eventos gerados pela carga têm recebimento igual à ocorrência, exceto os atrasos intencionais.
Testes: `CentralAndAnalysisTest::testLaterIntervalChangeDoesNotRewriteAPastCutoff`,
`testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff` e `testEquipmentArchivedLaterStillExistedAtEarlierCutoffs`.

## Método `atencao-v1`

| Fator | Pontos |
|---|---|
| Falhas nos últimos 30 dias (recorrência recente) | 18 cada, máx. 36 |
| Falhas entre 31 e 180 dias (propensão do próprio aparelho) | 7 cada, máx. 35 |
| Dias desde a última preventiva ÷ intervalo previsto | ≥ 0,8 → 6 · ≥ 1,0 → 15 · ≥ 1,5 → 25 |
| Sem periodicidade e > 120 dias sem manutenção concluída | 10 |

- Classes conforme a tabela acima (alta ≥ limiar; moderada ≥ metade; baixa abaixo).
- **Ordem preventiva vencida** aparece como sinal administrativo separado (não soma pontos além do fator de atraso).

Implementação: [`AttentionModel`](../src/Domain/AttentionModel.php) (regras puras, testadas) e
[`AnalysisService`](../src/Application/AnalysisService.php) (dados, cortes, gravação).

## Ajuste e avaliação

- Cortes semanais. **Ajuste:** de 150 a 63 dias atrás — só aqui o limiar de "alta" é escolhido entre {25, 35, 45, 55}
  (maior F1; empate: menor limiar; F1 indefinido nunca vence um valor definido; sem nenhuma falha na janela de ajuste,
  todos empatam e vale o menor, com aviso registrado). **Avaliação:** de 56 a 7 dias atrás, com o limiar já fixado. A
  janela de rótulo do último corte de ajuste termina quando começa a avaliação.
- Rótulo: houve ao menos uma falha nos 7 dias seguintes ao corte. Entram só pares classificáveis (operacional no
  corte e com histórico mínimo).
- Referência simples: "falhou nos últimos 30 dias". Também é mostrada a prevalência (o que um sorteio acertaria).
- Cada execução grava parâmetros, período, quantidade de eventos e a avaliação (`ANALYSIS_RUN`), e é reproduzível:
  mesmos dados + mesmo instante → mesmo resultado (teste `CentralAndAnalysisTest`).

**Convenções das métricas** (`AttentionModel::metrics`, calculadas direto das contagens, sem arredondar antes; a tela
arredonda): precisão = TP/(TP+FP), indefinida (`null`, "—") sem nenhum alerta; revocação = TP/(TP+FN), indefinida sem
nenhuma falha; F1 = 2TP/(2TP+FP+FN), indefinido só sem falhas **e** sem alertas; prevalência indefinida sem pares.
Errar sem acertar (ex.: TP=0, FP=1, FN=1) dá F1 = 0 — desempenho nulo, que é diferente de ausência de dados. Antes de
18/10/2026, F1 vinha de precisão e revocação arredondadas e virava `null` nesse caso (achado OP-05).

## Resultado honesto

Uma versão preliminar (sem a janela de 180 dias) foi avaliada na semente 2026 durante o desenvolvimento: precisão
0,077 e revocação 0,071. Como o método foi revisto depois de ver esse resultado, a verificação final do método
congelado foi feita em **cinco sementes inéditas**. Depois da correção da separação temporal (OP-04/OP-05), as mesmas
sementes foram reavaliadas em 18/10/2026 ([evidência](evidencias/2026-10-18-avaliacao-sementes.md)):

| | Precisão | Revocação |
|---|---|---|
| Índice ("revisar primeiro") | **8,9%** (11/123) | **16,7%** (11/66) |
| Referência "falhou nos últimos 30 dias" | 6,8% (14/206) | 21,2% (14/66) |
| Prevalência (acaso) | 3,9% (66/1.696 pares) | — |

Antes da correção: 8,2% e 15,2% para o índice, 6,3% e 19,7% para a referência (1.681 pares). A mudança vem da
reconstrução temporal (menos informação "do futuro" em cada corte), não de ajuste do método. Leitura: o índice
concentra falhas cerca de 2× acima do acaso com menos alertas que a referência, mas perde a maioria das falhas e o
limiar escolhido varia entre cenários (25 a 55) — amostras pequenas (~13 falhas por janela). Serve para **priorizar
uma rota semanal de inspeção**, não para garantir prevenção.

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
