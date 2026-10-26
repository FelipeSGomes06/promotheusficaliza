# Vídeo de apresentação (`video/`)

<!-- fontes: video/comum.mjs video/capturar.mjs video/caixas.mjs video/quadros.mjs video/servidor.mjs video/autoria.php video/render.mjs video/motor.js video/principal.js video/roteiro.js video/sons.mjs video/cena.css video/index.html -->

> **Estado.** A pasta `video/` **não está versionada** (fora do Git em 26/10/2026) e é trabalho em andamento do autor:
> esta página só descreve o que o código faz. O guia de uso, os comandos e as decisões estão em
> [`video/README.md`](../../../../video/README.md), que continua sendo a fonte. Nada aqui foi executado para esta
> documentação (nem captura, nem prévia, nem render). Regra do projeto: o vídeo é mostrado primeiro na prévia do
> navegador; MP4 só com autorização.

Composição em **CSS 3D + GSAP** sobre **capturas autênticas** do sistema local (Playwright clicando nos controles reais
na ordem da narração), sincronizada com a narração WAV (211,570 s); render determinístico quadro a quadro com FFmpeg.

## Etapas e efeitos

| Etapa | Script | Efeito |
|---|---|---|
| Capturar | `capturar.mjs` (`--desde=central|preditiva|mapa`) | entra no sistema com conta de teste e **grava uma missão fictícia** (passo 3 de Planejar reforço); recusa seguir com missão vigente — antes, `demo:seed --reset` num ambiente de demonstração isolado (ex.: projeto Compose `pf-video`, porta 8095) |
| Personagens | `php video/autoria.php > video/capturas/autoria.json` | renderiza as macros reais de `templates/_autoria.twig` (mascote, Codex, `defs`) |
| Sons | `sons.mjs` (com `servidor.mjs` no ar) | sintetiza os efeitos dos eventos da linha do tempo → `midia/efeitos.wav` e `midia/mix.wav` |
| Prévia | `servidor.mjs` → `http://127.0.0.1:5180/` | só leitura |
| Conferir | `quadros.mjs <pasta> <t…>`, `caixas.mjs <estado> "<texto>" [tag]` | PNG de instantes · consulta de caixas medidas |
| Render | `render.mjs [--processos=4] [--de] [--ate] [--audio]` | `saida/promotheus-fiscaliza.mp4` (H.264 CRF 14, AAC 320 kb/s) — **só depois de aprovada a prévia** |

## Funções

### Captura (`comum.mjs`, `capturar.mjs`, `caixas.mjs`, `autoria.php`)

| Símbolo | Contrato |
|---|---|
| `sleep` | `(ms)` — espera. |
| `abrir` | `({ largura = 1536, altura = 864, escala = 2.5, reduzir = false })` — Chrome local com `pt-BR`, fuso de São Paulo e perfil de cor sRGB. |
| `entrar` | `(page)` — login com `PF_USUARIO`/`PF_SENHA` (exige `PF_SENHA`). |
| `arquivo` | caminho dentro de `capturas/`. |
| `caixas` / `caixas.vis` | caixas (coordenadas da página, px CSS) de tudo o que pode ser alvo de cursor, realce ou enquadramento · o elemento está visível? |
| `neutro` | estaciona o mouse fora dos controles. |
| `estado` | `(id, { esperar, mouse, nota })` — captura a janela e, se rolar, a página inteira, e guarda as caixas em `estados.json`. |
| `clicar` / `navegarPor` / `menu` | clica num seletor e espera · clica e espera a navegação · navega pelo item do menu com o texto. |
| `instantaneo` / `marcadores` | `/api/central` com a sessão · posições dos marcadores do mapa. |
| `parte1` / `parte1.recurso` | Visão geral, Equipamentos, ficha de radar (e no mapa), câmera, Agentes e ficha de agente · abre a ficha de um código. |
| `parte2` / `parte2.sync` | Visão geral: legenda, filtros por tipo, indicadores, ficha pelo marcador, três atualizações reais seguidas (`central-sim-*`) e Multas · espera a próxima consulta. |
| `parte3` | Revisões, Planejar reforço (como ler, método, confirmar, **missão fictícia confirmada**), retrospecto (semanas anterior e seguinte, antes e depois de revelar) e Sobre. |
| `camadasDoMapa` / `camadasDoMapa.visivel` / `camadasDoMapa.carregar` | separa fundo, quadrados (geometria e estilos computados) e números do mapa de calor sem mudar a tela · liga/desliga camadas · decodifica uma imagem base64. |
| `autoria.php` | `$render` (função anônima) devolve o HTML de uma macro; imprime `{mascote, codex, defs}` em JSON. |

