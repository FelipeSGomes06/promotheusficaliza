# Revisões recomendadas

**Objetivo:** saber quais equipamentos merecem uma inspeção preventiva nos próximos **7 dias** e por quê.
**Como acessar:** menu **Revisões recomendadas**; `/antecipacao`. Também na ficha do equipamento (bloco "Revisão
recomendada"). Qualquer conta ativa; qualquer conta pode **Recalcular agora**.
**Antes de começar:** uma execução gravada (a carga `demo:seed` já roda uma; `php bin/fiscaliza analysis:run` grava
outra).

> **Isto não é o Planejar reforço.** Aqui é uma **heurística** (`atencao-v1`) sobre o histórico **sintético** de falhas
> e manutenções dos equipamentos. O [Planejar reforço](planejar-reforco.md) é outra coisa: onde concentrar agentes,
> com contagens **reais** agregadas de sinistros. A "prioridade sugerida" (0–100) **não é a chance de falhar**.

## O que aparece na tela

| Área | Significado | Regra |
|---|---|---|
| **Revisar primeiro** (`ALTA`) | operacional, histórico ≥ 60 dias, índice ≥ limiar ajustado | [`AttentionModel::assess`](../referencia/codigo/domain/atencao-preventiva.md#attentionmodelassess) |
| **Acompanhar** (`MODERADA`) | índice ≥ metade do limiar | idem |
| Sem destaque (`BAIXA`) | abaixo disso | idem |
| Seções recolhidas | **Já indisponível** (`CORRETIVA`: falha, manutenção ou furto — ação corretiva ou reposição, sem índice), **Histórico curto** (`INSUFICIENTE`, < 60 dias: pontua, não classifica), **Não avaliado** (não instalado, desativado) | idem |
| "Por quê" de cada item | fatores com pontos: falhas nos últimos 30 dias (18 cada, máx. 36), falhas entre 31 e 180 dias (7 cada, máx. 35), atraso da preventiva (≥ 0,8 → 6 · ≥ 1,0 → 15 · ≥ 1,5 → 25 do intervalo), sem periodicidade e > 120 dias sem manutenção (10); preventiva vencida como sinal administrativo separado | [método](../analise-preventiva.md#método-atencao-v1) |
| "O que fazer" | ALTA "Agendar inspeção preventiva nos próximos 7 dias." · MODERADA "Incluir na próxima rota de inspeção…" · BAIXA "Manter o plano atual." | idem |
| Ordem que já cuida do equipamento | a em execução ou a primeira da fila, lida agora | `OperationsController::analysis` |
| Retrospecto (fim da página) | precisão, revocação e F1 na janela de avaliação, comparados com "falhou nos últimos 30 dias" e com a prevalência | [`AttentionModel::metrics`](../referencia/codigo/domain/atencao-preventiva.md#attentionmodelmetrics) |

## Recalcular

1. **Recalcular agora**.

**Resultado esperado:** "Análise recalculada (execução #N)"; uma nova linha em `ANALYSIS_RUN` (método, parâmetros,
período, eventos usados, avaliação) e um resultado por equipamento em `ANALYSIS_RESULT`. Nada muda nos equipamentos ou
ordens: é apoio à decisão. Mesmos dados e mesmo instante → mesmo resultado (reproduzível).

## Como o limiar e o retrospecto são calculados

Cortes semanais. **Ajuste** de 150 a 63 dias atrás: o limiar de "alta" é escolhido entre {25, 35, 45, 55} pelo maior F1
(empate: menor; F1 indefinido nunca vence). **Avaliação** de 56 a 7 dias atrás, com o limiar fixado. Em cada corte, só
entra o que o sistema **já sabia** (eventos recebidos antes do corte; periodicidade vigente no corte; equipamentos
cadastrados antes e não arquivados até ele). Rótulo: ao menos uma falha nos 7 dias seguintes. Detalhes, convenções de
`null` nas métricas e o resultado em cinco sementes: [análise preventiva](../analise-preventiva.md).

## Estados e exceções

| Situação | O que a tela informa |
|---|---|
| Nenhuma execução | estado vazio, com o convite a recalcular |
| Sem nenhuma falha na janela de ajuste | todos empatam, vale o menor limiar, com aviso registrado na execução |
| Métrica indefinida (sem alertas ou sem falhas) | "—", nunca 0 |

## Como funciona no código

`GET /antecipacao` → `OperationsController::analysis` → [`AnalysisRepository`](../referencia/codigo/infrastructure/repositorio-analise.md)
(última execução). `POST /antecipacao/recalcular` → `OperationsController::recalculate` →
[`AnalysisService::run`](../referencia/codigo/application/analise-preventiva.md#analysisservicerun) →
[`load`](../referencia/codigo/application/analise-preventiva.md#analysisserviceload) (eventos `SITUACAO` aplicados,
ordens, histórico de periodicidade) → [`features`](../referencia/codigo/application/analise-preventiva.md#analysisservicefeatures)
por corte → `AttentionModel::assess`/`metrics` → `ANALYSIS_RUN` + `ANALYSIS_RESULT`
([dicionário](../referencia/dicionario-de-dados.md#analysis_run-e-analysis_result)) → 303.

## Verificação e limites

Testes: `AttentionModelTest` (6), `CentralAndAnalysisTest` (cortes temporais e reprodutibilidade)
([catálogo](../referencia/codigo/testes/casos.md)); avaliação em sementes por `scripts/avaliar-sementes.php`
([evidência](../evidencias/2026-10-18-avaliacao-sementes.md)). Resultado: o índice concentra falhas cerca de 2×
acima do acaso com menos alertas que a referência, mas perde a maioria das falhas; o perfil compacto é pequeno demais
para avaliar o método. Histórico sintético, pesos inventados, sem calibração probabilística ([ADR-0007](../adr/0007-analise-preventiva-heuristica.md)).

## Relacionados

[Manutenção](manutencao.md) · [Equipamentos](equipamentos.md) · [Planejar reforço](planejar-reforco.md)
