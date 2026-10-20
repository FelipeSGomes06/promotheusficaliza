# Contrato de importação v1

Exemplos reais e autoritativos: arquivos em `runs/primeiro-treino-verificado/` e `dados/setores.geojson`. Nenhum exemplo contém credenciais ou usuários reais. Caminhos abaixo são relativos à pasta de uma execução completa, salvo o GeoJSON.

## previsao.json

| Campo | Regra / significado |
|---|---|
| `schema_version` | Inteiro `1`. Versões desconhecidas recusadas. |
| `protocol_version` | `marilia-setores-v1`; vincula filtros e definição geográfica. |
| `mode` | `forecast`. Não misturar com retrospecto. |
| `as_of`, `until` | Datas ISO locais, intervalo `[as_of, until)`, exatamente sete dias. |
| `generated_at_utc` | Carimbo de geração do arquivo, distinto da data de previsão. |
| `source_snapshot_sha256` | 64 caracteres hexadecimais; deve coincidir com o manifesto. |
| `data_reference_through` | Último dia de histórico permitido nesta emissão. Deve ser anterior ao período previsto. |
| `selected_method` | `baseline` ou `poisson`. Decisão de ajuste anterior ao teste final. |
| `candidate_method` | `poisson`. |
| `unit` | `expected_registered_crashes_in_7_days`. |
| `test_status` | `experimental_retrospective`. |
| `test_relative_gain` | Ganho de desvio do candidato no teste conhecido. Pode ser negativo. Não equivale a taxa de acerto. |
| `sectors` | Exatamente quatro itens com IDs distintos `NO`, `NE`, `SO`, `SE`. |
| `notice` | Texto explicativo. Exibir escapado. |

Cada setor contém:

- `sector_id`, `rank` (permutação 1–4), `priority` (`PRIORITARIA` para rank 1, `ACOMPANHAR` para os demais).
- `expected_count`: estimativa do método selecionado no ajuste; número real finito não negativo.
- `expected_count_ml`, `expected_count_baseline`: duas estimativas comparáveis, números reais finitos não negativos. Preservar casas no banco/JSON; arredondar só na tela.
- `evidence.history_start`, `history_through`: intervalo inclusivo do histórico de 52 semanas.
- `evidence.count_13w`, `count_52w`: contagens inteiras não negativas; `baseline_expected_count` é o valor da referência.
- `evidence.data_age_days`: dias entre o corte exclusivo de informação e a emissão. A interface pode mostrar a data diretamente para evitar ambiguidade do arredondamento em dias.
- `evidence.availability`: `assumed_publication_delay_not_observed`.

O rank é decrescente por `expected_count`, com desempate pelo `sector_id` em ordem lexical. Para comparar outro método na interface, calcular o rank com aquele valor e mostrar qual método foi escolhido pelo usuário. Conservar o rank da execução importada.

## avaliacao.json

Contém protocolo, hash de `config.json`, método escolhido antes do teste, alpha do candidato, `test_model`, métricas de `poisson` e `baseline`, ganho de desvio e limitações.

As métricas incluem MAE, desvio médio de Poisson, soma observada/prevista, quantidade de linhas, semanas e período. `top_sectors_budget=1`; `top_budget_capture_fraction` é a fração de registros futuros que caíram no setor priorizado em cada semana, somada sobre o teste. Não chamar essa medida de precisão geral, probabilidade ou acidentes evitados.

`test_model.training_labels_until` é fim exclusivo do último rótulo de treino. Sua data é anterior ao início do teste; `test_model` é o modelo congelado para o retrospecto, diferente de `modelo.json`, que é retreinado para a emissão atual.

## retrospecto.json

- `schema_version=1`, `mode=historical_replay`, `model_issued_at=2025-01-01`, `selected_method`.
- `rows`: quatro linhas por semana, uma por setor. Cada linha tem `from`, `until` (sete dias, fim exclusivo), `sector_id`, `expected_count_ml`, `expected_count_baseline`, `observed_count` e `evidence`.
- `observed_count` só é usado ao revelar resultado, calcular métricas ou fornecer histórico depois do corte de publicação. Não vira entrada da previsão daquela mesma semana.

## dados/setores.geojson e dados/manifesto.json

GeoJSON em WGS84, **ordem `[longitude, latitude]`**. Quatro polígonos retangulares com propriedades `id`, `name`, `experimental=true`, `official_neighborhood=false`, `centroid={lat,lng}`. Conferir IDs e limites com `config.bounds`. A função `sector_for` atribui as linhas da divisão ao norte/leste e mantém bordas externas incluídas.

O manifesto fornece fonte oficial, licença, hash, data do snapshot, cobertura, filtros, contagens e hash do CSV agregado. Importar/guardar a proveniência com a execução. O retângulo é uma área de estudo; não é um limite administrativo de Marília.

## modelo.json e vetores_paridade.json

Modelo exportado em JSON: `kind=poisson_log_link_standardized`, nomes/ordem das nove variáveis, médias, escalas, coeficientes, intercepto, alpha e janela de treino.

Fórmula: `exp(intercept + soma(coef[i] * (x[i]-mean[i]) / scale[i]))`. Os quatro primeiros valores são one-hot do setor na ordem de `config.sectors`. Os demais são definidos em `treino.py`, função `Counts.features`. O script `verificar_paridade.php` é uma referência local mínima, não um controlador web.

Não é necessário rodar inferência PHP na primeira integração: importar previsões já calculadas é suficiente. Se implementar inferência, validar as nove variáveis e confirmar os 20 vetores de teste, com tolerância absoluta/relativa `1e-10`. O modelo não contém objetos Python serializados.

## Limites de leitura e integridade

Recomendação para o importador CLI: máximo 10 MiB por JSON, máximo 64 MiB no conjunto, profundidade limitada, JSON estrito, números finitos e IDs permitidos. Aceitar somente os arquivos esperados; não executar conteúdo. Validar antes de iniciar a transação Firebird. Registrar SHA-256 dos bytes importados; importar novamente o mesmo conjunto deve retornar a execução existente.

Para previsão com mais de 180 dias entre referência e emissão, o treino recusa gerar arquivo. O importador deve aplicar regra equivalente. A tela mostra os dados de referência mesmo abaixo desse limite. Geometria/CSV não recebem comandos SQL ou nomes de arquivos controlados pelo navegador.
