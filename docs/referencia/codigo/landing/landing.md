# Landing pública

<!-- fontes: landing/config.php landing/pagina.php landing/public/index.php landing/public/landing-assets/promotheus/boot.js landing/vite.config.js landing/src/pagina.js landing/src/movimento.js landing/src/navegacao.js landing/src/ampliar.js landing/src/rolagem.js landing/src/palco.js landing/src/cena3d.js landing/src/planos.js landing/src/planos3d.js landing/src/enfeites.js landing/src/enfeites3d.js landing/src/landing.css -->

Página pública servida em `/` (a aplicação continua em `/promotheusfiscaliza/`). **Não usa banco, sessão nem cookies**:
só monta HTML com texto, capturas reais do sistema e decoração 3D. Quem decide login é a aplicação. Operação, publicação,
configuração e troca do vídeo: [`landing/README.md`](../../../../landing/README.md). Decisões: ADR-0015, 0018, 0019 e 0020
([índice](../../../adr/README.md)). Guia do visitante: [landing](../../../guias/landing.md).

## Princípio: tudo funciona sem JavaScript e sem WebGL

| Camada | Sem JS | Sem WebGL / módulo 3D falhou | Movimento reduzido |
|---|---|---|---|
| Texto, botões, capturas | tudo no HTML | igual | igual |
| Marca da hero | SVG oficial | fica o SVG | marca 3D já pronta, sem montagem |
| Plano 3D noturno | cartaz em CSS/SVG | fica o cartaz | um quadro parado na pose de repouso |
| Enfeites junto das capturas | não aparecem (decoração, `aria-hidden`) | não aparecem | aparecem prontos e parados |
| Menu do celular | links numa faixa | — | — |
| Captura ampliada | o link abre o arquivo | — | — |

Os módulos 3D (`cena3d.js`, `planos3d.js`, `enfeites3d.js`, com three.js) **só são baixados** quando a parte
correspondente chega perto da tela e o navegador está ocioso. Abaixo de 900 px os enfeites nem aparecem.

## Servidor (PHP)

| Símbolo | Contrato |
|---|---|
| `landing/config.php` | devolve o array de configuração: `sistema_url`, `url_publica`, `autor`, `video` (`src`, `tipo`, `legendas`, `capa`), `hero` (`montagem`, `duracao`, `ponteiro`). Nada é segredo. |
| `e` | `(mixed $valor): string` — `htmlspecialchars` com `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`, UTF-8. Todo valor da configuração passa por ele. |
| `asset` | `(string $caminho): string` — URL de `landing-assets/promotheus/<caminho>` com `?v=<mtime>` quando o arquivo existe. |
| `construido` | `(string $fonte): string` — URL do arquivo com hash gerado pelo Vite, lido de `build/manifest.json` (uma vez por requisição). |
| `landing/public/index.php` | valida o vídeo (só caminho local começando com `/`, sem `..`, arquivo existente; senão "Vídeo de apresentação em breve."), envia a CSP própria (`default-src 'none'`, scripts/estilos/imagens/fontes/mídia só `'self'`, `form-action 'none'`, `frame-ancestors 'none'`), `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `Cache-Control: no-cache` e renderiza `pagina.php`. |
| `landing/pagina.php` | todo o texto. Seções: topo e navegação, hero (`#inicio`), O sistema (`#sistema`), recursos (`#recursos`: inventário, manutenção, multas, revisões, com o plano 3D entre elas), Planejar reforço (`#planejamento`), **dados e acesso** (`#dados`: o que é real e o que é demonstração; perguntas "Posso acessar sem conta?", "Está ligado a órgãos reais?" — não; "Como acompanhar as mudanças?"), chamada final, Para o avaliador (`#avaliador`, vídeo) e rodapé; o `<dialog>` de ampliação. Funções anônimas: `$img` (URL de imagem), `$captura` (figura com `srcset`, versão de celular e link de ampliação) e `$plano` (figura do plano 3D com cartaz). |
| `boot.js` | script clássico antes da pintura: põe a classe `js` e aplica `pf-motion` salvo (`reduced`/`full`) em `html[data-motion]` — a mesma chave do sistema, na mesma origem. |
| `landing/vite.config.js` | build separado do sistema para `landing/public/landing-assets/promotheus/build`, nomes com hash (fontes `.woff2`/`.svg` sem hash, via `assetFileNames`), `assetsInlineLimit: 0` (a CSP não aceita `data:`), avisos de licença preservados e `licencas-de-terceiros.md`. |

## Comportamento (JavaScript)

### Entrada, preferência, navegação, ampliação e rolagem

