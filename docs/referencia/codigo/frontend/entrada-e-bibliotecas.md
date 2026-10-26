# Frontend: entrada e bibliotecas compartilhadas

<!-- fontes: resources/js/app.js resources/js/lib/dom.js resources/js/lib/motion.js resources/js/lib/shell.js resources/js/lib/reveal.js resources/js/lib/toasts.js resources/js/lib/forms.js resources/js/lib/popovers.js resources/js/lib/picker.js resources/js/lib/cep.js -->

Scripts da aplicação em `resources/js/`, empacotados pelo Vite num **módulo ES** servido de `'self'` (a CSP não libera
nada inline) — [ADR-0012](../../../adr/0012-identidade-motion-e-build-de-assets.md). **Alpine.js no build CSP** cuida
do estado (sem `eval`/`new Function`); **GSAP** cuida do movimento; **Leaflet** (servido localmente, fora do build)
cuida dos mapas. Texto vindo do servidor entra por `textContent`/`x-text`, nunca por `innerHTML`.

**Melhoria progressiva:** sem JavaScript todas as telas funcionam por links e formulários comuns (o servidor renderiza
listas, fichas e mensagens). O JavaScript só acrescenta reatividade, mapas, ajuda ao passar o mouse, avisos animados e
atalhos. Movimento respeita a preferência da pessoa ([`motion`](#libmotionjs)).

## `app.js` — entrada única

Importa Alpine (CSP), GSAP + Flip e as bibliotecas; registra `Alpine.data('motionPref')` e `Alpine.data('nav')`; lê
`body[data-page]` e carrega **só o módulo da página**: `central` → `pages/central.js` (registrado no Alpine antes do
`start`, com `await`); `sobre` → `pages/sobre.js`; `planejar` → `pages/planejar.js`; `multas`/`multas-form` →
`pages/multas.js` (estes três em paralelo, sem atrasar a página). Depois: `initForms()`, `initPopovers()`,
`initPicker()`, `initCep()`, `Alpine.start()` e `adoptServerFlashes()`. Padrões do GSAP: `power3.out`, 0,25 s.

## `lib/dom.js`

| Símbolo | Contrato |
|---|---|
| `SVG_NS` | namespace SVG. |
| `iconsUrl` | `()` — URL do sprite `icons.svg` (de `body[data-icons]`, com versão). |
| `icon` | `(id, cls = 'icon')` — `<svg><use href="sprite#id">`; id fora de `^i-[A-Za-z_-]+$` vira `i-pin` (nunca monta URL arbitrária). |
| `el` | `(tag, attrs = {}, ...children)` — cria elemento; `class`, `text` (`textContent`), `on*` (ouvinte) e atributos; filhos string viram nós de texto. |
| `setBusy` | `(btn, busy, label = 'Enviando…')` — estado "carregando": mantém a largura, mostra indicador + texto, `aria-busy`/`aria-disabled`; embrulha texto solto em `<span>` na primeira vez. |

## `lib/motion.js`

Preferência de movimento. Vale, nesta ordem: a escolha salva neste navegador (`localStorage["pf-motion"]` =
`reduced` ou `full`) e a preferência do sistema (`prefers-reduced-motion`). O atributo `html[data-motion]` comanda o
CSS; `motion.reduced()` comanda GSAP e Leaflet. A escolha nunca é apagada sem a pessoa pedir; sem `localStorage`
(modo privado bloqueado), vale só na página.

| Símbolo | Contrato |
|---|---|
| `read` / `write` | `()` / `(value)` — acesso protegido ao `localStorage`. |
| `notify` | avisa os ouvintes (também quando a preferência do sistema muda). |
| `motion` | objeto exportado. |
| `motion.reduced` | `()` — `true` com `data-motion="reduced"` ou, sem escolha, com o sistema pedindo menos movimento. |
| `motion.source` | `()` — `'escolha'`, `'sistema'` ou `'padrão'`. |
| `motion.systemReduced` | `()` — preferência do sistema. |
| `motion.set` | `(value)` — grava a escolha e notifica. |
| `motion.onChange` | `(fn)` — registra ouvinte; devolve a função de remoção. |
| `motion.d` | `(seconds)` — duração para o GSAP (0 no modo reduzido: o estado final aparece de imediato). |
| `motionPref` | componente Alpine do botão **Animações** no menu. |
| `motionPref.init` / `motionPref.destroy` | assina/cancela `onChange`. |
| `motionPref.sync` | relê estado, origem e sistema. |
| `motionPref.toggle` | alterna `reduced`/`full` e salva. |
| `motionPref.pressed` | getter `aria-pressed`. |
| `motionPref.stateLabel` | getter "Animações reduzidas" / "Animações ativas". |
| `motionPref.whyLabel` | getter: "Por sua escolha neste navegador…", "Pela preferência do sistema…" ou "Ativadas por sua escolha (o sistema pede menos movimento)…". |

## `lib/shell.js`

| Símbolo | Contrato |
|---|---|
| `nav` | componente Alpine do menu principal (`aside.rail`). Desktop: trilho fixo, sem animação. Celular (`max-width: 1099px`): gaveta com GSAP (entrada 0,32 s; saída mais rápida), **interrompível** (abrir/fechar seguidos terminam no último estado). |
| `nav.init` | liga o botão ☰ e o fundo escurecido; monta a linha do tempo da gaveta por `gsap.matchMedia` (cascata nos links; sem conta, no rodapé). |
| `nav.destroy` | desfaz o `matchMedia`. |
| `nav.toggle` / `nav.close` / `nav.closeFromKey` | alternar; fechar (devolvendo o foco se pedido); `Esc` fecha e devolve o foco ao botão. |
| `nav.setOpen` | `(open, returnFocus, silent = false)` — atualiza `aria-expanded`/rótulo do botão, torna o conteúdo atrás **inert** enquanto aberta, toca a animação (ou o estado final no modo reduzido) e move o foco para o primeiro link. |

## `lib/reveal.js`

| Símbolo | Contrato |
|---|---|
| `reveal` | `(el, on, animate = true)` — mostra/oculta um bloco de formulário. Oculto, um `fieldset` fica **desabilitado** (não é enviado). Entrada curta (opacidade + 8 px) só no que aparece; troca rápida termina sempre na última escolha. |

## `lib/toasts.js`

Avisos ("toasts"). Regras: sucesso só depois da confirmação do servidor; **erro fica até a pessoa fechar** ou até a
causa sumir (nunca some por temporizador); mesma chave = mesmo aviso (atualiza o texto em vez de empilhar); leitores de
tela recebem o texto por regiões vivas fixas (`polite` para tudo, `assertive` só para erro). No máximo 4 visíveis (sai o
mais antigo que não seja erro). Tempos: sucesso 5 s, informação 6 s, atenção 9 s; pausam com o mouse ou o foco.

| Símbolo | Contrato |
|---|---|
| `region` | `()` — contêiner `[data-toasts]`. |
| `announce` | `(text, assertive = false)` — escreve na região viva (limpa e reescreve para forçar o anúncio). |
| `build` | `(type, title, body, actions, onClose)` — monta o aviso com ícone, título, corpo, botão fechar e ações. |
| `fill` | `(el, title, body, actions = [])` — atualiza texto e botões de ação. |
| `arm` / `hold` | inicia / pausa o temporizador de saída. |
| `enter` | `(el, type)` — entrada (desktop sobe da borda de baixo; celular desce do topo); erro treme. |
| `show` | `({type, title, body, key, actions, timeout})` — mostra ou atualiza; devolve a chave. |
| `dismiss` | `(key, instant = false)` — retira com animação curta e acomoda a pilha (Flip); `dismiss.remove` tira o elemento e anima os vizinhos. |
| `adoptServerFlashes` | `()` — mensagens do servidor (`[data-flash]` depois de um POST/303) viram avisos animados; sem script continuam na página. |

## `lib/forms.js`

| Símbolo | Contrato |
|---|---|
| `LABELS` / `busyLabel` | rótulo de "enviando" pelo texto do botão: "Entrando…", "Salvando…", "Iniciando…", "Concluindo…", "Cancelando…", "Arquivando…", "Registrando…", "Calculando…", "Simulando…", "Abrindo…", "Confirmando…". |
| `initForms` | `()` — em todo formulário `method="post"` (sem `data-no-guard`): bloqueia **envio duplicado** (`data-sending`), pede confirmação quando há `data-confirm` (ações irreversíveis: arquivar, cancelar ordem, redefinir senha…) e põe o botão em "carregando" depois do evento (se outro ouvinte cancelou, nada fica preso). Voltar pelo histórico (bfcache) devolve os botões ao normal. Dá foco ao resumo de erros (`[data-error-summary]`). Link `#id` para um `<details>` o abre (ex.: "Mudar situação" → "Mais ações"). No formulário de equipamento, mostra só as características do tipo escolhido (as outras são desabilitadas) e só os tipos de local coerentes com a situação — a mesma regra do servidor. |
| `initForms.openTarget` | `(id)` — abre o `<details>` alvo. |
| `initForms.update` | atualiza características e opções de local do formulário de equipamento. |

## `lib/popovers.js`

Painéis `<details data-popover>` (aviso de demonstração, legenda, menus de filtro, indicadores) e a **ajuda contextual
"?"** (`[data-help]`, macro [`_ui.help`](../templates/macros-e-telas.md#_uitwig)). Sem script continuam `<details>`
nativos. Com script: entrada a partir do gatilho, saída curta, `Esc` fecha (o foco volta ao gatilho só se estava no
painel), clique fora fecha, um aberto por vez (a ajuda dentro de um menu não fecha o menu). A ajuda também abre ao
passar o mouse (150 ms) e ao focar pelo teclado; essa "espiada" fecha ao sair; o clique/toque fixa a caixinha. A
caixinha é posicionada presa à janela (abaixo do "?", ou acima se não couber, sem passar das bordas, inclusive em 360
px) e acompanha a rolagem; se o "?" sai da tela, fecha.

| Símbolo | Contrato |
|---|---|
| `isHelp` / `panelOf` / `areaOf` | é ajuda? · painel (na ajuda, a irmã seguinte do `<details>`) · área clicável. |
| `animateOpen` | `(d)` — entrada do painel. |
| `closePopover` | `(d, returnFocus = false)` — fecha (animação curta) e devolve o foco se pedido. |
| `closePopover.done` | conclusão do fechamento. |
| `place` | `(d)` — posição fixa da caixinha de ajuda. |
| `openHelp` | `(d, peek)` — abre como espiada ou fixa. |
| `initHelp` | `(d)` — ouvintes de mouse, foco (`:focus-visible`), saída de foco e clique. |
| `initHelp.later` | temporizador único por ajuda. |
| `initPopovers` | `(root = document)` — liga todos os painéis e os ouvintes globais (clique fora, `Esc`, rolagem, redimensionamento). |
| `initPopovers.all` / `initPopovers.reposition` | lista os painéis · reposiciona ou fecha ajudas abertas. |

Verificado no navegador em 24/10/2026: 134 ajudas em 16 telas, mouse, teclado, toque em 375/360 px, movimento reduzido
e sem JavaScript ([evidência](../../../evidencias/2026-10-24-labels-e-ajuda.md)). Leitor de tela real não foi testado.

## `lib/picker.js`

| Símbolo | Contrato |
|---|---|
| `initPicker` | `()` — para cada `[data-picker]`: sem Leaflet, esconde o mapa (o formulário segue pelo endereço); com Leaflet, mostra o mapa e as instruções (`data-needs-map`), esconde a explicação sem mapa (`data-without-map`), cria o mapa preso ao entorno de Marília (zoom mínimo 11) e um marcador. Clique marca o ponto e preenche as coordenadas (6 casas) e o texto de estado (`data-empty`, `data-marked`). Com `data-mark-center`, `Enter`/espaço no mapa marca o centro (quem não usa mouse também marca). Emite `picker:marked`. Mudança nos campos de coordenada move o marcador; `[data-clear-point]` remove o ponto. |
| `initPicker.say` | atualiza o texto de estado e remove o erro do servidor sobre o ponto quando há ponto. |
| `initPicker.mark` | marca um ponto. |
| `initPicker.fromInputs` | sincroniza o marcador com os campos. |

## `lib/cep.js`

Endereço nos formulários de equipamento e agente (`[data-cep-lookup]`). Com 8 dígitos no CEP, pergunta ao próprio
servidor (`GET /api/cep/…`, 250 ms depois do 8º dígito) e preenche a rua — que continua editável: só troca o que está
vazio ou o que o CEP anterior preencheu. Sem rua na resposta, uma linha diz o motivo. Quando o endereço muda, localiza
(`GET /api/localizar`): sem ponto, ou com o ponto **fora da área** do endereço, a base/local vai para o endereço; dentro,
fica. Com endereço preenchido, um clique no mapa só vale dentro da área (ajuste fino); fora, o ponto volta ao endereço.
"Levar … até este endereço" leva sempre. Nada impede o envio: sem JavaScript, o servidor localiza ao salvar. Desiste de
uma consulta em 8 s; consultas novas cancelam as anteriores.

| Símbolo | Contrato |
|---|---|
| `insideArea` | `(area, a, b)` — ponto dentro de `[sul, norte, oeste, leste]`. |
| `getJson` | `(url, signal)` — GET JSON `same-origin`, `no-store`; `null` em falha. |
| `initCep` | `()` — liga cada bloco de endereço. |
| `initCep.byId` / `initCep.digitsOf` / `initCep.say` | elemento por `data-*` · dígitos do CEP · texto de estado (com indicador). |
| `initCep.syncLocate` | mostra o botão "Levar … até este endereço" só com rua. |
| `initCep.addressKey` | chave rua + CEP + número (evita localizar o mesmo endereço duas vezes). |
| `initCep.locate` | `(mode = 'button')` — `button` (sempre move), `auto` (endereço mudou: move se não há ponto ou se está fora) ou `check` (clique no mapa: fora da área, volta ao endereço). |
| `initCep.backToAddress` | põe o ponto do endereço e avisa o mapa. |
| `initCep.lookup` | `(digits)` — consulta o CEP e aplica a rua. |

Mensagens: "Rua preenchida pelo CEP. Se precisar, corrija.", "O CEP indica a rua …; a que você digitou foi mantida.",
"O ponto marcado estava fora deste endereço: ele foi para o endereço informado…", "O ponto marcado já fica neste
endereço e foi mantido.", "Esse ponto fica fora do endereço informado: o ponto voltou para o endereço…".

## Relacionados

[Central (JS)](central.md) · [Multas, Planejar e Sobre (JS)](paginas.md) · [Estilos](../../../../resources/css/README.md) ·
[`resources/js/README.md`](../../../../resources/js/README.md)
