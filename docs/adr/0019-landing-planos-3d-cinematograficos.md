# ADR-0019 — Landing: planos 3D cinematográficos no lugar dos objetos das cenas

Data: 23/10/2026 · Situação: aceita · Atualiza: [ADR-0015](0015-landing-publica-e-marca-3d.md) e
[ADR-0018](0018-landing-seis-pilares-e-foto-de-marilia.md) (só a parte dos objetos 3D das cenas; a marca da hero não
muda) · Evidência: [23/10/2026](../evidencias/2026-10-23-planos-3d.md)

## Contexto

O autor achou os três objetos 3D das cenas de recursos (radar, trecho de via com marcador, placa com seta) simples
demais e pediu algo mais cinematográfico para o avaliador, mantendo a marca da hero como está. Antes de refazer, pediu
para entender por que aquele modelo, naquela posição e com aquela animação, degradava. Medido no Chromium headless
(Playwright, SwiftShader; detalhes na evidência):

**Posição**
- Caixa absoluta de 116–176 px no vão entre as cenas, com 45% da largura para fora do conteúdo
  (`right: -0.45 × tamanho`). Entre 900 e ~1330 px de largura passa da janela e é cortada pelo `overflow-x: clip` do
  `body`: 7–10 px cortados em 960, 1024 e 1280 px.
- O último objeto (placa) fica no respiro do fim da seção e a caixa dele invade a seção escura seguinte: o poste
  atravessa a borda de Planejar reforço.
- Solto no vão, longe do texto e da captura, sem chão nem sombra: lia como adesivo, não como cena; pequeno demais para
  qualquer detalhe. No celular não aparecia.

**Animação**
- Três movimentos empilhados sem história: pose ligada à rolagem, balanço infinito de ±20° (seno) e flutuação CSS de
  10 px. A pose de entrada começa a −60° e o balanço assentado vai de −44° a −4°: a placa, que é plana, passa boa parte
  do tempo quase de lado.

**Desenho**
- Os objetos eram desenhados num canto do canvas da hero (scissor) e copiados com `drawImage` para um canvas 2D. Cada
  cópia de um canvas WebGL obriga o navegador a terminar o quadro antes de ler; com a hero na tela, o canto sujo fazia
  a hero ser redesenhada também (render dobrado).
- O balanço infinito pedia render + cópia a cada tique do GSAP enquanto o objeto estivesse a até 200 px da tela, mesmo
  com o visitante parado: 30–73 renders e cópias por segundo, 120–690 *draw calls*/s, sem parar.
- Densidade limitada a 1,5 px por px CSS: em tela 2× ficava borrado (248 px de canvas para 166 px CSS).
- Entrar por uma âncora (`/#manutencao`) baixava o módulo inteiro da hero (617 KB) e montava a cena da marca fora da
  tela (canvas 1044 × 909, ambiente, sombras, shaders) só para desenhar um ícone de 166 px.

## Decisões

### Composição: três planos de cinema, no fluxo da página
- Cada objeto vira um **plano 2,39:1** entre as cenas (depois de 01 Inventário, 02 Manutenção e 04 Revisões), na
  largura das cenas e por cima da via central, que entra e sai dele. Lugar próprio no fluxo: não cobre texto nem
  captura em nenhuma largura, nunca passa da janela e não muda o layout quando o 3D chega. No celular, 2:1 até 560 px
  e 16:11 abaixo, alinhado ao texto.
- Uma única avenida noturna (pista molhada, canteiro, calçadas, prédios com janelas, postes com cone de luz no ar,
  neblina, trânsito com faróis, lanternas e rastros de longa exposição) com três cenários ao longo dela:
  1. **Em campo** — lombada eletrônica na calçada; os laços detectores acendem quando um carro passa e o painel de LED
     mostra a velocidade (vermelho acima de 40 km/h); ondas turquesa da marca saem do sensor.
  2. **Intervenção** — radar de braço com gabinete, cones, refletor de trabalho e furgão com giroflex. A rolagem conta
     a história: falha registrada (anel vermelho piscando) → em execução (porta aberta, refletor, giroflex com facho
     volumétrico) → concluída (anel turquesa). Um rótulo no canto acompanha a fase.
  3. **Prioridade** — cancela, patinete, lombada e câmera lado a lado; uma varredura passa e colunas de luz sobem até a
     prioridade de cada um (32 e 27, como na captura; a mais alta em âmbar), com as peças em silhueta na frente.