| Símbolo | Contrato |
|---|---|
| `pagina.js` | entrada: registra `ScrollTrigger` e chama, nesta ordem, `iniciarNavegacao`, `iniciarAmpliacao`, `iniciarPalco`, `animarRolagem`, `iniciarPlanos`, `iniciarEnfeites`. |
| `movimento` | objeto exportado com a mesma regra do sistema: vale `html[data-motion]` (escolha salva) e, sem ela, `prefers-reduced-motion`. |
| `movimento.reduzido` / `movimento.porEscolha` | movimento reduzido agora? · há escolha salva? |
| `movimento.alternar` | troca a escolha, grava em `localStorage` (ignora bloqueio) e avisa os ouvintes. |
| `movimento.aoMudar` | `(ouvinte)` — registra um ouvinte; mudanças do sistema só avisam quando não há escolha salva. |
| `avisar` | chama os ouvintes. |
| `iniciarNavegacao` | botão do menu (abre/fecha, `aria-expanded`, `Esc` devolve o foco, clique fora fecha) e botão "Reduzir movimento" (`aria-pressed` e `title` explicando se segue o sistema ou a escolha salva). |
| `iniciarNavegacao.abrir` / `iniciarNavegacao.sincronizar` | abre/fecha o painel · atualiza o botão de movimento. |
| `iniciarAmpliacao` | clique simples num `a[data-ampliar]` abre um `<dialog>` modal com a imagem grande e a legenda (= `alt`); `Esc`, Fechar ou clique fora fecham e o foco volta ao link. Com Ctrl/Meta/Shift/Alt o navegador abre o arquivo normalmente. |
| `animarRolagem` | `gsap.matchMedia` refaz as animações ao mudar largura, preferência do sistema ou escolha: cenas ativas (`is-ativa`), captura principal entrando em perspectiva, faixa da via desenhada com a rolagem, textos e telas das cenas, tarjas de cinema dos planos e placa final. Com movimento reduzido nada se desloca. Rolagem nativa (sem *pin* nem *smooth*). |
| `animarRolagem.montar` | (re)cria o `matchMedia`; o grupo `estreito` garante que o celular com movimento normal também tenha cenas. |

### Hero: `palco.js` e `cena3d.js`

| Símbolo | Contrato |
|---|---|
| `iniciarPalco` | `(raiz)` — foto de Marília atrás, marca na frente. Único módulo que ouve o mouse: depois da montagem a foto desloca até ±12 × ±8 px e a marca gira um pouco; volta ao repouso após 2,2 s parado, ao sair ou com movimento reduzido. Só baixa `cena3d.js` com a hero visível ≥ 35% por 300 ms (passar rolando não baixa); na entrada por âncora abaixo da hero, espera a rolagem inicial terminar. Sem WebGL nem baixa o módulo. |
| `iniciarPalco.ativo` / `iniciarPalco.repousar` / `iniciarPalco.limitar` / `iniciarPalco.esquecerCaixa` | ponteiro vale agora? · volta ao repouso · limita a [−1, 1] · descarta o retângulo em cache (rolagem/redimensionamento). |
| `iniciarPalco.aoEstado` | `(estado)` — `pronta` liga o ponteiro; `falhou` remove o canvas e devolve o SVG. |
| `iniciarPalco.carregar` / `iniciarPalco.comecarVigia` | importa a cena 3D e a inicia quando a hero aparece (uma vez) · observa a visibilidade da hero. |
| `depoisDaRolagemInicial` / `depoisDaRolagemInicial.uma` / `depoisDaRolagemInicial.esperar` | roda `f` uma vez depois de `scrollend` (ou 1,5 s) após o `load`. |
| `quandoOcioso` / `quandoOcioso.agendar` | `requestIdleCallback` (ou `setTimeout`) depois do `load`. Há uma cópia em `palco.js`, `planos.js` e `enfeites.js`, com prazos de 1,2 s ou 1,5 s. |
| `criarCena` | `({ host, reduzido, celular, duracao, ponteiro, nome, sombra, aoEstado })` — marca 3D construída das formas do SVG oficial (`svgParaCena`, `formaDePontos`, `formaSvg`, `extrudar`, `capsula`, `arco`), com `materiais` e `luzes`. Uma timeline GSAP pausada em cinco etapas: base azul em perspectiva → primeiro traço instalado com impacto → segundo traço encaixa → sensor acende, ondas e pulso pintam as faixas → câmera assenta e o nome se firma; ao fim, `aoEstado('pronta')`. Desenha **só quando algo mudou** e o palco está visível; perda de contexto → `aoEstado('falhou')`. Devolve `null` se o WebGL não puder ser criado. |
| `criarCena.pontoNoPercurso` / `criarCena.aplicar` / `criarCena.impacto` / `criarCena.criarTimeline` / `criarCena.criarTimeline.onComplete` | ponto do pulso ao longo da via · converte o estado animado em cena · recuo/compressão de um encaixe · monta a timeline · fim da montagem. |
| `criarCena.desenhar` / `criarCena.invalidar` / `criarCena.tique` / `criarCena.redimensionar` / `criarCena.repousar` | renderiza · marca sujo · desenha no ticker se sujo e visível · ajusta tamanho e densidade (máx. 1,5 no celular, 1,75 no desktop) · gira o pivô de volta a zero. |
| `criarCena.iniciar` | `(aoComecar)` — espera a compilação assíncrona dos shaders, chama `aoComecar` (esconde o SVG) e toca a montagem (ou salta ao fim com movimento reduzido). |
| `criarCena.definirReduzido` / `criarCena.ligarPonteiro` / `criarCena.apontar` | troca para movimento reduzido (salta ao fim) · liga o ponteiro · gira até ~5° × 3,5° com `x`, `y` em [−1, 1]. |

