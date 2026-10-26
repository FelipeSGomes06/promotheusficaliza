# Planejar reforço: previsão semanal por setores

Extensão da Cidade Preditiva ([ADR-0016](adr/0016-planejamento-preventivo-por-setores.md)). Caso separado das
[revisões de equipamentos](analise-preventiva.md): aqui o alvo é a **contagem de sinistros registrados** em quatro
setores de Marília nos próximos 7 dias, e a decisão é onde reforçar a presença de agentes de trânsito (fictícios).

## Origem dos dados

| Camada | Origem | Na tela |
|---|---|---|
| Contagens históricas por setor e semana | **Reais e agregadas.** Fonte: Infosiga-SP / Detran-SP ([catálogo](https://dadosabertos.sp.gov.br/dataset/sinistros-infosiga)), licença CC BY 4.0, snapshot de 20/10/2026; agregação e modelo pelo projeto Promotheus Fiscaliza. Só sinistros fatais e não fatais em vias urbanas, com coordenada no retângulo de estudo: 8.419 registros de 2019 a setembro/2026. Nenhum dado de vítima ou veículo. | Selo "Real agregado · Infosiga-SP" |
| Agentes, posições, sugestões e missões | **Fictícios** (cenário da demonstração e simulador). | Selo "Fictício" |

Os quatro setores são **quadrantes experimentais** de um retângulo (lat. −22,28 a −22,14; long. −50,02 a −49,86),
divididos no ponto médio. Não são bairros nem o limite do município. O retângulo não cobre necessariamente todo o
município.

## Método (resumo de `ml/marilia-setores/04-METODO-E-LIMITES.md`)

- Uma linha = um setor numa segunda-feira; alvo = registros elegíveis nos 7 dias seguintes (zero quando não houve).
- **Referência histórica** (método escolhido): (registros em 52 semanas + 1) ÷ 53.
- **Poisson experimental**: regressão de Poisson regularizada (alpha 0,1) com setor, ritmo de 13 e 52 semanas, época do
  ano e anos desde 2020. Coeficientes exportados em JSON (`modelo.json`), sem pickle.
- **Atraso de publicação assumido:** cada emissão só usa dados até dois meses antes do mês dela (em 20/10/2026, até
  31/07/2026). A base não informa quando cada registro foi publicado: é uma hipótese, mostrada na tela.
- Seleção no **ajuste de 2024** (a referência venceu: desvio 1,0610 contra 1,1748 do melhor Poisson). O **teste de
  2025** veio depois, com o modelo congelado em 01/01/2025, e **já é conhecido**: novos ajustes não podem tratá-lo como
  teste intocado. O Poisson continua experimental; trocar o padrão agora anularia a escolha feita sem olhar o teste.
- Ordem: decrescente pela estimativa, empate pelo código do setor. O 1º é **prioridade relativa de planejamento**, não
  emergência nem probabilidade.

## Resultado honesto do teste de 2025 (51 semanas, 204 linhas setor × semana)

| Medida | Poisson experimental | Referência histórica (escolhida) |
|---|---:|---:|
| Erro absoluto médio por setor/semana | 1,5903 | 1,5952 |
| Desvio médio de Poisson (menor é melhor) | 1,2249 | 1,2599 |
| Registros no setor priorizado (1 por semana) | 246 de 626 (39,3%) | 233 de 626 (37,2%) |
| Soma prevista × observada | 802 × 626 | 782 × 626 |

O Poisson foi um pouco melhor (desvio 2,8% menor, 13 registros a mais no setor priorizado ao longo de 51 semanas),
sem teste de significância nem confirmação prospectiva. Os dois superestimaram o total: o snapshot de 2025 tem menos
registros que os anos anteriores (636 contra 919 em 2024), o que pode ser cobertura da base e não queda real. A fração
é sobre **registros elegíveis na área de estudo**; não é taxa de acerto, chance de acidente nem acidentes evitados.
**Priorizar um setor não comprova que deslocar agentes evita sinistros** — o Infosiga não registra alocação de agentes.

O importador recalcula essas métricas a partir das linhas do retrospecto e recusa a execução se não baterem com
`avaliacao.json`.

## Sugestão de agente e missão fictícia

- Entram só agentes de **trânsito**, não arquivados, **em atividade** e sem missão vigente. Guarda e polícia não são
  escalados por proximidade.
- Ordem: posição atual (≤ 5 min) → posição atrasada (≤ 30 min) → só a base (sem posição recente, dito na tela); em
  cada grupo, menor distância **em linha reta** até a borda do setor (0 = dentro). Não é rota nem tempo de chegada.
- Escolher outro agente que não o sugerido exige justificativa. O gestor confirma explicitamente (caixa obrigatória);
  ficam gravados usuário, instante, observação, ponto de apoio, método, estimativa e posição congelados.
- **Uma missão vigente por agente**, garantida pelo banco (índice único parcial) com a linha do agente travada para
  reconferir a elegibilidade: duas confirmações simultâneas → uma missão e um conflito. Concluir ou cancelar (com
  motivo) libera o agente; cada transição fica em `URBAN_MISSION_LOG`.
- A reserva não fica na tabela do agente: o simulador mexe em posição e situação, nunca na missão.
- Período encerrado (a partir do fim exclusivo da previsão): a tela avisa e a confirmação é recusada.

## Comandos

```bash
# Importar a execução de referência (validada; repetir não duplica)
docker compose exec app php bin/fiscaliza urban:import

# Importar outra execução completa
docker compose exec app php bin/fiscaliza urban:import --dir=ml/marilia-setores/runs/<execução> \
  --setores=ml/marilia-setores/dados/setores.geojson --manifesto=ml/marilia-setores/dados/manifesto.json
```

Treino e avaliação (Python 3.12+, fora da aplicação; o pacote já traz as contagens agregadas):

```powershell
cd ml/marilia-setores
.\TREINAR.cmd                      # cria .venv, instala requirements.txt, roda os testes do protocolo e o treino completo
.\.venv\Scripts\python.exe treino.py --modo completo --as-of 2026-10-20 --saida runs/<nova-pasta>
php verificar_paridade.php runs/<nova-pasta>/vetores_paridade.json   # paridade PHP × Python do Poisson
```

`ml/marilia-setores/` é a cópia imutável do pacote de treino (bytes conferidos contra
`ENTREGA-MANIFESTO.json` por `tests/Unit/UrbanImportTest.php`; `.gitattributes` impede conversão de fim de linha).
Execuções novas vão para pastas novas em `runs/`; a de referência não muda.

## Limites

Snapshot revisado (sem data de primeira publicação), atraso assumido, cobertura desigual entre anos, quadrantes
amplos (sem exposição a volume de tráfego), teste retrospectivo já conhecido, nenhuma evidência causal sobre o efeito
de agentes, agentes e missões fictícios. A previsão vale para uma semana: depois dela é preciso gerar e importar outra
execução.

## Quadrados de 2 km (dentro dos setores)

A área de estudo também é dividida em 64 quadrados iguais de cerca de 2 × 2 km (cada setor = 4 × 4). A tela mostra de 1
a 4 quadrados com mais registros no último ano, num mapa próprio com a grade inteira, o bairro de referência e
"registros esperados em 7 dias" = (registros em 52 semanas + 0,1) ÷ 52,1. Experimento e números completos:
`ml/marilia-grade-2km/RESULTADO.md`.

| Teste em 2026 (29 semanas, 452 registros, nunca visto antes) | Registros na área priorizada |
|---|---:|
| 1 setor (~64 km²) — método atual | 39,8% |
| 1 quadrado (~4 km²) | 27,2% |
| 4 quadrados (~16 km²) | 51,5% |
| 16 quadrados (~64 km², mesma área de um setor) | 91,2% (diferença +51 pontos, IC 95% +46 a +56) |

O Poisson não melhorou os quadrados (mesma escolha, desvio pior), por isso fica a média simples. A lista é quase fixa
(sempre o centro e arredores): serve para saber onde concentrar presença, não muda de semana a semana. O teste de 2026
agora também é conhecido.

```bash
docker compose exec app php bin/fiscaliza urban:import                      # execução + quadrados (padrão)
docker compose exec app php bin/fiscaliza urban:import --sem-grade          # só a execução
```

## Atualização automática (mensal e semanal)

`php bin/fiscaliza urban:update` é a rotina agendável (serviço `atualizador` do Compose, a cada 6 h; é idempotente):

1. **Mensal:** se ainda não houve download neste mês, baixa o arquivo aberto do Infosiga-SP
   (`INFOSIGA_URL`, padrão `https://infosiga.detran.sp.gov.br/painel/download/file/dados_infosiga.zip`) por curl, só
   https, até 512 MiB. Lê só os `sinistros_*.csv`, aplica os mesmos filtros do treino (Marília, sinistro fatal/não
   fatal, via urbana, coordenada no retângulo) e guarda **só as contagens por quadrado e dia dos últimos 12 meses**
   (52 semanas até o corte de publicação; os meses mais novos também ficam, para a próxima conta). O ZIP é apagado;
   nenhum dado de vítima ou veículo é lido. O mesmo arquivo (SHA-256) não é gravado duas vezes; arquivo quebrado, sem
   as colunas ou mais antigo que o anterior é recusado sem gravar nada; mudança grande num mês já guardado (revisão do
   Infosiga) aparece como aviso.
2. **Semanal:** se a segunda-feira atual ainda não tem previsão com o arquivo mais recente, recalcula setores (média de
   52 semanas e Poisson experimental com o modelo congelado da referência) e quadrados de 2 km, e grava uma execução
   comum, marcada na tela como "atualização automática". Se os dados guardados não cobrirem as 52 semanas até o corte,
   não calcula e diz o motivo. Falha no download não impede a etapa semanal.

Método, modelo do Poisson e avaliação **não mudam** sozinhos: vêm da execução de referência (importada do treino,
que também fornece o retrospecto). Para trocar de método é preciso um novo treino e um novo teste.

Verificado em 20/10/2026: com o mesmo arquivo usado no treino, o cálculo em PHP reproduziu a execução de referência
(contagens, média e os 64 quadrados idênticos; Poisson com diferença ≤ 9e-16). O download real do site levou 12 s e
trouxe um arquivo idêntico ao do treino, por isso não gravou nada novo.

```bash
docker compose --profile atualizador up -d atualizador                       # agenda (a cada 6 h)
docker compose exec app php bin/fiscaliza urban:update                        # rodar agora
docker compose exec app php bin/fiscaliza urban:update --forcar-download      # baixar de novo neste mês
docker compose exec app php bin/fiscaliza urban:update --arquivo=var/dados_infosiga.zip --semana=2026-10-20
```
