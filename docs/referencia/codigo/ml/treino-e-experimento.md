# Treino dos setores e experimento da grade

<!-- fontes: ml/marilia-setores/preparar_dados.py ml/marilia-setores/treino.py ml/marilia-setores/tests/test_protocolo.py ml/marilia-setores/verificar_paridade.php ml/marilia-grade-2km/experimento.py -->

Código Python **fora da aplicação**: nada aqui roda no servidor web nem no Compose. Gera arquivos que a aplicação importa
(`php bin/fiscaliza urban:import`) e que a atualização automática (`urban:update`) reproduz em PHP. Explicação do método
para quem usa: [planejamento por setores](../../../planejamento-por-setores.md); pastas e dados:
[`ml/README.md`](../../../../ml/README.md).

**O que é real e o que é fictício.** As contagens são **reais e agregadas** (Infosiga-SP/Detran-SP, CC BY 4.0,
snapshot de 20/10/2026, SHA-256 `009481bd…c9c8`): número de sinistros registrados por dia e setor (ou quadrado). Nenhuma
pessoa, veículo ou linha bruta sai do ZIP. Agentes e missões da tela são fictícios.

**Método efetivamente usado na tela.** A **referência histórica** (média das 52 semanas). O Poisson foi treinado e
avaliado, perdeu na escolha feita **antes** do teste e aparece só como comparação experimental nos dados importados. Na
grade de 2 km, o Poisson foi apenas experimentado e descartado (ver abaixo).

## `ml/marilia-setores/` — pacote de treino (20/10/2026)

Mantido **byte a byte**: `UrbanImportTest::testReferenceCopyIsByteIdenticalToItsManifest`
confere os 26 arquivos contra `ENTREGA-MANIFESTO.json`. Não edite esses arquivos; uma mudança exige novo pacote e
novo manifesto. Os documentos `00-…` a `05-…` são o planejamento e o contrato originais (histórico).

### `preparar_dados.py`

Uso: `python preparar_dados.py [--zip fonte/dados_infosiga.zip]`. Baixa a fonte só se o arquivo não existir (até
512 MiB, recusa SHA-256 diferente e preserva o `.download`) e grava `dados/contagens_diarias.csv`,
`dados/manifesto.json` e `dados/setores.geojson`.

| Função | Contrato |
|---|---|
| `write_json` | `(path, value)` — grava JSON UTF-8 indentado por arquivo temporário + `replace` (nunca deixa arquivo pela metade); recusa `NaN`. |
| `load_config` | `()` — lê `config.json` (protocolo, fonte, retângulo, setores, períodos, alphas, métrica). |
| `sector_for` | `(lat, lng, config) -> str | None` — `None` fora do retângulo; senão `N`/`S` × `O`/`E` pela metade do retângulo. A linha divisória vai para **norte** e **leste** (o centro exato é `NE`). |
| `sector_geojson` | `(config)` — os 4 retângulos com `experimental: true` e `official_neighborhood: false`. |
| `normalize` | `(value)` — maiúsculas sem acentos (compara "MARÍLIA"/"MARILIA"). |
| `sha256` | `(path)` — SHA-256 do arquivo. |
| `obtain_source` | `(path, config)` — download controlado (ver acima). |
| `prepare` | `(path, config) -> dict` — lê os três CSV do ZIP em `cp1252`, separador `;`. Filtros, em ordem: município Marília; `id_sinistro` repetido; data entre `events_from` e `data_through`; `tipo_registro` ∈ sinistro fatal/não fatal (notificações saem); `tipo_via = VIAS URBANAS`; GPS válido; dentro do retângulo. Grava a grade **completa** dia × setor (zeros incluídos) e o manifesto com a auditoria de cada filtro (12.725 registros de Marília → 8.419 elegíveis). |
| `main` | `()` — linha de comando. |

### `treino.py`

Uso: `python treino.py --modo piloto|completo [--as-of AAAA-MM-DD] [--saida pasta]`. Cada execução vai para uma pasta
nova em `runs/` (recusa sobrescrever). Fixa no máximo 2 threads de BLAS.

