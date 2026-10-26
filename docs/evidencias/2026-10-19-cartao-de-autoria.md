# Cartão de autoria na página Sobre — 19/10/2026

Pedido do autor: no espaço vazio da coluna da esquerda da página Sobre, a foto dele com "Desenvolvido por Felipe
Gomes" embaixo; ao clicar, a foto treme e a página faz uma transição "cinematográfica" que expande o cartão e leva a
<https://devfelipegomes.com.br/>. Sem tecnologia nova: Twig, CSS em `app.css` e GSAP do pacote já instalado, seguindo
a documentação oficial do GSAP. Decisões e valores no
[adendo da ADR-0012](../adr/0012-identidade-motion-e-build-de-assets.md#adendo-19102026--cartão-de-autoria-na-página-sobre).

## O que mudou

| Onde | Mudança |
|---|---|
| `templates/about.twig` | cartão `<a class="author-card" rel="author">` no fim da coluna da esquerda (link comum, funciona sem JavaScript); `data-page="sobre"`; texto de tecnologia atualizado |
| `resources/css/app.css` | cartão escuro com malha de ruas, halo e anéis de radar, brilho do mouse, hover; tela sobreposta (`.author-portal`); `sticky` a partir de 961×760 px |
| `resources/js/pages/sobre.js` | entrada (SplitText), inclinação (quickTo), tremor (CustomWiggle), expansão (clip-path + Flip), Esc, bfcache, região viva |
| `resources/js/app.js` | carrega o módulo só na página Sobre, sem atrasar o Alpine |
| `public/assets/img/autor/felipe-gomes.webp` | foto enviada pelo autor (440×550, 22 KB, sem metadados) — fora da licença MIT |
| `public/assets/build/` | build regenerado; o módulo novo tem 21 KB (9,4 KB gzip) |
| `tests/Http/WebSecurityTest.php` | a página Sobre continua sem `style=`/`on*=` e o cartão é um link comum para o portal |
| Documentação | ADR-0012 (adendo), arquitetura, licenças, README |

## Verificação

Chromium headless 141 (Playwright 1.56.1) contra o PHP embutido renderizando o template com `View`, `ViteAssets` e
`SecurityHeaders` reais. O destino foi respondido por uma página simulada, porque o domínio é bloqueado pela rede deste
ambiente. Relatório: [verificacao-navegador.json](2026-10-19-cartao-de-autoria/verificacao-navegador.json).

| Cenário | Resultado |
|---|---|
| Desktop 1440×900: rolar até o cartão, mouse, clique | entrada só ao aparecer; chega ao destino em ~1,6 s; **0 violações de CSP, 0 erros ou avisos de console** |
| Esc durante a expansão | fica na página, tela sobreposta removida, legenda devolvida, "Abertura do site cancelada." na região viva |
| Voltar pelo histórico depois de chegar ao destino | página Sobre sem tela sobreposta (bfcache) |
| Movimento reduzido (sistema) | link comum: navega em ~80 ms, nada escondido nem animado |
| Celular 390×844 (toque) | mesma sequência, foto e nome cabem na tela; chega ao destino |
| Flip do nome | a caixa inicial no portal coincide com a do cartão (493, 613, 225×48) |
| `npm run build` duas vezes | saída idêntica |

Não executado neste ambiente: PHPUnit, PHPStan e PHP-CS-Fixer (sem Firebird nem PHP 8.4+ aqui). A asserção nova do
`WebSecurityTest` foi conferida contra o HTML renderizado; a suíte completa roda na CI.

Capturas ([pasta](2026-10-19-cartao-de-autoria/capturas)): 01 cartão no desktop · 02 entrada (foto abrindo do
centro) · 03 clique: tremor e anéis · 04 expansão com foto e nome em Flip · 05 tela cheia "Abrindo
devfelipegomes.com.br" · 06 celular · 07 celular: tremor, expansão e tela cheia · 08 depois do Esc · 09 movimento
reduzido. Vídeos ([pasta](2026-10-19-cartao-de-autoria/videos)): sequência completa no desktop e no celular.

## Segunda rodada: foto em círculo e personagens 3D

Pedido: foto em círculo e, junto dela, dois personagens em 3D, todos
interagindo com o mouse. Feito em CSS 3D + SVG ([`templates/_autoria.twig`](../../templates/_autoria.twig)) e GSAP,
sem WebGL nem biblioteca nova; valores no [adendo da ADR-0012](../adr/0012-identidade-motion-e-build-de-assets.md#adendo-19102026--cartão-de-autoria-na-página-sobre).

| Onde | Mudança |
|---|---|
| `templates/_autoria.twig` (novo) | macros do mascote (caixas 3D com seis faces: corpo, braços, pernas, olhos) e da nuvem extrudada em camadas, e os desenhos compartilhados (`<defs>`) |
| `templates/about.twig` | palco com a foto em círculo, a nuvem (no alto, à esquerda) e o mascote (embaixo, à direita) |
| `resources/css/app.css` | palco medido em `em`; caixa 3D genérica (`.vx`); poses de repouso; olhos "> <", aceno e cursor do ">_" no hover |
| `resources/js/pages/sobre.js` | entrada (íris, giro, queda), olhar para o mouse na página inteira, pulo e giro no clique, cópia do palco para a tela cheia |
| `public/assets/img/autor/felipe-gomes.webp` | recorte quadrado no rosto (320×320, 16 KB) |

| Verificação (Chromium 141, mesmos cabeçalhos) | Resultado |
|---|---|
| Desktop: mouse à esquerda/acima, à direita/abaixo e sobre o cartão | mascote vira o corpo e os olhos, nuvem gira para o cursor; no cartão, "> <" e aceno; **0 violações de CSP, 0 erros de console** |
| Clique até o destino | pulo + volta da nuvem, palco inteiro e nome voam para a tela cheia; chega em ~1,6 s |
| Esc, voltar pelo histórico, movimento reduzido, celular | iguais à primeira rodada; com movimento reduzido os personagens aparecem parados na pose |
| Tela cheia em 1366×700 e 390×844 | palco, nome e barra cabem entre as faixas |

Capturas novas: 10 círculo e personagens · 11 mouse à esquerda · 12 mouse à direita · 13 mouse no cartão ("> <") ·
14 tela cheia com os personagens · 15 celular. Vídeos novos: 03 (desktop) e 04 (celular). As capturas 01–09 e os
vídeos 01–02 mostram a primeira versão (foto retangular).

## Terceira rodada: laranja mais vivo e navegação depois da animação

- Mascote no laranja do boneco de referência (frente `#f8600a`, medida na foto enviada), com topo mais claro e laterais
  mais escuras para manter o volume.
- A navegação começava junto com a barra de progresso (~1,5 s depois do clique) e, em conexão rápida, o site abria no
  meio da animação. Agora começa com a barra cheia, ~3 s depois do clique; clicar na tela continua pulando a espera.
