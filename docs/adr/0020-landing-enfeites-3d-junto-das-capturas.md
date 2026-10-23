# ADR-0020 — Landing: enfeites 3D junto das capturas; um plano só

Data: 23/10/2026 · Situação: aceita · Atualiza: [ADR-0019](0019-landing-planos-3d-cinematograficos.md) ·
Evidência: [23/10/2026](../evidencias/2026-10-23-enfeites-3d.md)

## Contexto

Depois de ver os três planos do ADR-0019 publicados, o autor manteve o primeiro (Em campo, a lombada eletrônica) e
pediu para tirar Intervenção e Prioridade: com três telas escuras de cinema, a página "perdeu o aspecto de landing
page". No lugar, quis objetos 3D que enfeitem a página junto das imagens que já existem, com animações que cativem o
avaliador, ligados ao tema e "sem parecer um vídeo".

Medido antes de posicionar (Chromium, 960–1440 px): acima das capturas das cenas sobram de 0 a ~90 px, e as quinas
das capturas têm conteúdo (o botão "Cadastrar equipamento", o título "Manutenção", "Revisões recomendadas"). Abaixo
delas ficam o "Ampliar" (à direita) e a legenda (à esquerda).

## Decisões

### Seis objetos, cada um com o seu assunto
| Onde | Objeto | Animação |
|---|---|---|
| O sistema, sobre a quina da captura principal | pedaço de mapa (ruas, parque, rio) com três pinos, um âmbar | o mapa gira para a frente, os pinos caem com quique e pulsam anéis turquesa |
| 01 Inventário | câmera de monitoramento num poste curto | o poste sobe e a câmera varre devagar; com o mouse por perto, ela **olha para o ponteiro** |
| 02 Manutenção | cone com faixas refletivas e sinalizador | cai, achata no impacto e balança até parar; o sinalizador pisca em pulsos duplos |
| 03 Multas | placa Mercosul **fictícia** (FIS2C26) | gira até ficar de frente e balança; um reflexo corre pela chapa a cada 3,6 s (sem "leitura" de placa: o sistema não faz OCR) |
| 04 Revisões | semáforo | testa as três luzes ao aparecer e segue o ciclo verde → âmbar → vermelho |
| Planejar reforço, ao lado do título | mapa de calor em colunas (6 × 5, da cor clara ao vermelho), com 1–4 nas maiores | as colunas sobem em onda a partir do quadrado mais quente e "respiram" de leve |

Todos giram um pouco conforme a posição na janela e inclinam na direção do mouse quando ele chega perto (até ~2,5
vezes o tamanho do objeto). Fundo transparente, luz de estúdio (ambiente `RoomEnvironment`) e sombra de contato
gaussiana.

### Lugar
- Nas cenas: na quina de cima da captura, do lado de fora (longe da via), com a base sobre a borda. O objeto avança só
  ~6% do tamanho sobre a imagem (a sombra de contato na borda) e sobe pelo vão acima; 112–172 px. O plano que fica
  perde a margem negativa de baixo, para o cone da cena seguinte caber.
- O mapa fica sobre a quina direita da captura principal e o mapa de calor à direita do título de Planejar, onde os
  títulos e textos já não chegam; os dois saem abaixo de 1180 px. Os das cenas saem abaixo de 900 px (cenas em
  coluna): no celular, nenhum enfeite e o módulo nem é baixado.
- Conferido: nada cruza texto, nada passa da janela, nenhuma rolagem horizontal de 960 a 1920 px.

### Desenho
- `enfeites3d.js`: os seis objetos (geometria e texturas geradas no navegador) e **um** `WebGLRenderer` de fundo
  transparente, separado da hero e do plano, num quadro quadrado de P × P px (o maior enfeite × densidade, de 256 a
  512). Desenho direto, sem pós-processamento (o caminho que funciona em todas as placas testadas); luzes acesas são
  materiais emissivos e halos.
- Com `OffscreenCanvas` + WebGL, cada quadro vira um `ImageBitmap` entregue ao canvas do enfeite por transferência
  (`bitmaprenderer`), sem cópia; sem isso (Safari antigo), cópia com `drawImage`.
- `enfeites.js`: baixa o módulo com um enfeite exibido a até 1,2 tela; desenha só os visíveis, a até 60 quadros/s
  (30 se atrasam); a entrada roda uma vez, com metade do enfeite à vista. Movimento reduzido: objeto pronto e parado,
  redesenhado só quando muda de tamanho. Sem WebGL, sem o módulo ou com o contexto perdido: os enfeites não aparecem
  (decoração, `aria-hidden`).

### O que sai
Os planos Intervenção e Prioridade (cenários 2 e 3 de `planos3d.js`, o rótulo de fase e as colunas holográficas).
O mundo do plano que fica não mudou.

## Consequências
- Três contextos WebGL no máximo (hero, plano e enfeites), cada um só desenhando quando o seu está na tela.
- Peso: `enfeites3d` 13,6 KB (5,6 KB gzip); o three.js segue compartilhado. `planos3d` caiu de 54,5 para 44,6 KB.
- Trocar um objeto, sua câmera ou animação: `landing/src/enfeites3d.js` (uma fábrica por objeto); trocar onde fica:
  `landing/pagina.php` (`data-enfeite`) e `.enfeite--*` em `landing.css`.

## Alternativas descartadas
- Objetos sobre as capturas: cobririam título e botões das telas.
- Canvas fixo cobrindo a janela, desenhando cada enfeite por *scissor*: atrasa um quadro em relação à rolagem.
- Um contexto WebGL por enfeite (seis): memória e shaders repetidos para objetos pequenos.
- Empréstimo do canvas ao mais visível (como no plano): dois enfeites podem aparecer juntos, e o outro congelaria.