### Plano 3D: `planos.js` e `planos3d.js`

| Símbolo | Contrato |
|---|---|
| `iniciarPlanos` | um único `WebGLRenderer` (separado da hero), emprestado ao plano mais visível; o que sai de cena fica com o último quadro copiado **uma vez** para um canvas 2D. A rolagem conduz a câmera com inércia (~0,5 s); o tempo move o trânsito. Até 60 quadros/s; com quadros lentos a densidade de pixels cai em degraus de 0,25. Se o quadro sair preto (algumas placas no pós-processamento), tenta o desenho direto (`simplificar`); persistindo, ficam os cartazes. |
| `iniciarPlanos.onUpdate` / `iniciarPlanos.onRefresh` | progresso do plano (0 quando entra por baixo, 1 quando sai por cima). |
| `iniciarPlanos.escolher` / `iniciarPlanos.dimensionar` / `iniciarPlanos.desenhar` / `iniciarPlanos.congelar` / `iniciarPlanos.preto` / `iniciarPlanos.ativar` / `iniciarPlanos.tique` / `iniciarPlanos.carregar` | plano mais visível · tamanho do renderer · um quadro · cópia 2D do quadro · amostra 16 × 8 pontos para detectar quadro preto · troca o plano em cena · laço do ticker · importa `planos3d.js` (só a pouco mais de uma tela de distância). |
| `REPOUSO` | pose de repouso de cada plano (`radar: 0.52`) para movimento reduzido. |
| `criarMundo` | `({ celular })` — avenida noturna em metros com canteiro, calçadas, prédios (janelas por UV), postes com facho e poça de luz, árvores, carros e ônibus instanciados com faróis, lanternas e rastros, e uma **lombada eletrônica fictícia** (limite 40 km/h) cujo painel mostra a velocidade do último veículo que cruzou os laços (vermelho acima do limite). Pós-processamento com *bloom* e acabamento de filme (vinheta, grão, aberração). Tudo gerado em código e canvas (a CSP não aceita `data:` nem outras origens). |
| `criarMundo.preparar` / `criarMundo.tamanho` / `criarMundo.quadro` / `criarMundo.simplificar` | compila os shaders · tamanho e densidade (mantém o campo horizontal 2,39:1) · desenha o quadro `(nome, { p, t, parado })` · desliga o pós-processamento. |
| `criarMundo.livre` / `criarMundo.lampadaPerto` / `criarMundo.refletir` / `criarMundo.luzesVeiculo` / `criarMundo.rastros` / `criarMundo.instanciar` / `criarMundo.zDe` / `criarMundo.posicionarTransito` / `criarMundo.lombada` / `criarMundo.lombada.desenharPlaca` / `criarMundo.caminho` | posição de poste livre do cenário · lâmpada mais próxima (luz-chave) · reflexos no asfalto · geometria de faróis e lanternas · rastros de longa exposição · malha instanciada · posição de um veículo no tempo · posiciona o trânsito · monta a lombada · desenha a placa de limite · curva Catmull-Rom da câmera. |
| `aleatorio` / `suave` / `linear` / `imagem` / `pixels` / `textura` / `ruido` / `ruido.em` / `pintar` / `juntar` / `caixa` / `cilindro` / `perfil` / `extrusao` / `retangulo` / `arco` / `quad` / `texturas` / `desenharPainel` / `materialFacho` | utilidades: gerador com semente · *smoothstep* · cor linear · canvas desenhado · canvas pixel a pixel · textura de canvas · ruído de valor em mosaico (e amostra) · cor por vértice · une geometrias · caixa e cilindro posicionados · forma de pontos · extrusão com chanfro · retângulo arredondado · arco das ondas · quadrilátero solto · texturas do mundo · dígitos do painel · material do facho de luz. |

