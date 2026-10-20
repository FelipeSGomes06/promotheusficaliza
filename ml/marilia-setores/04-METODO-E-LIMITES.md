# Método, protocolo congelado e limites

## Dados e geografia

Fonte: [Infosiga-SP/Detran-SP, catálogo oficial](https://dadosabertos.sp.gov.br/dataset/sinistros-infosiga), licença CC BY 4.0. Atribuição sugerida: **Fonte: Infosiga-SP / Detran-SP, snapshot de 20/10/2026; agregação e modelo pelo projeto Promotheus Fiscaliza.**

`config.json` é a fonte de verdade dos parâmetros. Foi congelado antes de avaliar o teste de 2025. O preparador lê só CSV de sinistros, sem extrair dados de vítimas/veículos. Filtra município, tipo de registro, via urbana, data e retângulo de estudo. O resultado é um CSV agregado `date,sector_id,count`, incluindo zeros para todas as combinações diárias.

Quatro setores experimentais aproximadamente iguais, fixados por coordenadas e sem consultar o desempenho futuro. Os limites são: latitude -22,28 a -22,14; longitude -50,02 a -49,86. Divisão no ponto médio. São quadrantes geográficos, não bairros oficiais nem uma divisão balanceada por número de acidentes. A área e a população podem ter perfis diferentes; o modelo estima concentração de registros, sem exposição ao volume de tráfego.

Snapshot revisado, com cobertura desigual entre anos. O recorte inicial tem 8.419 eventos de 2019 a setembro/2026. Dados anuais e motivos de exclusão estão no manifesto. Não inferir aumento/redução real de risco apenas pela variação de registros.

## Alvo e variáveis

Uma linha é **um setor em uma segunda-feira**, com alvo igual à quantidade de eventos registrados nesse setor nos sete dias seguintes. Linhas sem evento têm alvo zero. O primeiro corte é 02/03/2020, quando existe histórico suficiente mesmo com atraso de publicação.

Variáveis: quatro indicadores de setor, logaritmo de 1+taxa semanal das últimas 13 semanas publicadas, logaritmo de 1+taxa semanal das últimas 52 semanas publicadas, seno/cosseno da posição no ano e anos desde 2020. São nove valores.

Taxa 13 semanas: `(count13+1)/14`. Taxa 52 semanas/referência: `(count52+1)/53`. O acréscimo é uma suavização explícita de uma pseudocontagem/pseudosemana. A referência é a taxa de 52 semanas.

O modelo candidato é Poisson com link log, pré-processamento por StandardScaler ajustado apenas no treino e regularização alpha em `{0.01, 0.1, 1, 10}`. Referência técnica: [PoissonRegressor](https://scikit-learn.org/stable/modules/generated/sklearn.linear_model.PoissonRegressor.html).

## Publicação mensal e separação temporal

A base não informa o instante da primeira publicação de cada registro. Simula-se disponibilidade conservadora: **retirar dois meses completos anteriores ao mês da emissão**. Assim, em 20/10/2026 o corte exclusivo é 01/08/2026, permitindo registros até 31/07/2026. Isso é hipótese documentada; não reconstrução comprovada de todos os snapshots antigos.

Características e rótulos usados para treinar só podem terminar antes desse corte. Dados mais novos podem ser carregados para avaliação posterior, mas não entram nas características do corte nem no ajuste do modelo congelado.

| Etapa | Regra |
|---|---|
| Treino para ajuste | Cortes desde 02/03/2020; rótulos com fim até 01/11/2023 (emissão simulada em 01/01/2024). |
| Ajuste | 01/01/2024 a 28/10/2024, fim exclusivo, 43 semanas × 4 setores; informações/rótulos ficam disponíveis antes da emissão simulada de 01/01/2025. |
| Seleção | Menor desvio médio de Poisson no ajuste, desempate pelo alpha. Candidato só vira padrão se seu desvio for menor que o da referência. |
| Teste final | Modelo retreinado com rótulos permitidos na emissão de 01/01/2025 e congelado; 51 semanas completas de 2025. Características de cada semana respeitam o corte de publicação daquela semana. |
| Emissão atual | Retreino com alpha escolhido no ajuste e rótulos disponíveis no corte da data de emissão; previsão para sete dias. |

As primeiras/últimas semanas parciais de cada janela não são inventadas. O treino completo produz 1.372 linhas possíveis de semana/setor no snapshot. Datas exatas de cada etapa estão nos JSON.

**O teste de 2025 já foi avaliado nesta entrega.** Novos ajustes que observarem esses resultados não podem alegar que 2025 continua sendo um teste intocado. Preservar a execução original e o protocolo. Para confirmar ganhos de novas versões, reservar dados futuros/novo período antes da busca; repetição com a mesma configuração serve para reproduzir, não obter evidência independente.

Validação temporal é apropriada porque a informação futura não deve contaminar o passado. Com múltiplos setores na mesma data, separar por datas inteiras, não por linhas aleatórias. [Referência técnica](https://scikit-learn.org/stable/modules/generated/sklearn.model_selection.TimeSeriesSplit.html).

## Resultados e interpretação

No ajuste, a referência venceu: desvio **1,0610**, contra **1,1748** do melhor Poisson (`alpha=0.1`). A execução preservou a referência como padrão antes de abrir o teste.

No teste de 2025, o Poisson apresentou desvio **1,2249** contra **1,2599** da referência, ganho relativo de **2,8%**, e MAE **1,5903** contra **1,5952** por setor/semana. A fração de registros capturados no setor priorizado foi **39,3%** contra **37,2%**. É um ganho modesto de um candidato experimental em um período retrospectivo, sem confirmação prospectiva ou análise de significância.

O orçamento é de um setor por semana, para ambos os métodos, com desempate pelo ID em ordem lexical. A fração usa apenas eventos elegíveis na área de estudo; não significa que 39,3% de todos os acidentes do município foram previstos ou evitados. O tamanho dos setores é fixo entre métodos.

Trocar a referência por Poisson depois de ver o teste anularia a alegação de que o método ativo foi escolhido sem consultar esse teste. A interface pode oferecer comparação/seleção manual explicitamente identificada, mantendo a decisão original e todas as métricas.

## Equipes e efeito da decisão

O Infosiga não fornece alocação de agentes nem o efeito causal da presença deles. A recomendação inicial usa regras auditáveis de disponibilidade/proximidade; o gestor confirma uma missão fictícia. Não existe modelo treinado para quantos agentes enviar, nem evidência de redução de acidentes por essa ação.

O histórico real é agregado. Contas, agentes, posições operacionais e missões permanecem fictícios na demonstração. Métricas de avaliação não são calculadas sobre ocorrências inventadas pelo simulador.

## Execução e evolução

`TREINAR.cmd` prepara ambiente e executa o protocolo completo. `--modo piloto` executa apenas comparação no ajuste; `--modo completo` inclui teste, emissão atual e exportação. Cada execução exige diretório novo e preserva as anteriores.

Não há motivo computacional para deixar este treino por horas: a grade é pequena. Aumentar tentativas com o mesmo teste não melhora a validade. Evoluções úteis dependem de melhor cobertura geográfica/temporal, outras fontes disponíveis antes da previsão e confirmação de desempenho em período reservado.
