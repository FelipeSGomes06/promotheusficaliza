# ADR-0012 — Identidade de trânsito, movimento coordenado e build de assets

Data: 18/10/2026 · Situação: aceita · Substitui em parte a [ADR-0008](0008-interface-para-iniciantes.md) (paleta neutra,
fonte do sistema, "sem framework nem biblioteca nova", movimento só em CSS). Continuam valendo da ADR-0008: três
resumos, filtros essenciais à mão, mapa e lista com o mesmo conjunto, títulos "tipo · endereço", estados com cor +
forma + texto e nada animando a cada consulta.

## Contexto

O redesenho anterior resolveu a hierarquia, mas foi rejeitado por parecer genérico: identidade fraca, interações quase
imperceptíveis e uma chama na navbar que não comunica fiscalização de trânsito. O pedido (18/10/2026) fixou a base —
Tailwind CSS 4, Alpine.js no build CSP, GSAP 3 com Flip seletivo e Vite — mantendo PHP/Slim, Twig, Firebird e Leaflet,
sem enfraquecer a CSP (`script-src 'self'`, `style-src 'self'`, sem `unsafe-inline` nem `unsafe-eval`).

## Decisão

### Marca
Um **F desenhado como vias**: haste e dois braços são ruas com faixa tracejada (duas junções em T); na ponta do braço
curto há um sensor em âmbar e ondas de radar em turquesa, sobre um quadrado azul de marca. Lê como "F" em 16 px
(favicon) e como cruzamento + radar a partir de 24 px. O âmbar é a única lembrança da chama da etapa F.O.G.O.;
a explicação fica na página Sobre. Duas variantes descartadas: quadras com ondas (liam como "grade de apps"/Wi-Fi).
Arquivos: [`favicon.svg`](../../public/assets/img/favicon.svg) e o símbolo `i-brand` de
[`icons.svg`](../../public/assets/img/icons.svg) (o mesmo desenho na navbar, na gaveta, no login e na página de erro).

### Tokens ([`resources/css/app.css`](../../resources/css/app.css))
Paleta fechada (`--color-*: initial` no `@theme`): só existem as cores do sistema, também como utilitários Tailwind.

| Papel | Tokens | Uso |
|---|---|---|
| Estrutura | `graphite-950…100` (`#0f1a2b` base) | trilho de navegação, gaveta, avisos, agrupamentos do mapa |
| Leitura | `paper #edf1f7`, `surface #fff`, `ink`, `muted`, `line`, `control` | conteúdo; borda de campo 3,6:1 |
| Marca/ação | `brand-50…800` (`#2152e8` = 6,1:1 com branco) | botão primário, seleção, item ativo, foco, marca |
| Mobilidade | `teal-50/300/400/700` | ícone ativo no trilho, ondas da marca, sensores nas ilustrações, realce de dado recebido |
| Pontual | `amber-400` | sensor da marca, aviso "Demonstração" |
| Situações | `ok`, `fail`, `warn`, `stolen`, `off` (+ `-ink`, `-soft`) | só estados operacionais, sempre com forma e texto |

Contrastes calculados (WCAG): texto 15,4:1; secundário 6,2–7,0:1; ação 6,1:1; texto do trilho 14,4:1 (secundário
7,7:1); selos de situação ≥ 5,6:1. Tipografia: **Overpass** (SIL OFL 1.1), inspirada na Highway Gothic das placas de
rodovia, em toda a interface, com números tabulares nos indicadores. O ponto médio "·" vem da fonte do sistema
(`unicode-range` sem U+00B7), porque o glifo da Overpass fica deslocado.

### Composição
Aplicação, não página: trilho grafite fixo com ícones (gaveta com GSAP no celular), barra superior compacta (título,
estado da atualização, atualizar/pausar, aviso de demonstração), barra de filtros em chips (busca, Tipo e Situação com
listas de marcar, "Precisa de atenção", Mais filtros) com chips removíveis dos filtros ativos, e a área de trabalho:
resumos + lista curta à esquerda, mapa ocupando o resto e a ficha do recurso sobre o mapa (folha inferior no celular).
Miniaturas redesenhadas no mesmo estilo (malha de ruas ao fundo, via escura, contorno grafite, azul/turquesa/âmbar).

### Responsabilidades (uma camada por propriedade)

