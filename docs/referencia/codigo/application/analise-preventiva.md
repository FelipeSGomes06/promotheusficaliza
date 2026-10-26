# Execução da análise preventiva

<!-- fontes: src/Application/AnalysisService.php -->

`AnalysisService` executa a análise de **atenção preventiva para 7 dias** (Revisões recomendadas) e a **avaliação
retrospectiva com separação temporal**. A pontuação vem de [`AttentionModel`](../domain/atencao-preventiva.md); aqui
ficam os dados, os cortes e a gravação. Metodologia e resultados: [análise preventiva](../../../analise-preventiva.md).

Disparo: botão **Recalcular agora** (`POST /antecipacao/recalcular`, operador), `php bin/fiscaliza analysis:run` (cron
sugerido às 3h15) e o fim do `demo:seed`. Cada execução grava uma linha em `ANALYSIS_RUN` e um resultado por
equipamento em `ANALYSIS_RESULT`; a tela mostra sempre a mais recente. Nada muda automaticamente no inventário.

| Constante | Valor | Janela |
|---|---|---|
| `FIT_FROM_DAYS` / `FIT_TO_DAYS` | 150 / 63 | **ajuste**: cortes semanais de 150 a 63 dias atrás; só aqui o limiar de `ALTA` é escolhido |
| `EVAL_FROM_DAYS` / `EVAL_TO_DAYS` | 56 / 7 | **avaliação**: cortes semanais de 56 a 7 dias atrás, com o limiar fixado |

A janela de rótulo (7 dias) do último corte de ajuste (63 dias) termina quando começa a avaliação (56 dias).

## `AnalysisService::run`

`run(?int $actorId): array{id: int, summary: array<string, int>}`

1. [`load`](#analysisserviceload) com o instante atual; população = todos os equipamentos, **inclusive arquivados**
   (quem existia em cada corte).
2. Ajuste: para cada limiar de `THRESHOLD_GRID` calcula as métricas nos pares da janela de ajuste; escolhe o de **maior
   F1**; empate → o menor limiar; F1 indefinido (`null`) nunca vence um definido. Sem nenhuma falha na janela, todos
   empatam e vale o menor, com aviso em `fit_note`.
3. Avaliação na janela de avaliação com o limiar escolhido e, para comparação, a referência simples "falhou nos
   últimos 30 dias" (`baseline_recent_failure`).
4. Para cada equipamento **não arquivado**, características no instante atual e `AttentionModel::assess`.
5. Numa transação: `ANALYSIS_RUN` (método, instante, horizonte, período dos dados, eventos usados, parâmetros, avaliação
   em JSON, autor) e os `ANALYSIS_RESULT`.
6. Devolve o ID e a contagem por classe.

A avaliação grava `note` dizendo que o histórico é **sintético** e mede coerência do método no cenário, não acurácia
real. Mesmos dados + mesmo instante → mesmo resultado.

## `AnalysisService::load`

`load(DateTimeImmutable $until): list<array>` — lê os eventos de situação de equipamentos **recebidos** até `$until`
(`EventRepository::equipmentStatusHistory`), todas as ordens e as alterações de periodicidade
(`equipmentIntervalChanges`) e monta os índices de [`index`](#analysisserviceindex). Precisa ser chamado antes de
`features` (senão `LogicException`).

## `AnalysisService::features`

`features(array $e, DateTimeImmutable $cutoff): ?array` — características de um equipamento num corte usando **só o
que o sistema já sabia** antes dele; `null` se o equipamento ainda não existia ou já estava arquivado no corte.

- Situação no corte: parte do `de` do primeiro evento e aplica, em ordem de ocorrência, os eventos `APLICADO`
  ocorridos **e recebidos** antes do corte. Um evento que ocorreu antes mas chegou depois não entra.
- Falhas conhecidas (ocorridas e recebidas antes do corte) nas janelas de 30 e 31–180 dias.
- Última preventiva e última manutenção concluídas (pelo `CLOSED_AT`); sem preventiva, conta desde o cadastro.
- Periodicidade **vigente no corte** ([`intervalAt`](#analysisserviceintervalat)).
- Preventiva vencida: ordem preventiva aberta antes do corte, ainda não encerrada nele, com vencimento anterior à data
  local do corte.

Testes: `CentralAndAnalysisTest::testLaterIntervalChangeDoesNotRewriteAPastCutoff`,
`testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff`, `testEquipmentArchivedLaterStillExistedAtEarlierCutoffs`.

## `AnalysisService::intervalAt`

`private intervalAt(array $e, int $t): ?int` — periodicidade vigente no instante `$t`: o valor "antes" da primeira
alteração registrada a partir de `$t`; sem alteração posterior, o valor atual. Funciona porque toda mudança da coluna
passa por `EquipmentService::update`, que grava antes/depois. Alterações feitas por SQL direto não são reconstruídas.

## `AnalysisService::index`

`private index(array $statusEvents, array $orders, array $intervalChanges): void` — zera e preenche: linha do tempo de
situação (só eventos `APLICADO` com `para`), falhas, preventivas e manutenções concluídas, preventivas abertas com
vencimento e alterações de periodicidade (valor anterior e instante de recebimento).

| Método | Contrato |
|---|---|
| `AnalysisService::__construct` | `(Database, AnalysisRepository, EquipmentRepository, EventRepository, MaintenanceRepository, Clock)`. |
| `AnalysisService::pairs` | `private (array $equipment, array $cutoffs, int $threshold, bool $baseline = false): list<array{flag, failed}>` — pares classificáveis (operacional no corte e histórico mínimo); `failed` = falha **ocorrida** nos 7 dias seguintes ao corte (na avaliação tudo isso já foi recebido); `flag` = classe `ALTA` ou, na referência, falha nos últimos 30 dias. |
| `AnalysisService::cutoffs` | `private (DateTimeImmutable $now, int $fromDays, int $toDays): list<DateTimeImmutable>` — cortes de 7 em 7 dias, do mais recente ao mais antigo. |

Testes: `CentralAndAnalysisTest`, `AttentionModelTest`; medição em várias sementes: `scripts/avaliar-sementes.php`
([evidência](../../../evidencias/2026-10-18-avaliacao-sementes.md)).

## Relacionados

[Repositório de análise](../infrastructure/repositorio-analise.md) · [Guia: Revisões recomendadas](../../../guias/revisoes-recomendadas.md)
