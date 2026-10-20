# ADR-0015 — Landing pública na raiz e marca montada em 3D

Data: 20/10/2026 · Situação: aceita · Relacionadas: [ADR-0012](0012-identidade-motion-e-build-de-assets.md) (identidade,
tokens, Overpass), [ADR-0014](0014-acesso-somente-com-conta-cadastrada.md) (acesso só com conta)

## Contexto

A raiz de `fiscaliza.devfelipegomes.com.br` mostrava o "Portal simulado" (página de teste do contêiner). O pedido
(briefing de 19/10/2026) foi uma landing pública com identidade de trânsito, uma hero em que a marca oficial se monta
em 3D (Three.js + GSAP), apresentação do sistema com capturas reais, três recursos ligados por uma via, uma chamada
final e uma última seção preta "Para o avaliador" pronta para um vídeo. Sem prometer acesso sem conta.

## Decisões

### Onde e como é servida
- **Na raiz do contêiner que já serve o sistema.** O subdomínio aponta para um servidor HostGator/Unified Layer com
  Caddy na frente do Apache do projeto; a raiz vinha de `/var/www/portal-stub`, dentro da imagem. A troca é uma linha
  de `DocumentRoot` em `docker/php/apache-site.conf` (`/app/landing/public`), sem tocar no `Alias /promotheusfiscaliza`,
  no Caddy nem criar regra que capture rotas. A página antiga continua na imagem para voltar atrás.
- **PHP simples, independente da aplicação:** `landing/public/index.php` (cabeçalhos, escape, versão de arquivos) +
  `landing/pagina.php` (template) + `landing/config.php` (único ponto de ajuste). Sem Slim, Twig, banco, sessão ou
  cookie. Compatível com PHP 8.0+ (documentado 8.1+). CSP própria e estrita: `script-src 'self'`, `style-src 'self'`,
  `media-src 'self'`, nada inline (o GSAP e o Three.js escrevem estilos pelo CSSOM).
- **Build próprio** (`landing/vite.config.js`, `npm run build:landing`), versionado como o do sistema. JS e CSS com
  hash, achados pelo `manifest.json`: uma query `?v=` na entrada faria o pedaço 3D (que importa a entrada pelo nome)
  carregar uma segunda cópia dela. Assets em `/landing-assets/promotheus/`, sem colisão com `/promotheusfiscaliza/assets`.
- **Dependências:** `three@0.186.1` (MIT, versão fixada) entra só no pedaço 3D, baixado depois que a página carregou
  e o navegador ficou ocioso; `gsap@3.15.0` (já usado pelo sistema) com ScrollTrigger. Nenhuma CDN.

### Composição
- Hero escura de asfalto (grafite `#0F1A2B`→`#0A1322`, grão só com gradientes), texto à esquerda e palco 3D à
  direita; uma pista em perspectiva (bordas contínuas, divisórias tracejadas) foge para o horizonte atrás da marca,
  longe do texto. No celular vira coluna: título, apoio e as duas chamadas cabem na primeira tela (360×640 inclusive);
  o palco vem depois.
- Seções de leitura em papel `#EDF1F7`. A captura principal é real (sistema publicado, 19/10/2026, com mapa) e ganha
  quatro pontos numerados com legenda, em vez de um dashboard montado em HTML.
- Recursos: três cenas alternadas, ligadas por uma via central (no celular, um trilho à esquerda) com um nó-sensor
  que acende em turquesa na cena ativa. Cada cena tem texto, uma captura real e um objeto 3D na beira da via
  (radar, trecho de via com marcador, placa com seta).
- Chamada final como placa de indicação azul, com filete branco interno e postes que entram numa faixa de asfalto;
  o asfalto escurece até o preto da seção "Para o avaliador", que só tem título, uma frase e o quadro 16:9.
- Cores com papéis fixos: azul `#2152E8` na marca e nas ações; turquesa só em ondas, foco no escuro e sinais;
  âmbar só no sensor e no botão de movimento reduzido ativo. Overpass em tudo.

### Materiais e luz
- Geometria a partir dos caminhos oficiais do `favicon.svg` (SVGLoader → contorno → `ExtrudeGeometry` com chanfro
  para dentro: a silhueta máxima é exatamente a oficial). O F é dividido em dois traços: haste + braço superior
  (contorno oficial sem o desvio do braço curto) e o braço curto oficial, que entra 1 unidade na haste e fica
  escondido no encaixe. Faixas tracejadas = cápsulas com o traço de 1,4 e pontas redondas; ondas = arcos com
  espessura 2 calculados dos pontos do SVG; sensor = disco torneado âmbar.
- `MeshStandardMaterial` fosco (rugosidade 0,46–0,6), reflexos de um `RoomEnvironment` pequeno (128 px; 64 px no
  celular), luz-chave lateral forte com sombra (PCF, 1024 px; 512 no celular), recorte frio do lado direito, pouca luz
  ambiente. *Tone mapping* Neutral com exposição 0,45: o canal azul da marca fica abaixo da faixa comprimida. Medido
  no navegador: base `#1d51e9` (oficial `#2152e8`), vias quase papel. Sem pós-processamento nem reflexo em tempo real.