### Composição (`motor.js`, `roteiro.js`, `principal.js`, `cena.css`, `index.html`)

| Símbolo | Contrato |
|---|---|
| `Video` | linha do tempo GSAP **pausada**, buscável; a câmera é a transformação inversa aplicada ao `#mundo`. |
| `Video.constructor` / `Video.duracao` | `(raiz, dados, { reduzido })` · duração da narração. |
| `Video.som` | `(tipo, t, dados)` — registra um evento sonoro (usado por `sons.mjs`). |
| `Video.seek` / `Video.aplicar` | busca `t` e aplica a câmera — **determinístico**: o mesmo `t` gera o mesmo quadro. |
| `Video.camera` / `Video.enquadrar` | movimento de câmera · enquadra uma caixa de uma tela. |
| `Video.tela` / `Video.imagem` / `Video.recorte` / `Video.realceEm` | cria uma tela · registra imagem e quando fica visível (para decodificar antes) · recorte de captura como plano solto · realce dentro do recorte. |
| `Video.caixa` / `Video.uniao` | caixa capturada pelo texto (tag, classe, índice, região); **falha alto** se faltar · une caixas. |
| `Tela` | captura em três camadas (menu, barra, conteúdo rolável) que podem se separar em profundidade. |
| `Tela.constructor` / `Tela.e` / `Tela.camadasDe` | cria · elemento · camadas de um estado. |
| `Tela.entrar` / `Tela.sair` / `Tela.fecharJanela` / `Tela.estado` | aparecer · sair · fechar · trocar de estado (navegação rápida; mesma página, dissolve). |
| `Tela.rolar` / `Tela.rolarPara` | rola até `y` · rola para a caixa ficar numa fração da janela. |
| `Tela.caixa` / `Tela.janela` / `Tela.mundoDe` | caixa por texto · caixa → janela (descontada a rolagem) · caixa → mundo. |
| `Tela.mostrarCursor` / `Tela.esconderCursor` / `Tela.mover` / `Tela.clicar` / `Tela.irEClicar` | cursor: aparece · some · vai até a caixa com leve curva (mão sobre o clicável) · clique com onda · move e clica (devolve o instante). |
| `Tela.realce` / `Tela.fixo` / `Tela.separar` | realce editorial · parte fixa da captura (ex.: aviso) · separa menu e barra em profundidade. |
| `marcar` / `posicionar` / `dividirVao` | realce de uma caixa entre `t0` e `t1` (contorno, foco escurecendo o resto, rótulo) · posiciona · realces vizinhos dividem o vão sem se atravessar. |
| `mapaDeCalor` / `mapaDeCalor.alinhar` / `mapaDeCalor.alinhar.tamanho` | recompõe o mapa em camadas (coincide pixel a pixel com a captura) · alinha uma camada · tamanho natural. |
| `el` | cria um elemento com classe, pai e HTML. |
| `roteiro` | `(v)` — monta toda a apresentação: `abertura`, `tecnologias`, `inventario`, `central`, `preditiva`, `encerramento`, cada bloco com o tempo e a palavra da narração que o comanda. |
| `letras` / `sobre` / `aparecer` / `sumir` / `capitulo` / `rodape` / `recorte2D` / `linha` / `sobreA` | texto letra a letra · camada sobre a tela · entrada e saída · título de capítulo · rodapé · recorte plano · linha de legenda · posiciona sobre um recorte. |
| `tecnologias.placa` / `tecnologias.etiqueta` / `inventario.linhasDe` / `central.marc` / `preditiva.diag` | placa de tecnologia · etiqueta · linhas de um tipo · marcador · diagrama. |
| `vida` | `(tl, [h, s, o, c], reduzido, som)` — personagens vivos (Haiku, Sonnet, Opus, Codex) sem mudar o desenho; parados com movimento reduzido. |
| `vida.ev` / `vida.piscar` / `vida.olhar` / `vida.olhos` / `vida.pular` / `vida.respirar` / `vida.acenar` | evento sonoro · piscar · virar · olhos · pulo · respiração · aceno. |
| `json` / `decodificar` / `quadro` | lê JSON · decodifica imagens entre `t` e `t + 4 s` · busca `t` e espera as imagens (exposto em `window.__video.quadro`). |
| `iniciarPrevia` | prévia: o tempo vem do `<audio>` da narração; espaço, ← →, `,` `.`; `?t=` começa num instante; caixa "Efeitos sonoros" alterna `mix.wav`. |
| `iniciarPrevia.encaixar` / `iniciarPrevia.mostrar` / `iniciarPrevia.ir` / `iniciarPrevia.fonte` / `iniciarPrevia.alternar` / `iniciarPrevia.laco` | escala do palco · mostra o tempo · pula · escolhe o áudio · toca/pausa · laço de quadros. |
| `fmt` | formata segundos. |