| Símbolo | Contrato |
|---|---|
| `date` | `(value)` — `date.fromisoformat`. |
| `availability_cutoff` | `(as_of, lag) -> date` — **corte de informação**, exclusivo: 1º dia do mês, `lag` meses antes (com `lag=2`, em 20/10/2026 → 01/08/2026; em 01/01/2024 → 01/11/2023). Simula atraso de publicação — é uma **hipótese**, não observado. |
| `Counts` | carrega `contagens_diarias.csv`, confere o SHA-256 contra o manifesto e o snapshot contra o `config.json`, exige a grade completa (sem buraco, sem duplicata, sem negativo) e monta somas acumuladas. |
| `Counts.__init__` | `(config)` — ver acima; erros: "Dados/configuracao divergentes do manifesto.", "Grade diaria incompleta…". |
| `Counts.total` | `(begin, end, sector) -> float` — soma em `[begin, end)`; fora da cobertura lança erro. |
| `Counts.features` | `(as_of, sector) -> (x, evidence)` — atributos: indicador do setor (4), `log1p` do ritmo de 13 e 52 semanas com `(n+1)/14` e `(n+1)/53`, seno/cosseno do ano, anos desde 2020. `evidence`: janela usada, contagens de 13/52 semanas, **referência** `(n52+1)/53`, idade dos dados. Exige 52 semanas de histórico. |
| `feature_names` | `(config)` — nomes na ordem de `x`. |
| `rows` | `(counts)` — uma linha por semana (segunda a segunda) e setor, a partir de `training_rows_from` (precisa ser segunda), com `y` = registros da semana. |
| `labels_available` | `(all_rows, issued_at, config)` — só linhas cujo fim é ≤ corte de informação da emissão (nada do futuro entra no treino). |
| `fit` | `(selected, alpha) -> dict` — `StandardScaler` + `PoissonRegressor(alpha, max_iter=2000, tol=1e-7)`; aviso de convergência vira erro. |
| `predict` / `baseline` | previsões do Poisson · previsões da referência (`baseline_expected_count`). |
| `metrics` | `(selected, predicted, budget)` — MAE, desvio médio de Poisson, totais e **fração capturada** ao priorizar `budget` setor(es) por semana (empate pelo código do setor). |
| `portable` / `portable_predict` | modelo em JSON sem pickle (média, escala, coeficientes, intercepto) · previsão pela fórmula `exp(intercepto + Σ c·(x−média)/escala)`. |
| `result_rows` | linhas do retrospecto: as duas estimativas e o observado. |
| `run` | `(output, as_of, mode)` — (1) validação de 2024: treina com rótulos conhecidos em 01/01/2024 para cada alpha de `{0,01; 0,1; 1; 10}`; (2) **escolhe antes do teste**: Poisson só se o melhor desvio vencer a referência; grava `selecao.json` (`piloto` para aqui); (3) teste de 2025 com modelo congelado em 01/01/2025: `avaliacao.json` e `retrospecto.json`; (4) previsão da semana `as_of` (recusa data anterior ao fim do teste e base com mais de 180 dias), `modelo.json`, `vetores_paridade.json` (confere a fórmula portátil com tolerância 1e-10), `previsao.json`, `status.json` e `RESULTADO.md`. |
| `main` | `()` — linha de comando. |

### `tests/test_protocolo.py` (`unittest`, classe `ProtocolTest`)

| Caso | Prova |
|---|---|
| `ProtocolTest.setUpClass` | carrega configuração e contagens uma vez. |
| `ProtocolTest.test_publication_cutoff_across_year_boundary` | corte de informação atravessa o ano corretamente. |
| `ProtocolTest.test_future_observations_do_not_change_features` | somar contagens depois do corte não muda os atributos (sem vazamento do futuro). |
| `ProtocolTest.test_complete_zero_grid_and_labels_known_before_fit` | a grade tem zeros e os rótulos do treino são conhecidos antes da emissão. |
| `ProtocolTest.test_border_has_one_deterministic_sector` | ponto na divisa tem um único setor (centro → `NE`; canto sudoeste → `SO`; fora → nenhum). |

Rodar: `python -m unittest discover -s tests -v` (é o que `TREINAR.cmd` faz antes de treinar).

### `verificar_paridade.php`

Uso: `php verificar_paridade.php runs/<execução>/vetores_paridade.json`. Recalcula em PHP cada vetor com a mesma fórmula
de `portable_predict` e falha ("Previsao PHP diverge do Python.") se o erro passar de `1e-10 × max(1, |esperado|)`;
imprime `{"status":"ok","vectors":N,"max_error":…}`. A aplicação repete essa conferência em
`UrbanUpdaterTest::testPoissonFormulaMatchesThePythonParityVectors` ([`SectorForecast`](../domain/setores-e-regioes.md)).