| Camada | Cuida de | Não faz |
|---|---|---|
| CSS (Tailwind + componentes) | hover/foco/pressão (120–180 ms, `scale(.97)` em 100 ms), cores, layout | sequências |
| Alpine (`@alpinejs/csp`) | estado: filtros, linhas, seleção, ficha, sincronização, preferência de movimento | animar |
| GSAP 3 + Flip | ficha (abre/fecha/troca, interrompível), gaveta, avisos, reorganização da lista, realce do que mudou, destaque do ponto | ScrollTrigger, rolagem cinematográfica, loops infinitos |
| Leaflet | posição geográfica; contêiner do mapa e transform dos marcadores | — (animamos só o `.pin` interno) |

O polling de `/api/central` tem **um** dono (o componente `central`): pedido novo cancela o anterior
(`AbortController`) e só a resposta mais recente é aplicada (número de sequência). htmx não foi adotado.

### Contrato de movimento (valores usados)

| Interação | Implementação |
|---|---|
| Hover de botão | cor/borda/sombra + ícone sobe 1 px e cresce 8% (setas deslizam 3 px), 150 ms, só com `hover: hover` |
| Pressão | `scale(0.97)` em 100 ms, sem atrasar a ação |
| Envio | rótulo vira indicador ("Simulando…", "Salvando…"), `aria-busy`, clique repetido ignorado; resultado só após a resposta |
| Sucesso | aviso grafite com ícone verde: entrada 280 ms (`power3.out`) + ícone com `back.out`, sai em 5 s (pausa com foco/mouse) |
| Erro | aviso branco com borda vermelha, tremor curto (340 ms) e **sem temporizador**; some ao fechar ou quando a causa some |
| Ficha | 300 ms ao lado (x 32→0) / 340 ms como folha (y 16%→0); partes em cascata de 35 ms; fecha 1,5× mais rápido; abrir/fechar seguidos revertem a mesma timeline e terminam no último pedido |
| Filtro | Flip de 340 ms na lista, linhas novas entram em cascata de 30 ms, contagem e chips atualizados; Flip só em ação da pessoa |
| Seleção no mapa | `back.out` de 360 ms no ponto + anel que se expande duas vezes e para |
| Mapa ↔ lista | troca de 220 ms preservando filtro e seleção; `invalidateSize` por `ResizeObserver` |
| Dados recebidos | só o que mudou: fundo turquesa na linha (1,4 s), anel no ponto, número do resumo trocado de baixo para cima; alertas novos viram **um** aviso por consulta (mesma chave) |

