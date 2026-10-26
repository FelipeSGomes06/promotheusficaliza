# Frontend das páginas Multas, Planejar e Sobre

<!-- fontes: resources/js/pages/multas.js resources/js/pages/planejar.js resources/js/pages/sobre.js -->

Módulos carregados sob demanda por `app.js` conforme `body[data-page]`. Todos são **melhoria progressiva**: sem
JavaScript as telas funcionam (todos os blocos aparecem, os mapas dão lugar às listas, o cartão de autoria é um link).

## `pages/multas.js` (páginas `multas` e `multas-form`)

Sem JavaScript todos os blocos aparecem (com a instrução de preencher só o da origem escolhida) e o servidor ignora ou
recusa o bloco que não se aplica. Com JavaScript, o bloco inativo fica **oculto e desabilitado** (não é enviado), mas o
que foi digitado continua no lugar se a pessoa voltar atrás; campos que só valem numa situação (`data-show-if`) ficam
fora da vista até ela, **sem deixar de ser enviados**.

| Símbolo | Contrato |
|---|---|
| `filled` | `(el)` — o bloco já tem algo digitado ou um erro (então não pode ficar escondido). |
| `showIf` | `(form)` — divulgação progressiva: `[data-show-if="campo=A B;outro=C"]` aparece quando algum campo vale um dos valores (`campo=` = em branco) ou quando já tem conteúdo/erro. Ex.: "Não tem o número do AIT?" aparece com o AIT em branco. As condições espelham o que o servidor exige. |
| `showIf.value` / `showIf.update` | valor de um campo · reavalia todos os blocos. |
| `sourceForm` | `(form)` — formulário da multa: mostra só o bloco da origem escolhida (Radar / Agente / "Ainda não sei"), o bloco de velocidades só em "Excesso de velocidade" (com os campos desabilitados fora dele) e a **observação sobre a origem** quando o órgão autuador difere do órgão do radar/agente ou o radar não combina com o fato (sem a função, nunca instalado, fixo com operador) — as mesmas comparações de [`FineService::divergence`](../application/multas.md#fineservicedivergence). Prévia da **velocidade considerada** enquanto digita (mesma regra de `FineRules::consideredSpeed`; quem grava é o servidor). |
| `sourceForm.agencyOf` / `sourceForm.radarLacks` / `sourceForm.update` | órgão da opção escolhida · radar divergente · reavaliação. |
| `suggestDescription` | `(form, kind)` — descrição sugerida a partir do tipo e do logradouro ("Excesso de velocidade na Avenida …", até 240); o que a pessoa escreveu não é trocado. |
| `suggestDescription.suggestion` / `suggestDescription.fill` | monta · aplica se vazio ou igual à sugestão anterior. |
| `checkForm` | `(form)` — conferência documental: "Data a que se refere" só com escopo "Depois da multa" (desabilitada no outro). |
| `checkForm.update` | reavaliação. |
| `reviewQuestion` | `(form)` — "Quer ser lembrado de conferir de novo?" só aparece depois do resultado; com "Em dia" vem recolhida em "Não precisa" (marcado pelo sistema, desmarcado se o resultado mudar); com pendência ou "Não conferidos", aberta. A data só vai junto com "Escolher data" (o servidor recusa as duas). |
| `reviewQuestion.sync` / `reviewQuestion.onStatus` | mostra a data e a resposta escolhida · reage ao resultado. |
| `init` | exportada: liga `sourceForm` (`[data-fine-form]`), `checkForm` (`[data-fine-check]`) e `showIf` em todos os formulários. |

## `pages/planejar.js` (página `planejar`)

Mapa de calor da grade de quadrados de 2 km a partir do JSON que o servidor deixa na página (`#plan-data`); sem
JavaScript, a lista ao lado tem a mesma informação. Texto do servidor entra por `textContent`.

| Símbolo | Contrato |
|---|---|
| `text` | `(value)` — `<span>` com `textContent` (rótulos do Leaflet sem HTML). |
| `baseMap` | `(box, data)` — mapa sem zoom pela roda, `zoomSnap` 0,25 (o retângulo de ~16 km cabe inteiro), preso ao entorno de Marília. |
| `band` | `(value, min, max)` — faixa de cor 0–4 pela **raiz** da posição entre mínimo e máximo da semana: a previsão é muito concentrada (poucos quadrados no centro somam boa parte dos registros) e em escala linear quase tudo cairia na faixa mais clara; o maior fica sempre vermelho e o menor na mais clara. |
| `cellsMap` | `(box, data)` — pinta os 64 quadrados (dica com bairro e valor ao passar/tocar) e, por cima, os prioritários numerados; tocar num prioritário abre o passo 2 para ele (`href`). |
| `cellsMap.rect` | retângulo Leaflet de um quadrado. |
| `missionForm` | `(form)` — passo 3: a **justificativa** só aparece (e só é obrigatória) quando o agente marcado não é o sugerido pela tela. |
| `missionForm.update` | reavaliação. |
| `init` | exportada: liga o formulário da missão e, com Leaflet e dados, o mapa (`#cell-map`). |

## `pages/sobre.js` (página `sobre`)

Cartão de autoria da página Sobre (adendo da [ADR-0012](../../../adr/0012-identidade-motion-e-build-de-assets.md#adendo-19102026--cartão-de-autoria-na-página-sobre)).
É um **link comum** para o portal do autor; sem JavaScript ou com movimento reduzido, o clique só navega. No palco: a
foto em círculo, o mascote do Claude Code e o símbolo do Codex em CSS 3D (`templates/_autoria.twig`). Usa GSAP com Flip,
CustomEase, CustomWiggle e SplitText.

| Símbolo | Contrato |
|---|---|
| `init` | exportada: localiza as partes do cartão (`data-author-*`, `data-mascot-*`, `data-codex-*`), liga entrada, seguimento do mouse, clique e `Esc`; volta pelo histórico devolve o cartão ao repouso. |
| `entrance` | `(parts)` — na **primeira vez** que o cartão aparece: foto abrindo em íris, nuvem girando até a pose, mascote caindo e quicando, nome subindo letra a letra (SplitText). Se o cartão já estava visível ao carregar, nada é escondido. |
| `revealTimeline` | `(parts)` — a linha do tempo da entrada (criada pausada); opacidade só nas raízes dos personagens (dentro do 3D ela achataria as faces). |
| `revealTimeline.onComplete` | limpeza ao fim. |
| `follow` | `(parts)` — com ponteiro fino, todos olham para o mouse em qualquer ponto da página enquanto o cartão está na tela: a foto inclina (e o brilho segue), o mascote vira corpo e olhos, a nuvem gira; `quickTo` reaproveita uma tween por propriedade. |
| `follow.to` / `follow.reach` / `follow.fromCenter` | auxiliares de cálculo e aplicação do seguimento. |
| `open` | `(event, parts)` — clique: Ctrl/Cmd/Shift só reage (o navegador abre outra aba); senão `tremor` + `expand` e a navegação começa quando a barra termina (~3 s); clicar na tela pula a espera. |
| `tremor` | `(parts)` — a foto treme e perde força (CustomWiggle), o mascote pula, a nuvem dá uma volta e os anéis de radar se abrem. |
| `expand` | `(parts)` — o fundo cresce a partir do cartão (`clip-path`) até cobrir a tela; palco e nome voam para o centro (Flip); faixas de cinema. |
| `go` | navegação (uma vez). |
| `cancel` | `(parts)` — `Esc`: interrompe a navegação ainda não iniciada e desfaz a expansão. |
| `reset` | `(parts)` — repouso (também ao voltar pelo histórico). |
| `copy` | `(node)` — cópia do palco para a tela cheia na pose atual: atributos sem `style` e estilos inline (escritos pelo GSAP) copiados pelo CSSOM, que a CSP permite. |
| `portal` | `(stage, name, host)` — tela sobreposta decorativa (o anúncio vai para a região viva). |

Verificado no Chromium headless em 19/10/2026 (entrada, tremor, expansão, `Esc`, voltar, movimento reduzido, celular,
0 violações de CSP) — [evidência](../../../evidencias/2026-10-19-cartao-de-autoria.md).

## Relacionados

[Bibliotecas](entrada-e-bibliotecas.md) · [Guia: Multas](../../../guias/multas.md) · [Guia: Planejar reforço](../../../guias/planejar-reforco.md) ·
[Guia: Sobre](../../../guias/sobre-e-interface.md)
