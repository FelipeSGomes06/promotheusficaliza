# Frontend da Visão geral (`pages/central.js`)

<!-- fontes: resources/js/pages/central.js -->

Componente Alpine `central` da Visão geral: filtros, lista, mapa e ficha resumida a partir do instantâneo do servidor
([`/api/central`](../../../http.md#contrato-de-get-apicentral)). Guia de uso: [Visão geral](../../../guias/visao-geral.md).

**Divisão de responsabilidades** (nunca duas camadas na mesma propriedade do mesmo elemento):

- **Alpine (build CSP):** estado — filtros, linhas, seleção, ficha, sincronização. O template só usa `x-text`/`:attr`.
- **GSAP:** movimento coordenado — ficha (abre/fecha/troca, interrompível), reorganização da lista com Flip, realce do
  que mudou numa atualização, destaque finito do ponto escolhido.
- **Leaflet:** posição geográfica. Só o interior do ícone (`.pin`) é animado, nunca o contêiner que o Leaflet posiciona.
- **Um único dono do polling:** este componente. Pedido novo cancela o anterior (`AbortController`) e só a resposta
  mais recente é aplicada (número de sequência) — [ADR-0004](../../../adr/0004-atualizacao-por-consulta-periodica.md).

```mermaid
sequenceDiagram
  participant T as Temporizador / ação
  participant C as central.load
  participant S as /api/central
  participant V as applySnapshot
  T->>C: poll | filter | manual | action
  alt já há pedido em andamento
    C-->>C: poll ignora; filtro/ação cancela o anterior
  end
  C->>S: GET (filtros da URL), 10 s de limite
  alt 401
    S-->>C: sessão expirou → recarrega a página (vai ao login)
  else resposta antiga (seq ≠ atual)
    S-->>C: descartada
  else OK
    S-->>V: instantâneo
    V-->>V: lista por chave, mapa, ficha, realce do que mudou
  else falha de rede
    C-->>C: recuo exponencial (até 60 s) e aviso "Sem conexão"
  end
```

## Funções do módulo

| Símbolo | Contrato |
|---|---|
| `positionText` | `(r)` — o que o ponto representa: local de instalação, planejado, base (não é posição recente) ou última posição recebida; furtado = "Local de instalação; localização atual desconhecida após o furto". |
| `safe` | `(code, fallback)` — só códigos `^[A-Z_]+$` entram em classes e ids de ícone. |
| `keyOf` | `(r)` — `"EQUIPAMENTO:7"` / `"AGENTE:3"`. |
| `hasPosition` | `(r)` — coordenadas numéricas presentes. |
| `clock` / `dateTime` / `brDate` / `pct` / `ago` | formatação no fuso de Marília; "não se aplica" para `null`; "há 2 min". |
| `freshnessText` | `(r)` — "Sinal atual · há 2 min", "Posição atrasada…", "Sem informação recente… — não indica falha". |
| `register` | `(Alpine)` — exportada: registra `Alpine.data('central', central)`. |
| `central` | fábrica do componente; mantém fora do proxy do Alpine o mapa, as camadas, os temporizadores e o pedido em andamento. |
| `central.on` | registra ouvinte e guarda a remoção para `destroy`. |

## Ciclo de vida

| Método | Contrato |
|---|---|
| `central.init` | Lê `visao` e `selecionar` da URL; remove as linhas renderizadas pelo servidor (`[data-ssr]`, que servem a quem está sem JavaScript); lê os filtros; aplica o instantâneo embutido (`#snapshot-data`); cria mapa e ficha; abre a ficha de `?selecionar=` se existir; agenda o polling. Pausa ao esconder a aba e retoma ao voltar; para tudo ao sair (bfcache) e retoma ao voltar; `Esc` fecha a ficha (se nenhum painel estiver aberto). Expõe `window.__pfCentral` (somente leitura: contagem de consultas, aplicadas, canceladas, animações ativas, camadas) para os scripts de verificação. |
| `central.init.tweens` / `central.init.layers` | as duas funções do diagnóstico `__pfCentral`. |
| `central.destroy` | limpa temporizadores, pedido, observadores, `matchMedia` e o mapa. |

## Dados → estado

| Método | Contrato |
|---|---|
| `central.find` | `(k)` — recurso do instantâneo atual. |
| `central.rowVM` | `(r)` — linha da lista: título "tipo · endereço", subtítulo (código e bairro), ícone, selo, nota (sem localização / sinal atrasado), motivos de atenção e assinatura (situação, atenção, atualidade) para detectar mudança. |
| `central.panelVM` | `(r)` — ficha resumida: miniatura, situação, atualidade, onde está, motivos, **próxima ação** (vinda do servidor), alertas com o acompanhamento (responsável, próxima conferência, última providência), fatos (código, manutenção, marca/modelo, órgão, endereço/base, características), link da ficha completa, "Simular falha" (só operacional e com conta). |
| `central.applySnapshot` | `(snap, reason)` — atualiza indicadores, contagens, bairros, linhas (atenção primeiro; depois os demais, radares primeiro e agentes por último; lista curta de 6 com "Ver os outros N") e marcadores; com `reason = 'filter'` anima a reorganização (Flip); com `poll`/`action` realça só o que mudou; anuncia alertas novos só no polling; mantém a ficha aberta (ou marca "não está mais nos resultados"). |
| `central.applySnapshot.byTitle` | ordem por categoria e título (pt-BR). |
| `central.applySnapshot.onEnter` | entrada das linhas novas no Flip. |
| `central.highlightChanges` | `(before, beforeKpi)` — linha que mudou pisca em turquesa (1,4 s), o anel do ponto se expande uma vez e o número do resumo que mudou troca com animação. Nada no modo reduzido. |
| `central.bumpCount` | animação curta do total. |

## Mapa

| Método | Contrato |
|---|---|
| `central.initMap` | Sem Leaflet: aviso "O mapa não pôde ser carregado. A lista traz os mesmos recursos.". Com Leaflet: mapa preso ao entorno de Marília (zoom 11–18), tiles de `MAP_TILE_URL`; 3 falhas sem nenhum tile → faixa "Mapa de fundo indisponível… pontos, lista e resumos seguem atualizados". `ResizeObserver` avisa o Leaflet quando o layout muda. |
| `central.pinHtml` | `(r)` — marcação do ponto: forma por tipo de posição (`pin--planned` borda tracejada, `pin--base` "B", `pin--stale` ⌛ pontilhado), cor pela situação, "!" de atenção. Só códigos validados e o endereço do sprite entram; nenhum texto livre. |
| `central.pinLabel` | `(r)` — rótulo acessível do ponto. |
| `central.asButton` | `(marker, label, activate)` — ponto como botão: `role`, `aria-label`, `Enter`/espaço ativam (o Leaflet só trata o antigo `keypress`). |
| `central.groups` | `()` — agrupamento por proximidade em pixels (34 px até o zoom 17, depois 6 px); o recurso escolhido nunca entra num grupo. |
| `central.syncMarkers` | `()` — cria, atualiza ou remove marcadores e grupos, reaproveitando os existentes por chave (o grupo guarda os membros do instantâneo atual). |
| `central.syncMarkers.activate` | ativação de um marcador. |
| `central.paintSelection` | `(k, marker)` — destaque e `zIndex` do escolhido. |
| `central.ring` | `(pin, times = 2)` — anel que se expande e some (destaque finito; nada pulsa sem parar). |
| `central.popPin` | `(k)` — "salto" do ponto escolhido. |
| `central.openGroup` | `(members, marker)` — grupo espalhado: aproxima o mapa; mesmo ponto (ou zoom máximo): abre uma lista para escolher. |
| `central.openGroup.onclick` | escolha de um item da lista do grupo. |
| `central.panTo` | `(r, fly = true)` — centraliza o ponto descontando a ficha aberta ao lado (desktop) ou embaixo (celular). |

## Ficha resumida

| Método | Contrato |
|---|---|
| `central.initPanel` | linhas do tempo da ficha por `gsap.matchMedia`: lateral (≥ 1100 px) ou folha inferior (celular); `inert` enquanto fechada. |
| `central.playPanel` | `(open)` — abre/fecha (interrompível; estado final no modo reduzido). |
| `central.select` | `(k, {origin, from, focus})` — escolhe um recurso: preenche a ficha, tira o ponto de grupos, destaca, abre (ou troca o conteúdo se já aberta), centraliza se veio da lista, rola a lista, grava `selecionar` na URL e dá foco ao título. |
| `central.close` | fecha, limpa a seleção na URL e devolve o foco a quem abriu. |
| `central.ensureRowVisible` | `(k)` — garante a linha visível (expande a lista se preciso). |
| `central.rowClick` | `(e, key)` — clique na linha abre a ficha (Ctrl/Shift/Meta seguem o link para a ficha completa). |
| `central.isSelected` | `(key)` — `aria-current`. |
| `central.expandOthers` | mostra todos os "Demais recursos" com Flip. |
| `central.expandOthers.onEnter` | entrada das linhas reveladas. |
| `central.showOnMap` | troca para o mapa e centraliza o escolhido. |

## Mapa × lista e filtros

| Método | Contrato |
|---|---|
| `central.setView` / `central.viewMap` / `central.viewList` | troca a visão (gravada na URL como `?visao=lista`). |
| `central.mapPressed` / `central.listPressed` | getters `aria-pressed`. |
| `central.readChips` | lê o formulário e monta os chips de filtro ativo e os contadores dos menus. |
| `central.queryString` | filtros do formulário como query. |
| `central.writeUrl` | `history.replaceState` com filtros, visão e seleção (a URL é compartilhável e sobrevive a recarregar). |
| `central.onFilterChange` | mudança de filtro (exceto a busca) aplica na hora. |
| `central.onSearchInput` | busca aplica 320 ms depois de parar de digitar. |
| `central.onSubmit` | `Enter` na busca aplica sem recarregar. |
| `central.applyFilters` | chips, URL e `load('filter')`. |
| `central.removeChip` | `(id)` — remove um filtro e anuncia. |
| `central.clearFilters` | "Limpar tudo". |
| `central.showAttention` | resumo "precisam de atenção" liga o filtro. |
| `central.hasChips` | getter. |

## Sincronização

| Método | Contrato |
|---|---|
| `central.schedule` | agenda o próximo polling: `POLL_INTERVAL_SECONDS` (mín. 3 s) ou, após falhas, `intervalo × 2^falhas` até 60 s; nada se pausado ou aba oculta. |
| `central.load` | `(reason)` — ver diagrama. Ao recuperar a conexão: "Conexão restabelecida". Depois de uma ação sem resposta, troca o aviso por "confira o resultado" com a hora dos dados. |
| `central.reportOffline` | aviso vermelho "Sem conexão com o servidor" com os dados da tela, o tempo até a nova tentativa e **Tentar agora**. |
| `central.reportOffline.onClick` | ação "Tentar agora". |
| `central.refreshNow` | botão "Atualizar agora". |
| `central.togglePause` / `central.pauseLabel` / `central.pausePressed` | pausar/retomar a atualização automática. |
| `central.renderSync` | estado da sincronização a cada segundo: `live` ("Atualizado às…"), `paused`, `offline` ("nova tentativa em N s") ou `stale` (sem sucesso há mais de 3 intervalos); "simulador ativo/parado". |
| `central.ping` | pulso do ponto verde ao voltar a "ao vivo". |
| `central.announceNewAlerts` | `(snap, mayAnnounce)` — alertas novos viram **um** aviso por vez (mesma chave), com "Ver no mapa"; mudar o filtro não anuncia alertas antigos. |
| `central.announceNewAlerts.onClick` | "Ver no mapa" do aviso. |

## Ações com o servidor

| Método | Contrato |
|---|---|
| `central.post` | `(url, btn, busyLabel)` — `POST` com `X-CSRF-Token` e `Accept: application/json`; 401/403 → "Sua sessão expirou…"; **sem resposta** → `{unknown: true}`: não dá para saber se o servidor gravou. |
| `central.acknowledge` | `(e, id)` — **Registrar ciência** (`/alertas/{id}/reconhecer`): sucesso só depois da resposta, então recarrega; desconhecido → `unconfirmed`. |
| `central.unconfirmed` | `(key, what, message)` — aviso "resultado não confirmado — Atualize os dados antes de tentar novamente" com **Atualizar agora**, e recarrega na hora ([ADR-0023, R05](../../../adr/0023-revisao-de-logica-e-fluxos.md)). |
| `central.unconfirmed.onClick` | ação "Atualizar agora". |
| `central.simulateFailure` | `(e)` — **Simular falha** (`/equipamentos/{id}/simular-falha`). |
| `central.onMotionChange` | aplica a preferência de movimento ao Leaflet e à ficha. |

Verificação registrada no navegador (13 s com o simulador: foco, seleção e mapa preservados; Flip; avisos; sem erros de
CSP) — [evidência de 18/10/2026](../../../evidencias/2026-10-18-identidade-e-motion.md). Latência em rede real não
medida.

## Relacionados

[Instantâneo (servidor)](../application/central.md) · [Template `central.twig`](../templates/macros-e-telas.md#centraltwig) ·
[Bibliotecas](entrada-e-bibliotecas.md)
