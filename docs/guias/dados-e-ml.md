# Dados reais e ML (Planejar reforço)

**Objetivo:** entender de onde vêm os únicos dados reais do sistema, como viram a previsão da semana e o que foi só
experimentado.
**Quem usa:** quem opera o servidor (`urban:import`, `urban:update`) e quem refaz o treino (`ml/`, fora da aplicação).
Método e números para quem lê a tela: [planejamento por setores](../planejamento-por-setores.md); código do treino:
[referência](../referencia/codigo/ml/treino-e-experimento.md); arquivos: [`ml/README.md`](../../ml/README.md).

## O que é real

Contagens **agregadas** de sinistros fatais e não fatais em vias urbanas de Marília, com coordenada no retângulo de
estudo, do arquivo aberto do **Infosiga-SP / Detran-SP** (licença CC BY 4.0, [licenças e fontes](../licencas-e-fontes.md)).
Snapshot de 20/10/2026 (SHA-256 `009481bd…c9c8`): 12.725 registros de Marília → **8.419 elegíveis** (2019 a
setembro/2026). Nenhuma vítima, veículo ou linha bruta é guardada — só contagens por dia e área.

## Do arquivo à tela

| Etapa | Onde | Saída |
|---|---|---|
| 1. Preparar contagens (uma vez, Python) | `ml/marilia-setores/preparar_dados.py` | `contagens_diarias.csv` (dia × setor, zeros incluídos), manifesto com a auditoria dos filtros, `setores.geojson` |
| 2. Treinar e avaliar (Python) | `treino.py` | escolha no ajuste de 2024 (**referência histórica** venceu); teste de 2025 com modelo congelado; previsão da semana; modelo portátil e vetores de paridade |
| 3. Experimento da grade (Python) | `ml/marilia-grade-2km/experimento.py` | contagens por quadrado e dia; teste único de 2026: grade com média simples muito melhor que os setores; Poisson sem ganho |
| 4. Importar (PHP) | `php bin/fiscaliza urban:import` | `URBAN_RUN`, `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_CELL` — valida o pacote e **recalcula as métricas** do retrospecto (recusa se não baterem) |
| 5. Atualizar (PHP, agendado) | `php bin/fiscaliza urban:update` | snapshot mensal e previsão semanal |

**Candidatos × método em uso.** Setores: Poisson regularizado (alpha 0,1) foi **candidato** e perdeu no ajuste; a tela
usa a referência `(n52 + 1) ÷ 53`. Grade: Poisson experimentado e descartado; a tela usa `(n52 + 0,1) ÷ 52,1`. O Poisson
dos setores continua calculado nos dados (`EXPECTED_ML`) só para comparação.

**Janelas temporais.** Cada emissão usa só dados anteriores ao 1º dia do mês, **dois meses antes** (hipótese de atraso
de publicação, não observada): em 20/10/2026, dados até 31/07/2026. Ajuste em 2024, teste em 2025 (já conhecido), teste
da grade em 2026 (já conhecido).

## Importação (`urban:import`)

```bash
docker compose exec app php -d memory_limit=512M bin/fiscaliza urban:import
```

Padrão: `ml/marilia-setores/runs/primeiro-treino-verificado` + `ml/marilia-grade-2km/dados/contagens_grade.csv`.
Idempotente (repetir não duplica; a grade é acrescentada uma vez a uma execução existente). Pacote inválido → nada
gravado. Opções: `--dir`, `--setores`, `--manifesto`, `--grade`, `--sem-grade` ([console](../referencia/codigo/cli/console.md)).
Código: [`UrbanImporter`](../referencia/codigo/application/importacao-urbana.md).

## Atualização automática (`urban:update`)

```bash
docker compose --profile atualizador up -d atualizador
```

A cada 6 h (idempotente):

1. **Mensal** — se ainda não houve download neste mês, baixa `INFOSIGA_URL` (só HTTPS, até 512 MiB), lê só os
   `sinistros_*.csv`, aplica os mesmos filtros e guarda **só as contagens por quadrado e dia dos últimos 12 meses**
   (`URBAN_SNAPSHOT`, `URBAN_DAILY`); o ZIP é apagado; arquivo igual (SHA-256) não é gravado de novo; arquivo quebrado,
   sem as colunas ou mais antigo é recusado sem gravar.
2. **Semanal** — se a segunda-feira atual não tem previsão com o arquivo mais recente, recalcula setores e quadrados e
   grava uma execução comum, marcada "atualização automática". Sem 52 semanas cobertas até o corte, não calcula e diz o
   motivo. Falha no download não impede esta etapa.

Método, modelo e avaliação **não mudam sozinhos**. Código: [`UrbanUpdater`](../referencia/codigo/application/atualizacao-urbana.md);
opções `--forcar-download`, `--arquivo`, `--semana`.

| Falha ou defasagem | O que acontece |
|---|---|
| Site fora do ar | saída 1; a previsão semanal roda com o que já está guardado |
| Arquivo inválido | recusado, nada gravado, saída 1 |
| Mesmo arquivo do mês anterior | conta como a checagem do mês (`infosiga_ultima_checagem`), sem snapshot novo |
| Semana sem previsão nova | a tela avisa "semana encerrada" e não confirma missões |

## Paridade PHP × Python

O Poisson portátil (`exp(intercepto + Σ c·(x − média)/escala)`) é recalculado em PHP e conferido contra os 20 vetores de
`vetores_paridade.json`: `ml/marilia-setores/verificar_paridade.php` (tolerância 1e-10) e
`UrbanUpdaterTest::testPoissonFormulaMatchesThePythonParityVectors`.

## Testes e evidências

`UrbanImportTest` (8; inclui os 28 arquivos do pacote byte a byte), `UrbanUpdaterTest` (6), `PlanningTest` (importação),
`ml/marilia-setores/tests/test_protocolo.py` (4) — [catálogo](../referencia/codigo/testes/casos.md). Evidência:
[20/10/2026](../evidencias/2026-10-20-planejar-reforco.md) (download real idêntico ao do pacote; o cálculo em PHP reproduziu a
execução de referência). Esta documentação não baixou nem importou nada.

## Limites

Snapshot revisado (sem data de primeira publicação), atraso assumido, cobertura desigual entre anos, recortes
experimentais (não bairros), testes retrospectivos já conhecidos, nenhuma evidência causal sobre o efeito de agentes.

## Relacionados

[Planejar reforço](planejar-reforco.md) · [ADR-0016](../adr/0016-planejamento-preventivo-por-setores.md) ·
[Dicionário: `URBAN_*`](../referencia/dicionario-de-dados.md#tabelas-do-planejamento-urban_)
