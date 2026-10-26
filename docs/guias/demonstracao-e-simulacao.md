# Demonstração e simulação

**Objetivo:** ter dados sintéticos coerentes para mostrar o sistema e uma "operação ao vivo" fictícia, sem nunca tocar
em dado real nem em contas.
**Quem usa:** quem opera o servidor, pela linha de comando (`php bin/fiscaliza`). Nada disso é acessível por HTTP.
Contratos dos comandos: [console](../referencia/codigo/cli/console.md); passo a passo: [operação](../operacao.md).

> **Distinção da telemetria real.** O simulador grava eventos **fictícios** pelos mesmos casos de uso da aplicação
> (`EventIngestor`, `AgentService`, `AlertService`) — por isso a tela reage como reagiria a uma telemetria de verdade —,
> mas não existe nenhum aparelho, rádio ou integração real. A tela mostra "Demonstração" no topo.

## Carregar a demonstração

```bash
docker compose exec app php bin/fiscaliza demo:seed --perfil=compacto
```

| Situação | Comportamento |
|---|---|
| Banco vazio | carrega: órgãos, bairros, equipamentos com **240 dias** de histórico, agentes, alertas reconciliados, **uma análise** e as **multas** |
| Já há equipamentos e sem `--reset` | recusa ("Use --reset…", saída 1) |
| `--reset` | exige `DEMO_ALLOW_RESET=true` **e** o marcador `dataset = demo-sintetico` em `APP_META`; apaga o domínio e recarrega |
| Perfil inválido | erro |

**Apaga com `--reset`:** multas e conferências, missões, análises, alertas e acompanhamentos, eventos, ordens,
equipamentos, agentes, bairros, órgãos, `APP_META`. **Preserva:** contas (`APP_USER`, `LOGIN_ATTEMPT`) e as execuções
importadas do Infosiga (`URBAN_*`, dados reais agregados) — [`DemoSeeder::wipe`](../referencia/codigo/demo/cenario-e-carga.md#demoseederwipe).
Roda com a identidade do **dono do esquema** (precisa apagar histórico que o usuário da aplicação não pode apagar).

| Perfil | Equipamentos | Agentes | Para quê |
|---|---:|---:|---|
| `compacto` (padrão, `DEMO_PROFILE`) | 17 | 7 | apresentação: cobre todas as categorias, situações e exemplos de manutenção ([ADR-0013](../adr/0013-perfil-compacto-de-demonstracao.md)) |
| `completo` | 48 | 21 | volume e metodologia da análise |

**Repetibilidade:** mesma semente (`SIM_SEED`) ⇒ mesmo cenário **relativo à data da carga** (as datas andam com o dia em
que se carrega). Casos curados: dois aparelhos silenciosos (sem sinal, sem falha confirmada), um concentrador com 25 min
de atraso, um evento atrasado permanente (só no histórico) e o "Hermes 06" em turno sem transmitir posição.

## Multas de demonstração

```bash
docker compose exec app php bin/fiscaliza demo:seed-multas
```

Acrescenta o cenário de multas (12 casos no compacto, 40 no completo) a uma demonstração **já carregada**, sem apagar
nada; exige o marcador sintético; a mesma versão (`multas-v3`) não é carregada duas vezes; versões anteriores são
**atualizadas no lugar**, com cada mudança no histórico do registro; multas de semente sem marcador desta versão →
conflito, nada alterado. `demo:seed` já inclui as multas. Casos: [multas de demonstração](../referencia/codigo/demo/multas-de-demonstracao.md).

## Simulador

```bash
docker compose --profile simulador up -d simulator
```

Ou um ciclo por vez (cron, hospedagem sem processo permanente): `php bin/fiscaliza sim:tick`.

Cada ciclo ([`Simulator::tick`](../referencia/codigo/demo/simulador.md#simulatortick)): batimento de sinal dos
equipamentos (menos os silenciosos); falha rara (0,04% por ciclo × propensão) e recuperação (0,6%); passos dos móveis e
dos agentes em atividade (até ~1 km da base); pausas e retornos de agentes; a cada 12 ciclos, **uma entrega repetida**
(descartada e contada) e **um evento atrasado 20 min** (fica só no histórico); reconciliação de alertas. Situações
administrativas definidas por pessoa prevalecem sobre a falha simulada.

**Trava:** `var/simulador.lock` com `flock`: um segundo ciclo ao mesmo tempo é **ignorado** ("Ciclo ignorado: outro
ciclo do simulador ainda está em execução"), saída 0. Vale para processos que compartilham o mesmo `var/` (mesmo
servidor ou volume). Banco fora do ar: `sim:run` registra e tenta no próximo ciclo.

**O que o simulador nunca toca:** missões de Planejar reforço (`URBAN_MISSION`), contas, multas e dados do Infosiga.

## Ações de demonstração na interface

| Ação | Onde | Efeito |
|---|---|---|
| Simular falha | ficha do equipamento ou ficha resumida | evento `FALHA` fictício pelo ingestor |
| Simular recebimento de posição | ficha do agente | posição fictícia perto da base, identificada no histórico |

## Verificação e limites

Testes: `DemoProfileTest`, `CentralAndAnalysisTest::testDemoSeedAnalysisIsReproducibleAndTemporallySeparated`,
`OperationsTest` (duplicados, atrasos, trava), `PlanningTest::testSimulatorTicksNeverTouchAConfirmedMission`,
`FinesTest::testNonDestructiveSeedAddsTheScenarioOnceAndRefusesUnmarkedDatabases`
([catálogo](../referencia/codigo/testes/casos.md)). Avaliação da análise em várias sementes:
`scripts/avaliar-sementes.php` (**substitui** os dados da demonstração). Limites: os mecanismos do gerador são
plausíveis, mas inventados.

## Relacionados

[Operação](../operacao.md) · [Cenário e carga](../referencia/codigo/demo/cenario-e-carga.md) ·
[Simulador](../referencia/codigo/demo/simulador.md)