### Coreografia (uma timeline GSAP, etapas nomeadas, ~4 s, `hero.duracao` ajusta)
| Etapa | Tempo | O que acontece |
|---|---|---|
| `quadrado` | 0–0,5 s | a base sobe deitada em perspectiva e assenta com um passe curto (0,06 rad); câmera alta à esquerda |
| `primeiroTraco` | 0,5–1,4 s | a haste é "instalada" de baixo para cima e o braço da esquerda para a direita (planos de recorte + barra clara na frente da obra), flutuando; cai e encaixa em 1,27 s |
| `segundoTraco` | 1,4–2,2 s | o braço curto desliza de lado e encosta na haste em 1,88 s — outro eixo, outro impacto |
| `sinal` | 2,2–3,2 s | o sensor acende, as duas ondas saem em sequência (mais um eco que some), um pulso de luz percorre a via e pinta as faixas quando passa |
| `marcaPronta` | 3,2–4,0 s | a câmera assenta quase de frente (13°/8°), a luz-chave sobe, "Promotheus Fiscaliza" firma abaixo |

Impactos: recuo de 0,8–0,9 unidade do conjunto, compressão da peça (0,84–0,86) e da base (0,93), retorno elástico
curto, sombra no chão que aperta, tremor de câmera de ~3 px derivado do tempo da timeline (volta a zero sozinho e
não depende do relógio da página). Nada de quique, explosão ou som. O texto e as chamadas nunca dependem da sequência.

### Quem controla o quê
| Camada | Cuida de |
|---|---|
| CSS | layout, cores, hover/foco/pressão, entrada curta do texto da hero (só deslocamento), nó ativo da via, pista e grão |
| GSAP | timeline da marca (números → Three.js), nome sob o palco, sombra do palco, entradas e *scrub* ligados à rolagem |
| Three.js | só desenha: um `WebGLRenderer`; desenho sob demanda (só quando algo mudou e o palco está visível) |

Os objetos das cenas usam **o mesmo renderer**: são desenhados num canto do canvas da hero e copiados para canvas 2D
no mesmo instante (a hero é redesenhada em seguida) — sem segundo contexto WebGL. `gsap.matchMedia()` refaz as
animações de rolagem quando muda a largura, a preferência do sistema ou o botão "Reduzir movimento".

### Falhas e acessibilidade
- Primeira pintura: marca oficial em SVG (inclinada, com sombra), nome, título e chamadas. O 3D substitui o SVG só
  quando está compilado; sem WebGL, sem o módulo, com contexto perdido ou sem JavaScript, o SVG fica.
- Movimento: mesma chave `pf-motion` do sistema (mesma origem), aplicada antes da pintura por `boot.js`; com
  movimento reduzido a marca aparece pronta, sem impactos, câmera, ponteiro, *scrub* ou entradas. Botão "Reduzir
  movimento" (com `aria-pressed`) no topo. A sequência dura ~4 s e roda uma vez; o botão "Pular animação"/"Rever
  montagem" do palco foi retirado a pedido do autor (20/10/2026).
- Ponteiro: só mouse, só com a marca pronta e visível, volta ao repouso parado 2,2 s. Nunca no toque.
- Canvas `aria-hidden`; marca, textos e links no HTML; foco turquesa no escuro e azul no claro; títulos em ordem.

## Consequências
- Peso: HTML ~14 KB; CSS 19 KB; JS da página 120 KB (47 KB gzip, GSAP + ScrollTrigger); pedaço 3D 617 KB (160 KB gzip),
  baixado depois do carregamento; capturas 21–90 KB cada (WebP, `srcset`).
- Lighthouse 12.8.2 no PHP embutido (sem gzip): desktop 90 de desempenho, 100 em acessibilidade, boas práticas e SEO;
  celular 46–58 conforme a variante, com TBT de ~1,1–1,4 s vindo da avaliação do three.js e da montagem da cena
  (geração do ambiente e shaders) sob CPU 4× mais lenta. A variante sem mapa de ambiente baixava o TBT para 0,5 s,
  mas deixava a marca escura e o F cinza no celular; foi descartada em favor das cores oficiais.
- Mudar a raiz exige reconstruir a imagem uma vez (`docker compose up -d --build`).

## Alternativas descartadas
Modelo 3D externo (GLB) — não existe e afastaria a marca do SVG oficial; CSS 3D em camadas (como o cartão de
autoria) — sem luz, sombra nem impacto críveis; vários renderers — um contexto WebGL por objeto; pin longo com
rolagem sequestrada — a página precisa de rolagem nativa e previsível; dashboard fictício em HTML — o briefing pede a
interface real.
