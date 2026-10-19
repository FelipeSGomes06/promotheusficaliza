/*
 * Visão geral — mapa, lista e ficha resumida a partir do instantâneo do servidor.
 *
 * Atualização por consulta periódica (POLL_INTERVAL_SECONDS): pausa com a aba oculta, recua em falha (até 60 s),
 * pode ser pausada e nunca move o mapa, fecha a seleção ou tira o foco de quem está usando a tela.
 * Só ações da pessoa (escolher um recurso, aproximar um grupo) movem o mapa.
 * Todo texto vindo do servidor entra no DOM por textContent (nunca innerHTML com dado externo).
 */
(() => {
  'use strict';

  const root = document.getElementById('central');
  if (!root) return;

  const ICONS = document.body.dataset.icons;
  const cfg = {
    api: root.dataset.api,
    base: root.dataset.base,
    mini: root.dataset.mini,
    tiles: root.dataset.tiles,
    attribution: root.dataset.attribution,
    poll: Math.max(3, Number(root.dataset.poll) || 5) * 1000,
    csrf: root.dataset.csrf,
    canEdit: root.dataset.canEdit === '1',
  };
  const TZ = 'America/Sao_Paulo';
  const ENUM = /^[A-Z_]+$/;
  const GLYPH = { NAO_INSTALADO: '◌', OPERACIONAL: '✓', FALHA: '!', MANUTENCAO: '⚙', DESATIVADO: '–', FURTADO: '✕', EM_ATIVIDADE: '●', EM_PAUSA: '❚❚', INDISPONIVEL: '!', FORA_DE_SERVICO: '–' };
  const POSITION = {
    INSTALADO: 'No local de instalação',
    PLANEJADO: 'Local planejado: ainda não instalado',
    BASE: 'Na base de trabalho (não é posição recente)',
    ULTIMA_POSICAO: 'Última posição recebida',
  };
  const MARILIA = [-22.2185, -49.944];
  const CLUSTER_PX = 34;
  const CLUSTER_UNTIL_ZOOM = 17;

  const $ = (sel, ctx = root) => ctx.querySelector(sel);
  const $$ = (sel, ctx = root) => Array.from(ctx.querySelectorAll(sel));
  const reducedMotion = () => {
    const pref = document.documentElement.dataset.motion;
    return pref === 'reduced' || (pref !== 'full' && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  };
  const narrow = () => window.matchMedia('(max-width: 1080px)').matches;

  /** Cria elemento; filhos string viram nós de texto (seguro contra marcação). */
  function el(tag, attrs = {}, ...children) {
    const node = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
      if (v === null || v === undefined || v === false) continue;
      if (k === 'class') node.className = v;
      else if (k === 'text') node.textContent = v;
      else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
      else node.setAttribute(k, v === true ? '' : String(v));
    }
    for (const c of children.flat()) {
      if (c === null || c === undefined || c === false) continue;
      node.append(c instanceof Node ? c : document.createTextNode(String(c)));
    }
    return node;
  }
  function icon(id) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'icon');
    svg.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', `${ICONS}#${/^i-[A-Za-z_]+$/.test(id) ? id : 'i-pin'}`);
    svg.append(use);
    return svg;
  }
  const safe = (code, fallback) => (ENUM.test(code || '') ? code : fallback);
  const badge = (code, label, glyph) => el('span', { class: `st st--${safe(code, 'NAO_SE_APLICA')}` }, el('span', { class: 'st__g', 'aria-hidden': 'true', text: glyph || GLYPH[code] || '•' }), label);
  const clock = (iso) => (iso ? new Date(iso).toLocaleTimeString('pt-BR', { timeZone: TZ }) : '—');
  const dateTime = (iso) => (iso ? new Date(iso).toLocaleString('pt-BR', { timeZone: TZ, dateStyle: 'short', timeStyle: 'short' }) : '—');
  const brDate = (ymd) => (ymd ? ymd.split('-').reverse().join('/') : '—');
  const pct = (v) => (v === null || v === undefined ? 'não se aplica' : `${(v * 100).toFixed(1).replace('.', ',')}%`);
  function ago(iso) {
    if (!iso) return 'sem registro';
    const s = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
    if (s < 60) return 'há menos de 1 min';
    if (s < 3600) return `há ${Math.round(s / 60)} min`;
    if (s < 86400) return `há ${Math.round(s / 3600)} h`;
    return `há ${Math.round(s / 86400)} dia(s)`;
  }
  const keyOf = (r) => `${r.kind}:${r.id}`;
  const hasPosition = (r) => r.position.lat !== null && Number.isFinite(Number(r.position.lat)) && Number.isFinite(Number(r.position.lng));
  const detailUrl = (r) => `${cfg.base}/${r.kind === 'AGENTE' ? 'agentes' : 'equipamentos'}/${Number(r.id)}`;
  const announce = (msg) => { const a = $('[data-announcer]'); a.textContent = ''; window.setTimeout(() => { a.textContent = msg; }, 50); };

  /** Atualidade em palavras: ausência de informação nunca vira "falha". */
  function freshnessText(r) {
    const what = r.kind === 'AGENTE' || r.position.kind === 'ULTIMA_POSICAO' ? 'Posição' : 'Sinal';
    const when = r.last_signal_at ? ` · ${ago(r.last_signal_at)}` : '';
    switch (r.freshness) {
      case 'ATUAL': return `${what} atual${when}`;
      case 'ATRASADA': return `${what} ${what === 'Sinal' ? 'atrasado' : 'atrasada'}${when}`;
      case 'SEM_INFORMACAO': return `Sem informação recente${when} — não indica falha`;
      default: return null;
    }
  }

  const params = new URLSearchParams(window.location.search);
  const state = {
    snapshot: JSON.parse(document.getElementById('snapshot-data').textContent),
    selected: params.get('selecionar'),
    view: params.get('visao') === 'lista' ? 'lista' : 'mapa',
    returnFocus: null,
    paused: false,
    failures: 0,
    timer: 0,
    nextAt: 0,
    inFlight: false,
    lastOkAt: Date.now(),
    knownAlerts: null,
  };
  const workspace = $('[data-workspace]');
  const findResource = (k) => state.snapshot.resources.find((x) => keyOf(x) === k) || null;

  function writeUrl() {
    const q = new URLSearchParams(window.location.search);
    if (state.view === 'lista') q.set('visao', 'lista'); else q.delete('visao');
    if (state.selected) q.set('selecionar', state.selected); else q.delete('selecionar');
    const qs = q.toString();
    window.history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
  }

  /* ---------------- Mapa ---------------- */
  let map = null;
  const layers = new Map(); // chave do marcador → { marker, html, label }
  const tilesBanner = $('[data-tiles-banner]');
  if (window.L) {
    map = window.L.map('map', { minZoom: 11, maxZoom: 18, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], zoomControl: true }).setView(MARILIA, 13);
    const tiles = window.L.tileLayer(cfg.tiles, { maxZoom: 19, attribution: cfg.attribution });
    let loaded = 0;
    let failed = 0;
    tiles.on('tileload', () => { loaded += 1; tilesBanner.classList.remove('is-visible'); });
    tiles.on('tileerror', () => { failed += 1; if (failed >= 3 && loaded === 0) tilesBanner.classList.add('is-visible'); });
    tiles.addTo(map);
    map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
    map.on('zoomend', () => syncMarkers());
  } else {
    tilesBanner.textContent = 'O mapa não pôde ser carregado. A lista traz os mesmos recursos.';
    tilesBanner.classList.add('is-visible');
  }

  function pinHtml(r) {
    const cls = ['pin', `pin--${safe(r.status, 'DESATIVADO')}`];
    if (r.kind === 'AGENTE' || r.position.kind === 'ULTIMA_POSICAO' || r.position.kind === 'BASE') cls.push('pin--agent');
    if (r.position.kind === 'PLANEJADO') cls.push('pin--planned');
    if (r.position.kind === 'BASE') cls.push('pin--base');
    if (r.position.freshness && r.position.freshness !== 'ATUAL') cls.push('pin--stale');
    if (state.selected === keyOf(r)) cls.push('is-selected');
    const cat = safe(r.category, 'RADAR');
    const corner = r.position.kind === 'BASE' ? '<span class="pin__kind">B</span>' : (r.position.freshness && r.position.freshness !== 'ATUAL' ? '<span class="pin__kind">⌛</span>' : '');
    const alert = r.attention.length > 0 ? '<span class="pin__badge">!</span>' : '';
    return `<span class="${cls.join(' ')}"><svg class="icon" aria-hidden="true"><use href="${ICONS}#i-${cat}"></use></svg>${alert}${corner}</span>`;
  }
  /** Marcador como botão: Enter e Espaço ativam (o Leaflet só trata o antigo "keypress"). */
  function asButton(marker, label, activate) {
    const node = marker.getElement();
    if (!node) return;
    node.setAttribute('role', 'button');
    node.setAttribute('aria-label', label);
    node.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      e.stopPropagation();
      activate();
    });
  }
  const pinLabel = (r) => `${r.headline}. ${r.status_label}. ${POSITION[r.position.kind] || ''}${r.attention.length ? `. Precisa de atenção: ${r.attention.join(', ')}` : ''}`;

  /** Agrupa pontos que se sobrepõem na tela; o selecionado fica sempre visível por conta própria. */
  function groups() {
    const located = state.snapshot.resources.filter(hasPosition);
    const out = [];
    const radius = map.getZoom() < CLUSTER_UNTIL_ZOOM ? CLUSTER_PX : 6;
    for (const r of located) {
      const p = map.latLngToLayerPoint([Number(r.position.lat), Number(r.position.lng)]);
      if (keyOf(r) === state.selected) { out.push({ p, members: [r] }); continue; }
      const g = out.find((x) => x.members[0] && keyOf(x.members[0]) !== state.selected && Math.abs(x.p.x - p.x) < radius && Math.abs(x.p.y - p.y) < radius);
      if (g) g.members.push(r); else out.push({ p, members: [r] });
    }
    return out;
  }

  function syncMarkers() {
    if (!map) return;
    const seen = new Set();
    for (const g of groups()) {
      if (g.members.length === 1) {
        const r = g.members[0];
        const k = keyOf(r);
        seen.add(k);
        const latlng = [Number(r.position.lat), Number(r.position.lng)];
        const html = pinHtml(r);
        const label = pinLabel(r);
        const existing = layers.get(k);
        if (existing) {
          existing.marker.setLatLng(latlng);
          if (existing.html !== html) { existing.marker.setIcon(window.L.divIcon({ className: 'pin-wrap', html, iconSize: [32, 32], iconAnchor: [16, 16] })); existing.html = html; }
          if (existing.label !== label) { existing.marker.getElement()?.setAttribute('aria-label', label); existing.label = label; }
          existing.marker.setZIndexOffset(k === state.selected ? 1000 : 0);
          continue;
        }
        const marker = window.L.marker(latlng, { icon: window.L.divIcon({ className: 'pin-wrap', html, iconSize: [32, 32], iconAnchor: [16, 16] }), keyboard: true, title: r.headline, zIndexOffset: k === state.selected ? 1000 : 0 });
        const activate = () => select(k, { origin: marker.getElement(), from: 'map' });
        marker.on('click', activate);
        marker.addTo(map);
        asButton(marker, label, activate);
        layers.set(k, { marker, html, label });
        continue;
      }
      const keys = g.members.map(keyOf).sort();
      const k = `grupo:${keys.join(',')}`;
      seen.add(k);
      if (layers.has(k)) continue;
      const attention = g.members.filter((r) => r.attention.length > 0).length;
      const lat = g.members.reduce((s, r) => s + Number(r.position.lat), 0) / g.members.length;
      const lng = g.members.reduce((s, r) => s + Number(r.position.lng), 0) / g.members.length;
      const html = `<span class="cluster${attention ? ' cluster--attention' : ''}">${g.members.length}</span>`;
      const marker = window.L.marker([lat, lng], { icon: window.L.divIcon({ className: 'cluster-wrap', html, iconSize: [40, 40], iconAnchor: [20, 20] }), keyboard: true, title: `${g.members.length} recursos próximos` });
      const activate = () => openGroup(g.members, marker);
      marker.on('click', activate);
      marker.addTo(map);
      asButton(marker, `${g.members.length} recursos próximos${attention ? `, ${attention} precisam de atenção` : ''}. Ative para aproximar ou escolher.`, activate);
      layers.set(k, { marker, html, label: '' });
    }
    for (const [k, m] of layers) {
      if (!seen.has(k)) { m.marker.remove(); layers.delete(k); }
    }
  }

  /** Grupo: aproxima o mapa; se já estiver perto (ou os pontos coincidirem), oferece a lista para escolher. */
  function openGroup(members, marker) {
    const bounds = window.L.latLngBounds(members.map((r) => [Number(r.position.lat), Number(r.position.lng)]));
    const span = map.latLngToLayerPoint(bounds.getNorthEast()).distanceTo(map.latLngToLayerPoint(bounds.getSouthWest()));
    if (map.getZoom() < CLUSTER_UNTIL_ZOOM && span > 2) {
      map.fitBounds(bounds.pad(0.4), { maxZoom: CLUSTER_UNTIL_ZOOM, animate: !reducedMotion() });
      return;
    }
    const list = el('ul', { class: 'cluster-list', 'aria-label': 'Recursos neste ponto' });
    for (const r of members) {
      list.append(el('li', {}, el('button', { type: 'button', onclick: () => { map.closePopup(); select(keyOf(r), { origin: marker.getElement(), from: 'map' }); } },
        icon(`i-${safe(r.category, 'RADAR')}`), el('span', {}, el('strong', { text: r.headline }), el('br'), badge(r.status, r.status_label, r.glyph)))));
    }
    window.L.popup({ maxWidth: 320 }).setLatLng(marker.getLatLng()).setContent(list).openOn(map);
    list.querySelector('button')?.focus();
  }

  /* ---------------- Lista (atualização por chave, sem perder foco) ---------------- */
  const listEl = $('[data-list]');
  const rowCache = new Map(); // chave → { sig, li }
  function rowNode(r) {
    const meta = el('span', { class: 'row__meta' }, badge(r.status, r.status_label, r.glyph));
    // Na linha, só o que não está no selo nem nos motivos: atraso de sinal/posição e falta de localização.
    if (!hasPosition(r)) meta.append(el('span', { class: 'fresh', text: 'Sem localização cadastrada' }));
    else if (r.freshness === 'ATRASADA') meta.append(el('span', { class: 'fresh fresh--ATRASADA', text: freshnessText(r) }));
    const reasons = r.attention.filter((t) => t !== r.status_label);
    const sub = el('span', { class: 'row__sub' });
    if (r.kind === 'EQUIPAMENTO') sub.append(el('span', { class: 'code-tag', text: r.code }), ' ');
    sub.append(r.region || 'sem bairro');
    const link = el('a', { class: 'row', href: detailUrl(r), 'data-key': keyOf(r), 'aria-current': state.selected === keyOf(r) ? 'true' : null },
      el('span', { class: `row__thumb${r.kind === 'AGENTE' ? ' row__thumb--agent' : ''}` }, icon(`i-${safe(r.category, 'RADAR')}`)),
      el('span', { class: 'row__main' }, el('span', { class: 'row__title', text: r.headline }), sub),
      meta,
      reasons.length ? el('span', { class: 'row__reasons' }, reasons.map((t) => el('span', { text: t }))) : null);
    return el('li', {}, link);
  }
  function renderList() {
    const res = state.snapshot.resources;
    const focusedKey = listEl.contains(document.activeElement) ? document.activeElement.closest('[data-key]')?.dataset.key : null;
    const nodes = [];
    if (res.length === 0) {
      nodes.push(el('li', { class: 'empty' }, 'Nenhum recurso atende a estes filtros. ', el('a', { href: `${cfg.base}/`, 'data-clear-filters': '' }, 'Limpar filtros')));
    }
    const byTitle = (a, b) => a.headline.localeCompare(b.headline, 'pt-BR');
    const attention = res.filter((r) => r.attention.length > 0).sort(byTitle);
    const others = res.filter((r) => r.attention.length === 0).sort(byTitle);
    const used = new Set();
    for (const [label, items] of [['Precisam de atenção', attention], ['Demais recursos', others]]) {
      if (items.length === 0) continue;
      nodes.push(el('li', { class: 'group-label', text: `${label} (${items.length})` }));
      for (const r of items) {
        const k = keyOf(r);
        used.add(k);
        const sig = JSON.stringify([r.headline, r.code, r.region, r.status, r.status_label, r.freshness, r.last_signal_at && r.freshness !== 'ATUAL' ? ago(r.last_signal_at) : '', r.position.lat, r.attention, state.selected === k]);
        let cached = rowCache.get(k);
        if (!cached || cached.sig !== sig) { cached = { sig, li: rowNode(r) }; rowCache.set(k, cached); }
        nodes.push(cached.li);
      }
    }
    for (const k of rowCache.keys()) if (!used.has(k)) rowCache.delete(k);
    const current = Array.from(listEl.children);
    if (current.length !== nodes.length || current.some((n, i) => n !== nodes[i] && !(n.classList.contains('group-label') && nodes[i].classList?.contains('group-label') && n.textContent === nodes[i].textContent))) {
      listEl.replaceChildren(...nodes);
    }
    if (focusedKey) listEl.querySelector(`[data-key="${CSS.escape(focusedKey)}"]`)?.focus({ preventScroll: true });
  }

  /* ---------------- Ficha resumida ---------------- */
  const briefEl = $('[data-brief]');
  let briefSig = '';
  function fact(dl, label, ...value) { dl.append(el('dt', { text: label }), el('dd', {}, ...value)); }
  function renderBrief(r, entering) {
    const alerts = state.snapshot.alerts.filter((a) => a.kind === r.kind && a.resource_id === r.id);
    const sig = JSON.stringify([r, alerts, ago(r.last_signal_at), cfg.canEdit]);
    if (!entering && sig === briefSig) return;
    briefSig = sig;
    const focusedAction = briefEl.contains(document.activeElement) ? document.activeElement.dataset.action : null;
    const top = el('div', { class: 'brief__top' },
      el('figure', { class: 'brief__thumb' },
        el('img', { src: `${cfg.mini}${safe(r.category, 'RADAR').toLowerCase()}.svg`, alt: '', width: 112, height: 84 }),
        el('figcaption', { text: r.kind === 'AGENTE' ? 'Ilustração da função; pessoa fictícia.' : 'Ilustração da categoria, não do modelo cadastrado.' })),
      el('div', {},
        el('h2', { id: 'brief-title', tabindex: '-1', text: r.headline }),
        el('p', { class: 'small muted' }, r.kind === 'EQUIPAMENTO' ? [el('span', { class: 'code-tag', text: r.code }), ' '] : null, `${r.category_label} · ${r.region || 'sem bairro'}`)),
      el('button', { type: 'button', class: 'btn btn--quiet btn--small brief__close', 'data-action': 'close', onclick: closeBrief, 'aria-label': 'Fechar ficha resumida' }, 'Fechar'));

    const status = el('div', { class: 'brief__state' }, el('div', {}, badge(r.status, r.status_label, r.glyph)));
    const fresh = freshnessText(r);
    if (fresh) status.append(el('div', { class: `fresh fresh--${safe(r.freshness, 'X')}`, text: fresh }));
    if (!hasPosition(r)) status.append(el('div', { class: 'fresh', text: 'Sem localização cadastrada: aparece só na lista.' }));
    else status.append(el('div', { class: 'fresh', text: `${POSITION[r.position.kind] || ''}${r.position.observed_at ? ` · ${dateTime(r.position.observed_at)}` : ''}` }));

    const body = [top, status];
    if (r.attention.length) {
      body.push(el('p', { class: 'small' }, el('strong', { text: 'Precisa de atenção: ' }), r.attention.join(' · ')));
    }
    for (const a of alerts) {
      const box = el('div', { class: `brief__alert brief__alert--${safe(a.severity, 'MEDIA')}` }, el('p', { text: a.message }),
        el('span', { class: 'small muted', text: a.state === 'RECONHECIDO' ? `Ciência registrada por ${a.ack_by || 'operador'}; o alerta se resolve sozinho quando a causa acabar.` : `Aberto em ${dateTime(a.opened_at)}.` }));
      if (cfg.canEdit && a.state === 'ABERTO') box.append(' ', el('button', { type: 'button', class: 'btn btn--small', 'data-action': `ack-${a.id}`, onclick: () => acknowledge(a.id) }, 'Registrar ciência'));
      body.push(box);
    }
    const dl = el('dl', { class: 'facts' });
    if (r.maintenance) fact(dl, 'Manutenção', badge(r.maintenance.urgency, r.maintenance.label), ` ${r.maintenance.state === 'EM_EXECUCAO' ? 'em execução · ' : ''}${r.maintenance.due_date ? `${brDate(r.maintenance.due_date)} · ` : ''}${r.maintenance.reason}`);
    fact(dl, r.kind === 'AGENTE' ? 'Identificação' : 'Marca e modelo', r.subtitle);
    fact(dl, 'Órgão', r.agency);
    if (r.place) fact(dl, r.kind === 'AGENTE' ? 'Base' : 'Endereço', r.place);
    for (const h of r.highlights || []) fact(dl, h.label, h.value);
    body.push(dl);
    const actions = el('div', { class: 'btn-row' }, el('a', { class: 'btn btn--primary', href: detailUrl(r), 'data-action': 'detail' }, 'Ver ficha completa'));
    if (hasPosition(r) && state.view === 'lista') actions.append(el('button', { type: 'button', class: 'btn', 'data-action': 'show-map', onclick: () => { setView('mapa'); panTo(r); } }, 'Mostrar no mapa'));
    body.push(actions);
    briefEl.replaceChildren(...body);
    briefEl.setAttribute('aria-labelledby', 'brief-title');
    briefEl.classList.toggle('is-entering', Boolean(entering) && !reducedMotion());
    if (focusedAction) briefEl.querySelector(`[data-action="${CSS.escape(focusedAction)}"]`)?.focus({ preventScroll: true });
  }
  function panTo(r) {
    if (map && hasPosition(r)) map.setView([Number(r.position.lat), Number(r.position.lng)], Math.max(map.getZoom(), 15), { animate: !reducedMotion() });
  }
  function select(k, { origin = null, from = 'list', focus = true } = {}) {
    const r = findResource(k);
    if (!r) return;
    state.selected = k;
    state.returnFocus = origin || document.activeElement;
    workspace.classList.add('has-selection');
    briefEl.hidden = false;
    renderBrief(r, true);
    renderList();
    syncMarkers();
    writeUrl();
    if (from === 'list' && state.view === 'mapa') panTo(r);
    if (focus) $('#brief-title', briefEl)?.focus({ preventScroll: !narrow() });
  }
  function closeBrief() {
    if (!state.selected) return;
    const previous = state.selected;
    state.selected = null;
    briefSig = '';
    briefEl.hidden = true;
    briefEl.replaceChildren();
    workspace.classList.remove('has-selection');
    renderList();
    syncMarkers();
    writeUrl();
    const back = state.returnFocus && document.contains(state.returnFocus) ? state.returnFocus : listEl.querySelector(`[data-key="${CSS.escape(previous)}"]`);
    back?.focus?.({ preventScroll: true });
  }
  root.addEventListener('keydown', (e) => { if (e.key === 'Escape' && state.selected) { e.preventDefault(); closeBrief(); } });
  listEl.addEventListener('click', (e) => {
    const link = e.target.closest('.row');
    if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
    e.preventDefault();
    select(link.dataset.key, { origin: link, from: 'list' });
  });

  /* ---------------- Mapa ou lista ---------------- */
  function setView(view) {
    state.view = view;
    workspace.dataset.view = view;
    $$('[data-view-btn]').forEach((b) => { if (b.closest('.seg')) b.setAttribute('aria-pressed', String(b.dataset.viewBtn === view)); });
    writeUrl();
    if (view === 'mapa' && map) window.setTimeout(() => map.invalidateSize(), 0);
    if (view === 'lista' && state.selected) listEl.querySelector(`[data-key="${CSS.escape(state.selected)}"]`)?.scrollIntoView({ block: 'nearest' });
    const r = state.selected && findResource(state.selected);
    if (r) { briefSig = ''; renderBrief(r, false); }
  }
  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-view-btn]');
    if (btn) { e.preventDefault(); setView(btn.dataset.viewBtn); btn.closest('.seg') ? btn.focus() : $('[data-view-btn="lista"]', $('.seg'))?.focus(); }
  });

  /* ---------------- Números do resumo (sem animação a cada atualização) ---------------- */
  function renderNumbers(snap) {
    const k = snap.kpis.equipment;
    const values = {
      operational: k.operational, in_service: k.in_service, needs_attention: snap.needs_attention, urgent: snap.maintenance.urgent.length,
      availability: k.availability, availability_confirmed: k.availability_confirmed, operational_unconfirmed: k.operational_unconfirmed,
      total: k.total, not_installed: k.not_installed, deactivated: k.deactivated, unavailable: k.unavailable, failure: k.failure,
      maintenance: k.maintenance, stolen: k.stolen, agents_active: snap.kpis.agents.active, agents_total: snap.kpis.agents.total,
      agents_stale: snap.kpis.agents.position_not_current, upcoming: snap.maintenance.upcoming.length, resources: snap.resources.length,
      without_location: snap.without_location,
    };
    for (const node of $$('[data-n]')) {
      const v = values[node.dataset.n];
      const text = node.dataset.format === 'pct' ? pct(v) : String(v ?? '—');
      if (node.textContent !== text) node.textContent = text;
    }
    $('[data-n-label="resources"]').textContent = snap.resources.length === 1 ? 'recurso' : 'recursos';
    $('[data-no-location]').hidden = snap.without_location === 0;
    const tbody = $('[data-regions]');
    tbody.replaceChildren(...snap.regions.map((r) => el('tr', {}, el('th', { scope: 'row', text: r.name }), el('td', { text: r.equipment }), el('td', { text: r.unavailable }),
      el('td', { text: pct(r.availability) }), el('td', { text: r.agents_active }), el('td', { text: r.alerts }))));
  }

  /* ---------------- Sincronização ---------------- */
  const syncBar = $('[data-sync]');
  const pauseBtn = $('[data-pause]');
  pauseBtn.hidden = false;
  function renderSync() {
    const s = state.snapshot.sync;
    let mode = 'live';
    let text = `Atualizado às ${clock(state.snapshot.generated_at)}`;
    if (state.failures > 0) {
      mode = 'offline';
      text = `Sem conexão com o servidor: mostrando os dados das ${clock(state.snapshot.generated_at)}; nova tentativa em ${Math.max(0, Math.round((state.nextAt - Date.now()) / 1000))} s`;
    } else if (state.paused) {
      mode = 'paused';
      text = `Atualização pausada: dados das ${clock(state.snapshot.generated_at)}`;
    } else if (Date.now() - state.lastOkAt > cfg.poll * 3) {
      mode = 'stale';
    }
    syncBar.dataset.state = mode;
    $('[data-sync-text]').textContent = text;
    $('[data-sim-text]').textContent = s.simulator_running ? '· simulador ativo' : '· simulador parado (sem mudanças automáticas)';
  }
  function announceNewAlerts(snap, firstRender) {
    const ids = new Set(snap.alerts.map((a) => a.id));
    const fresh = firstRender || !state.knownAlerts ? [] : snap.alerts.filter((a) => !state.knownAlerts.has(a.id));
    state.knownAlerts = ids;
    if (fresh.length) announce(fresh.length === 1 ? `Novo alerta: ${fresh[0].message}` : `${fresh.length} novos alertas.`);
  }
  function render(snap, firstRender = false) {
    state.snapshot = snap;
    renderNumbers(snap);
    renderList();
    syncMarkers();
    announceNewAlerts(snap, firstRender);
    if (state.selected) {
      const r = findResource(state.selected);
      if (r) { workspace.classList.add('has-selection'); briefEl.hidden = false; renderBrief(r, false); }
      else if (!firstRender) {
        // O recurso saiu do filtro atual: a ficha avisa em vez de sumir sem explicação.
        briefEl.replaceChildren(el('div', { class: 'aside__hint' }, 'O recurso escolhido não está mais nos resultados deste filtro. ',
          el('button', { type: 'button', class: 'btn btn--small', 'data-action': 'close', onclick: closeBrief }, 'Fechar')));
        briefSig = '';
      }
    }
    renderSync();
  }
  function schedule() {
    window.clearTimeout(state.timer);
    if (state.paused || document.hidden) return;
    const delay = state.failures ? Math.min(60000, cfg.poll * 2 ** state.failures) : cfg.poll;
    state.nextAt = Date.now() + delay;
    state.timer = window.setTimeout(refresh, delay);
  }
  async function refresh() {
    if (state.inFlight) return;
    state.inFlight = true;
    const controller = new AbortController();
    const abort = window.setTimeout(() => controller.abort(), 10000);
    try {
      const q = new URLSearchParams(window.location.search);
      q.delete('visao');
      q.delete('selecionar');
      const res = await fetch(`${cfg.api}${q.toString() ? `?${q}` : ''}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
      // Sessão expirada ou conta desativada: recarrega para o servidor levar ao login (o sistema não tem leitura pública).
      if (res.status === 401) { window.location.reload(); return; }
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const snap = await res.json();
      state.failures = 0;
      state.lastOkAt = Date.now();
      render(snap);
    } catch {
      state.failures += 1;
      renderSync();
    } finally {
      window.clearTimeout(abort);
      state.inFlight = false;
      schedule();
    }
  }
  pauseBtn.addEventListener('click', () => {
    state.paused = !state.paused;
    pauseBtn.setAttribute('aria-pressed', String(state.paused));
    pauseBtn.textContent = state.paused ? 'Retomar atualização' : 'Pausar atualização';
    if (state.paused) { window.clearTimeout(state.timer); renderSync(); } else refresh();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) window.clearTimeout(state.timer);
    else if (!state.paused) refresh();
  });
  window.setInterval(renderSync, 1000);

  async function acknowledge(id) {
    try {
      const res = await fetch(`${cfg.base}/alertas/${Number(id)}/reconhecer`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-CSRF-Token': cfg.csrf, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ note: '' }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 401 || res.status === 403) announce('Sua sessão expirou. Entre novamente para registrar ciência do alerta.');
      else announce(data.message || data.error || (res.ok ? 'Ciência registrada.' : 'Não foi possível registrar a ciência do alerta.'));
    } catch {
      announce('Sem conexão: a ciência do alerta não foi registrada.');
    }
    refresh();
  }

  /* ---------------- Filtros ---------------- */
  const form = $('[data-filters]');
  const search = $('#f-q');
  const clearLink = $('.result-count [data-clear-filters]');
  function syncFilterControls() {
    const data = new FormData(form);
    const more = ['bairro[]', 'orgao[]'].filter((n) => data.get(n)).length + (data.get('atencao') ? 1 : 0);
    const pill = $('[data-more-count]');
    pill.textContent = String(more);
    pill.hidden = more === 0;
    const any = Array.from(data.values()).some((v) => v !== '');
    clearLink.hidden = !any;
    $('[data-filter-note]').textContent = any ? ' no filtro' : '';
  }
  function applyFilters() {
    const q = new URLSearchParams();
    for (const [k, v] of new FormData(form)) if (v !== '') q.append(k, String(v));
    if (state.view === 'lista') q.set('visao', 'lista');
    if (state.selected) q.set('selecionar', state.selected);
    const qs = q.toString();
    window.history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
    syncFilterControls();
    refresh();
  }
  let typing = 0;
  form.addEventListener('change', (e) => { if (e.target !== search) applyFilters(); });
  search.addEventListener('input', () => { window.clearTimeout(typing); typing = window.setTimeout(applyFilters, 350); });
  form.addEventListener('submit', (e) => { e.preventDefault(); window.clearTimeout(typing); applyFilters(); });
  root.addEventListener('click', (e) => {
    const attention = e.target.closest('[data-attention-link]');
    if (attention) {
      e.preventDefault();
      form.querySelector('[name="atencao"]').checked = true;
      applyFilters();
      announce('Mostrando só o que precisa de atenção.');
      return;
    }
    if (!e.target.closest('[data-clear-filters]')) return;
    e.preventDefault();
    form.reset();
    form.querySelectorAll('input[type="checkbox"]').forEach((c) => { c.checked = false; });
    form.querySelectorAll('select').forEach((s) => { s.value = ''; });
    search.value = '';
    applyFilters();
    announce('Filtros limpos.');
  });

  setView(state.view);
  render(state.snapshot, true);
  syncFilterControls();
  if (state.selected && findResource(state.selected)) select(state.selected, { focus: false, from: 'url' });
  schedule();
})();