### Prévia, conferência e render (`servidor.mjs`, `quadros.mjs`, `render.mjs`)

`servidor.mjs` serve, sem dependências, esta pasta e, do projeto, `public/assets/build` (`/app/`), `public/assets/img`
(`/img/`), `resources/` e `node_modules/`, recusando caminhos fora (403) e respondendo `Range` (o `<audio>` precisa).

| Símbolo | Contrato |
|---|---|
| `arg` | lê `--nome=valor`. |
| `ffmpeg` | inicia o FFmpeg e devolve o processo e a promessa do fim (com as últimas linhas de erro). |
| `trecho` | `(k, a, b)` — um Chrome por trecho busca cada quadro `i/60`, captura PNG e envia ao FFmpeg (H.264 CRF 14, BT.709); os trechos são unidos sem recodificar e o áudio entra no fim. |

### Sons (`sons.mjs`)

Efeitos **sintetizados em código** (nenhum arquivo de terceiros) a partir de `midia/efeitos.json`; a narração fica
intacta no centro e os efeitos 15 a 24 dB abaixo, com *ducking* do whoosh pela voz.

| Símbolo | Contrato |
|---|---|
| `midia` / `lerWav` / `escreverWav` | caminho em `midia/` · lê WAV PCM · grava WAV. |
| `aleatorio` / `biquad` / `biquad.f` | gerador com semente · filtro biquad e sua função de amostra. |
| `somar` / `tom` / `ataque` / `sino` | mistura com pan e ganho · tom com harmônicos · envelope de ataque · sino FM. |
| `clique` / `whoosh` / `base` / `tamanho` | clique do mouse · deslize com intensidade e estéreo pelo movimento · tom base do personagem · tamanho do personagem. |
| `gerar` | objeto com um gerador por evento: `gerar.clique`, `gerar.camera`, `gerar.queda`, `gerar.pouso`, `gerar.quique`, `gerar.pulo`, `gerar.piscar`, `gerar.rodopio`, `gerar.aceno` (com `gerar.aceno.nota`) e `gerar.volta`. |
| `suave` / `db` | curva suave · decibéis → ganho. |