### Execuções versionadas (`runs/`)

| Pasta | Modo | Conteúdo |
|---|---|---|
| `runs/piloto-verificado/` | `piloto` | só a escolha por validação (`selecao.json`, `progresso.json`, `status.json` = `pilot_complete`, sem teste final) |
| `runs/primeiro-treino-verificado/` | `completo`, `as_of` 20/10/2026 | escolha **baseline** (Poisson alpha 0,1 como candidato); teste 2025: desvio 1,2249 (Poisson) × 1,2599 (referência), ganho relativo 2,8%; fração no setor priorizado 39,3% × 37,2%; previsão 20–27/10/2026 com dados até 31/07/2026. É a execução importada por padrão por `urban:import`. |

Essas execuções são **evidência histórica**: não são regravadas. Execuções novas vão para outras pastas (o
`.gitignore` não as exclui; decida se versiona).

## `ml/marilia-grade-2km/` — experimento da grade (20/10/2026)

Experimento do projeto, separado do pacote dos setores, com [protocolo registrado antes de rodar](../../../../ml/marilia-grade-2km/PROTOCOLO.md)
e [resultado](../../../../ml/marilia-grade-2km/RESULTADO.md). Pergunta: quadrados de ~2 km concentram melhor o reforço
que os 4 setores? Uso: `python experimento.py preparar --zip <ZIP>`, `validar`, `testar` (o teste **recusa rodar uma
segunda vez**: "O teste já foi executado uma vez; não se repete").

| Símbolo | Contrato |
|---|---|
| `cell_of` | `(lat, lng) -> int | None` — quadrado 0–63 da grade 8 × 8 no mesmo retângulo; mesma regra de borda dos setores. |
| `sector_of` | `(cell) -> str` — setor que contém o quadrado (cada setor = 4 × 4 quadrados). |
| `cutoff` | `(as_of)` — mesmo corte de informação de dois meses. |
| `norm` / `write_json` | normalização sem acentos · gravação de JSON. |
| `prepare` | `(zip_path)` — mesmos filtros e snapshot do pacote dos setores; grava `dados/contagens_grade.csv` (dia, quadrado, contagem; só onde há registro) e confere que a soma por setor bate com as contagens do pacote (8.419). |
| `Grid` | contagens diárias por quadrado em somas acumuladas, com os vizinhos de cada quadrado. |
| `Grid.__init__` / `Grid.total` / `Grid.week_rows` | carrega o CSV · soma por quadrado em `[begin, end)` · linhas da semana com atributos, rótulo, **referência `(n52+0,1)/52,1`** e histórico desde 2019. |
| `mondays` | segundas cujo fim cabe até a data. |
| `fit` | Poisson regularizado (`max_iter=3000`, `tol=1e-8`). |
| `evaluate` | capturas por semana com 1, 2, 4 e 16 quadrados para referência, histórico fixo e Poisson, e o setor escolhido pela regra dos setores; desvio de Poisson. |
| `bootstrap` | `(weeks, a, b, seed=2026, draws=2000)` — diferença de frações e intervalo de 95% por reamostragem de semanas. |
| `validate` | escolhe o alpha em 2025 → `resultados/validacao.json` (alpha 0,01). |
| `test` | uma única vez, modelo congelado em 01/01/2026 → `resultados/teste.json`; a saída impressa está em `resultados/teste-saida.txt`. |

Resultado (29 semanas de 2026, 452 registros): com a mesma área, a grade (referência) capturou 91,2% contra 39,8% dos
setores (+51 pontos, IC 95% +46 a +56); com 4 quadrados, 51,5%. **O Poisson não acrescentou nada** (mesmos quadrados,
desvio pior: 0,521 × 0,375). Decisão integrada: a tela usa a grade com a **média simples** e mostra de 1 a 4 quadrados;
o Poisson da grade não é usado em lugar nenhum. Na aplicação, os quadrados são recalculados na importação a partir das
contagens (ver [importação](../application/importacao-urbana.md)).

## Relacionados

[ADR-0016](../../../adr/0016-planejamento-preventivo-por-setores.md) · [Planejamento por setores](../../../planejamento-por-setores.md) ·
[Dicionário: tabelas `URBAN_*`](../../dicionario-de-dados.md#tabelas-do-planejamento-urban_) ·
[Licenças e fontes](../../../licencas-e-fontes.md)
