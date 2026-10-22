# Evidência — Planejar: mapa de calor dos quadrados de 2 km (22/10/2026)

Pedido: no passo 1 de /planejar ("Onde reforçar"), ver de relance onde a previsão é maior ou menor. Ambiente: Windows 11
+ Docker Desktop, projeto Compose isolado `pf-heat` (volume próprio, porta 8098), PHP 8.5.11 e Firebird 5.0.4, cenário
de demonstração + `urban:import`. Nada foi publicado.

## O que mudou na tela

- Os 64 quadrados da grade são pintados pelo valor esperado na semana, em 5 faixas claro → amarelo → laranja →
  vermelho, semitransparentes (as ruas continuam legíveis). O maior da semana (Centro, 3,8) fica vermelho forte; os de
  valor perto de zero, no tom mais claro.
- Faixa = raiz da posição do valor entre o mínimo e o máximo da semana. A previsão é muito concentrada (35 dos 64
  quadrados não tiveram registro no ano e ficam perto de zero; o 1º vale mais que o dobro do 2º): em escala linear quase tudo ficaria claro, e quantis
  pintariam do mesmo vermelho quadrados de 0,5 e de 3,8.
- Os prioritários seguem numerados, clicáveis (abrem o passo 2) e com contorno verde; o escolhido, contorno escuro.
  Os demais mostram, ao passar o mouse ou tocar, "Nova Marília · Setor sudeste: 0,8 sinistros esperados na semana".
- Legenda abaixo do mapa: barra "menos … mais" e "Cor = sinistros esperados na semana, por quadrado de 2 km".
  O `aria-label` do mapa descreve o mapa de calor e diz o quadrado mais vermelho e seu valor.
- Carga `#plan-data`: `grid.all` com os 64 quadrados (`rank`, `sector_id`, `expected`, limites, texto do tooltip),
  vindos de `UrbanRepository::allCells`. A lista continua com os N prioritários de `topCells`.

Capturas (1440 × 900 e 390 × 844, movimento reduzido, tooltip aberto): `capturas-2026-10-22-mapa-de-calor/`.

## Verificação

| O quê | Resultado |
|---|---|
| `composer check` no contêiner (estilo, PHPStan, PHPUnit) | 0 de 111 arquivos · 0 erros · **209 testes, 1166 asserções, OK** |
| Teste novo `PlanningHttpTest::testHeatMapCarriesTheWholeGridWhileTheListKeepsOnlyThePriorities` | 64 quadrados iguais a `URBAN_CELL` (ordem, setor, valor); clicáveis e lista só com os 2 pedidos |
| `npm run build` | sem erros |
| Edge headless via playwright-core (1440 e 390 px) | 64 formas; 1 na faixa vermelha; tooltip ao passar o mouse e ao tocar; sem erro no console; sem rolagem horizontal |
| Sem JavaScript | 200, os 4 cartões da lista presentes |

Não rodado: `scripts/verificacao-interface/verify.cjs` e `captures.cjs` completos (as capturas acima vieram de um
script avulso com o mesmo Playwright); leitores de tela reais.
