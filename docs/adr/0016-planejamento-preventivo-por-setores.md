# ADR-0016 — Planejamento preventivo semanal por setores, com histórico real agregado

Data: 20/10/2026 · Situação: aceita · Relacionadas: [ADR-0007](0007-analise-preventiva-heuristica.md) (revisões de
equipamentos), [ADR-0010](0010-versao-do-cadastro-separada-da-telemetria.md) (cadastro × telemetria)

## Contexto

A Cidade Preditiva tinha só a antecipação de falhas de equipamentos, sobre histórico sintético. O Codex entregou um
pacote (`ml/marilia-setores/`) com contagens diárias **reais e agregadas** de sinistros do Infosiga-SP/Detran-SP
(CC BY 4.0) em quatro quadrantes experimentais de Marília, um treino Python separado (referência de 52 semanas ×
Poisson regularizado, escolha no ajuste de 2024, teste de 2025) e uma execução completa em JSON. Pedido: prever a
semana por setor, explicar, sugerir um agente de trânsito, o gestor confirmar uma missão fictícia e rever o passado.

## Decisões

- **Dois casos separados.** "Revisões recomendadas" (equipamentos, sintético) continua igual; "Planejar reforço" é
  outro módulo, com tabelas próprias (`URBAN_*`). A previsão não é evento de recurso nem muda disponibilidade.
- **Treino fora da aplicação.** O PHP só importa JSON/GeoJSON já calculados: `php bin/fiscaliza urban:import`.
  Validação completa (tamanho, versão, IDs, datas, métodos, hashes, números finitos e não negativos, geometria,
  e as métricas do teste recalculadas a partir das linhas do retrospecto) **antes** de abrir a transação; SHA-256 do
  conjunto de bytes torna a reimportação idempotente. Sem shell, Python ou pickle em requisição web.
- **Precisão.** Estimativas em `DECFLOAT(34)` gravadas pelo texto mais curto que reproduz o `double` do JSON
  (`DOUBLE PRECISION` voltava pelo PDO com 16 dígitos e perdia o último bit). A tela arredonda a uma casa.
- **Métodos.** O método ativo é o escolhido no ajuste (referência). O Poisson aparece como "experimental",
  comparável na tela, com ordem recalculada pelos seus valores; a execução importada não é sobrescrita.
- **Retrospecto.** Usa as previsões congeladas do modelo emitido em 01/01/2025 (não o modelo de 2026). Os
  registros observados só são enviados ao navegador depois do controle "Revelar".
- **Missão fictícia.** Elegível: agente de trânsito, não arquivado, em atividade e sem missão vigente. Ordenação
  por confiabilidade da posição (atual → atrasada → só base) e distância em linha reta até o retângulo do setor.
  A reserva é garantida pelo banco (índice único parcial `UX_URBAN_MISSION_ACTIVE` em `AGENT_ID` com
  `STATE = 'CONFIRMADA'`), com a linha do agente travada para conferir a elegibilidade na mesma transação. A missão
  fica fora da tabela `AGENT`: o simulador mexe em posição/situação, nunca na reserva. Estados `CONFIRMADA`,
  `CONCLUIDA`, `CANCELADA`; histórico em `URBAN_MISSION_LOG` (só leitura e inserção para a aplicação).
- **Origem dos dados à vista.** Selo "Infosiga-SP · real agregado" na camada histórica/preditiva e "fictício" em
  agentes e missões; o aviso global deixou de dizer que tudo é fictício.

## Consequências

- Uma semana prevista vence: depois de `until`, a tela avisa e a confirmação é recusada até importar outra execução
  (`treino.py --as-of <data>`). Mais de 180 dias entre referência e emissão é recusado na importação.
- O ganho do Poisson no teste (2,8% no desvio; 39,3% × 37,2% dos registros no setor priorizado) é modesto,
  retrospectivo e já conhecido: não vira método padrão nem prova de prevenção.
- Quatro quadrantes amplos não são bairros nem limite municipal; distância em linha reta não é tempo de chegada.

## Alternativas descartadas

Serviço Python permanente (dependência e superfície a mais para valores que mudam semanalmente); inferência PHP do
Poisson na primeira versão (o contrato permite importar o calculado); reserva como coluna em `AGENT` (o simulador e a
edição do cadastro passariam a disputar a mesma linha e a reserva poderia ser apagada por um tick).

## Adendo (20/10/2026) — quadrados de 2 km

Experimento separado (`ml/marilia-grade-2km/`, protocolo registrado antes de rodar, teste único em 29 semanas de 2026
que nenhum método tinha visto): com a mesma área, 16 quadrados de 2 km pegaram 91% dos registros contra 40% do setor
priorizado; 4 quadrados (um quarto da área) pegaram 51%. O ganho veio de dividir melhor — o centro fica no cruzamento
dos quatro setores —, não do Poisson, que escolheu os mesmos quadrados e errou mais na quantidade.

**Decisão:** a tela mostra de 1 a 4 quadrados pela **média simples** das 52 semanas, num mapa próprio com a grade
inteira (8 × 8) e só os prioritários numerados; o mapa dos setores não muda. Os quadrados são calculados na
importação (`URBAN_CELL`, migração 0004) a partir de `contagens_grade.csv`, e a soma dos quadrados de cada setor tem de
bater com as contagens de 13 e 52 semanas da execução. Ficam fora do SHA-256 da execução: uma execução já importada
recebe os quadrados depois. Bairros de referência = os da demonstração cujo centro cai no quadrado (ou o mais próximo).
Missões continuam por setor; o quadrado pode ir no "ponto de apoio". A ordem quase não muda de semana para semana.

## Adendo (20/10/2026) — atualização automática

**Decisão:** `urban:update` (serviço `atualizador`, a cada 6 h, idempotente) baixa o Infosiga-SP uma vez por mês e
guarda só as contagens de Marília por quadrado e dia dos últimos 12 meses (`URBAN_SNAPSHOT`, `URBAN_DAILY`, migração
0005, só leitura e inserção); toda segunda grava a previsão da semana como uma execução comum, com a média simples
calculada em PHP e o Poisson pelo modelo congelado da referência (paridade conferida com os 20 vetores do treino).
Python fica só para treino e avaliação. Download por curl (o projeto mantém `allow_url_fopen` desligado), só https;
a imagem PHP ganhou a extensão `zip`. O retrospecto da tela continua sendo o da execução importada do treino.
**Consequência:** a previsão não vence mais sozinha; o método não muda sem novo treino e novo teste.

## Adendo (20/10/2026) — tela só com a média simples

A pedido do autor, a tela ficou só com os quadrados de 2 km e a média simples: saíram o mapa e a lista dos setores, o
seletor de método e a comparação com o Poisson (também no retrospecto). Tocar num quadrado abre o setor dele, onde
continuam a explicação, a sugestão de agente e a missão. O Poisson continua nos dados importados, no treino e nos
testes de serviço, mas não aparece para quem planeja.

## Adendo (20/10/2026) — tela em três passos

A pedido do autor ("mais intuitiva, sem tirar o que faz"): resumo da semana em uma frase ("Reforce primeiro: <bairros>"),
1 · onde (quantos lugares a equipe cobre, mapa e lista clicáveis, quadrado escolhido destacado, "já tem agente"),
2 · quem (sugestão com a distância até o **quadrado**), 3 · confirmar, e a lista de missões "agente → quadrado". As
definições e a origem dos dados ficam em "Como ler estes números". A missão guarda o quadrado (`URBAN_MISSION.CELL_NO`,
migração 0006, opcional); o servidor confere que o quadrado é da execução atual e do setor informado.