### Enfeites junto das capturas: `enfeites.js` e `enfeites3d.js`

| Símbolo | Contrato |
|---|---|
| `iniciarEnfeites` | seis objetos decorativos (`data-enfeite`: `mapa`, `camera`, `cone`, `placa`, `semaforo`, `calor`). Estado por enfeite: `entrada` (0→1 em 1,8 s, uma vez, com metade visível), `t`, `giro` (posição na janela), `x`/`y` (ponteiro perto, até ~2,5 tamanhos) e `parado`. Desenha só os visíveis, até 60 quadros/s (30 se atrasam); com movimento reduzido, prontos e parados, redesenhados só ao mudar de tamanho. Perda de contexto remove os enfeites. |
| `iniciarEnfeites.entrar` / `iniciarEnfeites.tique` / `iniciarEnfeites.carregar` | anima a entrada · laço do ticker · importa `enfeites3d.js`, cria um canvas por enfeite (lado = maior enfeite × densidade, 256–512 px) e começa. |
| `criarEnfeites` | `({ lado })` — um único `WebGLRenderer` (com `OffscreenCanvas` quando há: o quadro vira `ImageBitmap` transferido sem cópia; senão `drawImage` num canvas 2D). Devolve `null` sem WebGL. |
| `criarEnfeites.preparar` / `criarEnfeites.desenhar` / `criarEnfeites.aoPerder` | compila todos os shaders · desenha um enfeite com seu estado no contexto de destino · avisa uma vez se o contexto cair. |
| `mapa` / `mapa.atualizar` | pedaço de mapa com pinos que caem e pulsam (seção O sistema). |
| `camera` / `camera.atualizar` | câmera de monitoramento num poste curto, varrendo e seguindo o ponteiro quando ele chega perto (inventário). |
| `cone` / `cone.atualizar` / `cone.faixaCone` / `cone.faixaCone.r` | cone com faixas refletivas e sinalizador âmbar que cai, balança e assenta (manutenção) · geometria de uma faixa · raio na altura `y`. |
| `placa` / `placa.atualizar` / `placa.desenhar` | placa Mercosul **fictícia** que gira para a frente, com reflexo correndo (multas) · textura da chapa. |
| `semaforo` / `semaforo.atualizar` / `semaforo.qualAcesa` | semáforo que testa as luzes e segue o ciclo (revisões) · luz acesa no instante `t`. |
| `calor` / `calor.atualizar` / `calor.corDe` | mapa de calor em quadrados que viram colunas, com as quatro prioridades numeradas (Planejar reforço) · cor da faixa de valor. |
| `suave` / `faixa` / `quique` / `passa` / `imagem` / `textura` / `retangulo` / `chapa` / `fosco` / `mancha` / `sombra` / `halo` | utilidades: *smoothstep* · progresso limitado entre dois instantes · queda com quique curto · passa um pouco e volta · canvas desenhado · textura · retângulo arredondado · extrusão de chapa · material fosco · mancha radial · sombra de contato · halo colorido de uma luz. |

### Estilo (`landing.css`)

Tailwind não é usado na landing: CSS próprio com as cores da marca (`#2152e8`, âmbar `#ffb21a`, turquesa `#7cf5e1`),
a fonte Overpass local (licença OFL em `OFL-overpass.txt`), `html[data-motion]` comandando transições e o esconderijo dos
enfeites até o primeiro quadro e abaixo de 900 px.

## Capturas e mídia

`landing/public/landing-assets/promotheus/img/` tem **capturas reais** do sistema com dados de demonstração (WebP em
várias larguras e recorte de celular), a foto de Marília (crédito: Pedro Monteiro, ver
[licenças e fontes](../../../licencas-e-fontes.md)), a imagem de compartilhamento 1200×630 e o favicon.
`video/` tem `apresentacao.mp4`, `capa.webp` e `LEIA-ME.txt`; `config.php` aponta para os dois primeiros, então a
seção "Para o avaliador" mostra o player. Os arquivos de `build/` são **gerados** (`npm run build:landing`) e
conferidos pela CI: não edite à mão.

## Verificações registradas

Evidências datadas: [20/10](../../../evidencias/2026-10-20-landing-publica.md),
[22/10](../../../evidencias/2026-10-22-landing-atualizada.md), [planos 3D, 23/10](../../../evidencias/2026-10-23-planos-3d.md),
[enfeites 3D, 23/10](../../../evidencias/2026-10-23-enfeites-3d.md); a regressão A11 (cenas abaixo de 900 px) é coberta
por `scripts/verificacao-interface/auditoria.cjs`. Esta documentação não abriu a landing publicada.
