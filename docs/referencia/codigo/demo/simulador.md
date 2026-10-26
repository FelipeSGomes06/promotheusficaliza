# Simulador de campo

<!-- fontes: src/Demo/Simulator.php -->

Cada ciclo gera observações **fictícias** e as entrega pelos **mesmos casos de uso de ingestão**, persistindo no
Firebird — nada é simulado só na interface. É a fonte da "atualização em tempo quase real" da Visão geral. Rodado por
`php bin/fiscaliza sim:run` (serviço `simulator` do Compose, perfil `simulador`) ou `sim:tick` (cron), sempre com a trava
de [`Console::exclusiveTick`](../cli/console.md#consoleexclusivetick). Ciclos numerados + semente fixa (`SIM_SEED`) =
sequência de decisões reproduzível.

| Constante (privada) | Valor | Papel |
|---|---|---|
| `FAILURE_PER_TICK` | 0,0004 | chance por ciclo de um operacional falhar, × propensão do aparelho |
| `RECOVERY_PER_TICK` | 0,006 | chance por ciclo de um em falha voltar |
| `MAX_RADIUS` | 0,009° (~1 km) | raio máximo do passeio em torno da base |

## `Simulator::tick`

`tick(): array<string, int|string>` — devolve `{ciclo, sinais, aplicados, historico, duplicados, posicoes}`.

1. Número do ciclo = `APP_META.sim_tick + 1`; gerador `Xoshiro256**` semeado por `sha256("sim:<semente>:<ciclo>")`.
2. **Sinais** (uma transação): batimento para cada equipamento que espera sinal, exceto os silenciosos do cenário; o
   concentrador atrasado recebe o sinal com 25 min de defasagem.
3. **Telemetria**: operacional pode falhar (`falha detectada pela telemetria simulada`); em falha pode voltar
   (`equipamento voltou a responder`). Pelo [`EventIngestor`](../application/ingestao.md#eventingestorequipmentstatus):
   situações administrativas prevalecem.
4. **Móveis** (veículo, patinete) operacionais dão um passo; histórico de posição a cada 6 ciclos.
5. **Agentes**: em atividade podem entrar em pausa (0,3%); em pausa podem voltar (3%), por
   `AgentService::changeStatus` com origem `SIMULADOR`. Em atividade dão um passo — **exceto Hermes 06**, cujo rádio
   simulado não transmite (fica "em turno sem posição recente"). Em pausa ninguém transmite.
6. A cada 12 ciclos: **reenvia** o último evento do simulador (duplicata, que não altera nada) e entrega um evento de
   situação **atrasado 20 minutos** (vira `HISTORICO` porque o estado atual é mais recente).
7. Numa transação: reconcilia os alertas e grava `sim_tick` e `last_sim_tick_at`.

`DuplicateEvent` numa entrega conta como duplicata.

| Método | Contrato |
|---|---|
| `Simulator::__construct` | `(Services $services, int $seed)`. |
| `Simulator::tick` | ver acima. |
| `Simulator::step` | `private (Randomizer $rng, mixed $lat, mixed $lng, float $baseLat, float $baseLng): array{0: string, 1: string}` — passo aleatório de até ±0,0006° preso a `MAX_RADIUS` da base (posições sempre plausíveis na área urbana). |

Duas funções anônimas internas: `$count` (soma o resultado na estatística) e `$deliver` (chama a entrega e trata
`DuplicateEvent`).

Testes: `OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock` (trava do `sim:tick`) e
`PlanningTest::testSimulatorTicksNeverTouchAConfirmedMission` (40 ciclos não alteram uma missão confirmada, só a posição
do agente). Duplicatas e atrasos são testados direto na ingestão (`OperationsTest::testDuplicateEventIsIgnoredAndCounted`,
`testLateEventGoesToHistoryWithoutRegressingCurrentState`), não pelo simulador. Logs: uma linha JSON por ciclo
(`docker compose logs -f simulator`).

## Relacionados

[Ingestão](../application/ingestao.md) · [Operação: cron](../../../operacao.md#cron-hospedagem-sem-processo-permanente) ·
[ADR-0004](../../../adr/0004-atualizacao-por-consulta-periodica.md)