### Preferência de movimento
`localStorage["pf-motion"]` (`reduced`/`full`) ou o sistema (`prefers-reduced-motion`). O atributo `html[data-motion]`
comanda o CSS; `motion.reduced()` comanda GSAP (duração zero = estado final) e Leaflet (`animate:false`, zoom/fade
desligados). O botão do trilho mostra o estado **e a origem** ("por sua escolha neste navegador" × "pela preferência do
sistema"), para que movimento reduzido não seja confundido com animação quebrada; a escolha nunca é apagada sozinha.

### Build
Vite 8 gera `public/assets/build/` (CSS, módulo ES, fontes, `manifest.json`, `licencas-de-terceiros.md`) com
`base: './'`; [`ViteAssets`](../../src/Http/ViteAssets.php) escreve as tags a partir do manifesto e de `APP_BASE_PATH`,
então o mesmo build serve em `/promotheusfiscaliza` e na origem dedicada. O resultado é versionado: instalar e publicar
continuam sem Node; Node só é necessário para mudar o frontend (`npm ci && npm run build`). Versões travadas no
`package.json`/`package-lock.json`; comentários de licença preservados no bundle.

### CSP: o que foi verificado
- Alpine **CSP 3.17** usa um analisador próprio (sem `eval`/`new Function`); as expressões dos templates usam só
  acesso a propriedade, chamadas, ternários e literais. Texto do servidor entra por `x-text`/`textContent`.
- GSAP escreve estilos pelo CSSOM (permitido pela CSP). **Achado:** o Flip usa `setAttribute("style", …)` só com a
  opção `nested: true` (bloqueado sem `unsafe-inline`); o projeto não usa `nested`.
- Nenhum script inline executável, nenhum atributo `style`/`on*` no HTML (teste `WebSecurityTest`); verificado no
  Chromium com os cabeçalhos reais: zero violações em 34 carregamentos (desktop e celular, com e sem conta).

## Consequências
- Mais peso: CSS 58 KB (12,6 KB gzip), JS ≈ 205 KB (≈ 74 KB gzip, GSAP+Flip ≈ 39 KB gzip), fontes 85 KB.
- O reset do Tailwind não afeta tiles nem controles (o CSS do Leaflet não tem camada e vence; ajustes ao Leaflet ficam
  fora de camada, com seletor `.mapbox`). Tiles medidos em 256×256.
- Uma base de estilo só: o antigo `public/assets/css/app.css` e os scripts antigos foram removidos.
- GSAP usa a licença "Standard 'no charge'" da GreenSock (não é MIT), registrada em [licenças](../licencas-e-fontes.md);
  os termos precisam ser conferidos antes da publicação (a página não pôde ser lida do ambiente de desenvolvimento).

## Adendo (19/10/2026) — cartão de autoria na página Sobre

Pedido do autor: a foto dele no espaço vazio da coluna da esquerda, com "Desenvolvido por Felipe Gomes", tremendo ao
clique e levando a <https://devfelipegomes.com.br/> com uma transição "cinematográfica" que expande o cartão. Feito
só com o que o projeto já usa (Twig, CSS em `app.css`, GSAP do pacote `gsap@3.15.0`), seguindo as skills oficiais
`greensock/gsap-skills` (MIT). Nenhuma biblioteca nova.

- **Sem JavaScript ou com movimento reduzido** é um link comum (`<a rel="author">`); o nome tem cópia em texto
  oculto e o trecho animado é `aria-hidden`. Ctrl/Cmd/Shift + clique abrem a nova aba normalmente (só a foto treme).
- **Movimento** ([`pages/sobre.js`](../../resources/js/pages/sobre.js), carregado só nessa página):

| Momento | Implementação |
|---|---|
| Entrada | uma vez, quando o cartão aparece (IntersectionObserver, não ScrollTrigger): foto abre do centro como tela de cinema (clip-path, 1,1 s, `expo.inOut`) e o nome sobe letra a letra (SplitText com máscara, 35 ms). Se o cartão já está visível ao carregar, nada é escondido |
| Mouse | inclinação 3D da foto (até 8°/6°) e brilho que segue o cursor, com `quickTo`; só com ponteiro fino |
| Clique | tremor de 0,6 s que perde força (CustomWiggle, deslocamento e giro em ritmos diferentes), o cartão afunda 2,5% e dois anéis de radar se abrem; "Desenvolvido por" e o link esmaecem |
| Expansão | aos 0,55 s, uma tela sobreposta cresce a partir do retângulo do cartão (clip-path, 0,9 s) e foto e nome voam até o centro com Flip (1 s, `scale: true`); faixas de cinema entram; a navegação começa aos 0,9 s junto com a barra de progresso, e a tela fica até o navegador trocar de página |
| Saídas | clique na tela pula a espera; Esc interrompe a navegação (`window.stop()`), desfaz a expansão a 2,2× e devolve o cartão; voltar pelo histórico (bfcache) remove a tela sobreposta |

- **Leitores de tela:** "Abrindo o site de Felipe Gomes…" (e "Abertura do site cancelada.") pela região viva.
- **CSP:** SplitText, CustomEase e CustomWiggle escrevem estilos só pelo CSSOM; o Flip continua sem `nested`.
  Verificado no Chromium com os cabeçalhos reais: 0 violações e 0 erros de console (desktop, celular, Esc, voltar,
  movimento reduzido) — [evidência](../evidencias/2026-10-19-cartao-de-autoria.md).
- **Peso:** o módulo da página Sobre tem 21 KB (9,4 KB gzip) e só é baixado nela; a foto, 22 KB (WebP 440×550).
- **Sticky:** em telas a partir de 961×760 px o cartão acompanha a rolagem no espaço livre da coluna.

## Alternativas
React/Vue (reescreveria telas que já funcionam só para animar), htmx (duplicaria o polling), ScrollTrigger (rolagem
cinematográfica não serve a uma central operacional), animações só em CSS (sem interrupção nem sequência controlada).