- Cada plano é um **caminho de câmera** (Catmull-Rom) ligado à rolagem: grua de cima para a altura da rua, com inércia
  de ~0,5 s e um leve giro de lente que se desfaz. As viradas acontecem enquanto o plano está inteiro na janela
  (p ≈ 0,37–0,63). O tempo só move o trânsito e as luzes.
- Linguagem de cinema: tarjas pretas que se recolhem quando o plano chega ao meio da tela, bloom nas luzes, vinheta,
  grão, aberração cromática leve nas bordas, sombras levemente azuladas, reflexos das lâmpadas no asfalto calculados
  pelo ponto especular (alongados em ângulo rasante) e câmera "na mão" de poucos centímetros.
- Honestidade: cada plano tem legenda real ("Ilustração 3D: …"); nada sugere operação ao vivo, câmera real ou
  integração com equipamentos. Rótulos e fase são decoração (`aria-hidden`).

### Desenho: renderer próprio, emprestado ao plano em cena
- `planos3d.js` tem o mundo inteiro (geometria e texturas geradas em canvas; nada para baixar, nada fora da CSP) e
  **um `WebGLRenderer` separado do da hero**, que não muda. `planos.js` decide o resto:
  - o canvas 3D é **movido** para o plano mais visível; o que sai de cena fica com o último quadro, copiado **uma vez**
    para um canvas 2D. Na carga, um quadro de cada plano já é congelado, para quem rolar depressa encontrar a cena.
    Nenhuma cópia por quadro;
  - só desenha com um plano na tela e a aba visível, a até 60 quadros/s; fora dos planos, zero renders;
  - densidade até 2× no desktop (1,5× no celular), com queda automática de 0,25 por degrau quando a média passa de
    ~25 ms por quadro;
  - quantidade fixa de luzes (trocar de plano só move e reacende, sem recompilar shader); shaders compilados com
    `compileAsync` antes do primeiro quadro;
  - pós-processamento: render em alvo HalfFloat com MSAA 4× (sem MSAA no celular) → bloom em meia resolução → saída
    ACES → acabamento de filme. Sombra só de uma luz e só no desktop.
- Carga: o módulo dos planos (54 KB, 17 KB gzip) e o three.js (agora um pedaço compartilhado com a hero) só são pedidos
  quando um plano chega a 1,2 tela de distância, com o navegador ocioso. A hero deixou de ser baixada por causa das
  cenas: entrar por `#manutencao` não cria mais o canvas da marca.
- Movimento reduzido (sistema ou `pf-motion`, inclusive trocado depois): cada plano mostra **um quadro parado** na pose
  de repouso (o giroflex aponta para longe da lente), as tarjas ficam recolhidas e nada é redesenhado.
- Sem WebGL, sem o módulo, com contexto perdido ou sem JavaScript: fica o **cartaz em SVG** (a avenida à noite com a
  lombada, `#plano-cartaz` no sprite da página), sem buraco nem layout diferente.

## Consequências
- A página fica mais longa (três planos de ~520 px no desktop). O custo de GPU por quadro é maior que o dos ícones
  (canvas maior e pós-processamento), mas só enquanto um plano está visível, com teto de 60 quadros/s e queda de
  densidade; parado fora dos planos o custo é zero (antes, 30–73 renders/s perto de qualquer objeto).
- Criar o mundo leva ~115 ms de CPU no contêiner sem GPU (texturas, geometria), uma vez, no ocioso.
- Trocar texto das legendas: `landing/pagina.php` (`$plano(...)`). Ajustar cenas, luzes ou caminhos de câmera:
  `landing/src/planos3d.js` (`CAMINHOS`, `arranjar`, `REPOUSO`).

## Alternativas descartadas
- Manter os ícones e só melhorar o modelo: o problema era também de lugar (canto da página, cortado) e de desenho
  (cópia por quadro).
- Um canvas fixo cobrindo a janela, desenhando cada plano por scissor: o canvas fixo atrasa um quadro em relação à
  rolagem e os planos "nadam".
- Um contexto WebGL por plano (quatro com a hero): triplica shaders, texturas e memória para nunca mostrar dois planos
  inteiros ao mesmo tempo.
- `OffscreenCanvas` com `transferToImageBitmap`: evitaria até a cópia na troca, mas o suporte a WebGL em
  `OffscreenCanvas` ainda falha em Safari antigos e a troca acontece poucas vezes por visita.
- Vídeo pré-renderizado ou cenas de bibliotecas externas (AnimatedWebs, 21st.dev): CSP só aceita a própria origem,
  vídeo em autoplay foi descartado no ADR-0018 e não acompanharia a rolagem.
- Pin com rolagem sequestrada: a landing mantém rolagem nativa (ADR-0015).
