# Demonstração (`src/Demo/`)

Dados **sintéticos** e a "operação ao vivo" fictícia, gravados pelos mesmos casos de uso da aplicação. Guia:
[demonstração e simulação](../../docs/guias/demonstracao-e-simulacao.md).

| Arquivo | Responsabilidade | Referência |
|---|---|---|
| `DemoScenario.php` | órgãos, bairros, bases, equipamentos e agentes; perfis compacto (17/7) e completo (48/21) | [cenário e carga](../../docs/referencia/codigo/demo/cenario-e-carga.md#demoscenario) |
| `DemoSeeder.php` | carga com 240 dias de histórico reproduzível por semente, apaga só o domínio no `--reset` | [cenário e carga](../../docs/referencia/codigo/demo/cenario-e-carga.md#demoseeder) |
| `FineScenario.php` | casos curados de multas (12/40), versão `multas-v3`, atualização no lugar de versões anteriores | [multas de demonstração](../../docs/referencia/codigo/demo/multas-de-demonstracao.md) |
| `Simulator.php` | um ciclo de sinais, falhas, posições, pausas, duplicata e atraso | [simulador](../../docs/referencia/codigo/demo/simulador.md) |
