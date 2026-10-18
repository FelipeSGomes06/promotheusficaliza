/*
 * Central operacional — mapa, lista, indicadores e alertas a partir do instantâneo do servidor.
 *
 * Atualização por consulta periódica (POLL_INTERVAL_SECONDS, padrão 5 s): pausa com a aba oculta, recua
 * exponencialmente em falha (até 60 s), pode ser pausada pelo usuário e nunca move o mapa sozinha.
 * Todo texto vindo do servidor entra no DOM por textContent (nunca innerHTML).
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
  const GLYPH = { NAO_INSTALADO: '◌', OPERACIONAL: '✓', FALHA: '!', MANUTENCAO: '⚙', DESATIVADO: '–', FURTADO: '✕', EM_ATIVIDADE: '●', EM_PAUSA: '❚', INDISPONIVEL: '!', FORA_DE_SERVICO: '–' };
  const POSITION_LABEL = { INSTALADO: 'Local de instalação', PLANEJADO: 'Local planejado (não instalado)', BASE: 'Base (sem posição recente)', ULTIMA_POSICAO: 'Última posição observada' };
  const MARILIA = [-22.2185, -49.944];

  const $ = (sel, ctx = root) => ctx.querySelector(sel);
  const $$ = (sel, ctx = root) => Array.from(ctx.querySelectorAll(sel));
  const reducedMotion = () => {
    const pref = document.documentElement.dataset.motion;
    return pref === 'reduced' || (pref !== 'full' && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  };

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
    use.setAttribute('href', `${ICONS}#${ENUM.test(id) || id.startsWith('i-') ? id : 'i-pin'}`);
    svg.append(use);
    return svg;
  }
  const badge = (code, label, glyph) => el('span', { class: `st st--${ENUM.test(code) ? code : 'NAO_SE_APLICA'}` }, el('span', { class: 'st__g', 'aria-hidden': 'true', text: glyph || GLYPH[code] || '•' }), label);
  const time = (iso) => (iso ? new Date(iso).toLocaleTimeString('pt-BR', { timeZone: TZ }) : '—');
  const dateTime = (iso) => (iso ? new Date(iso).toLocaleString('pt-BR', { timeZone: TZ, dateStyle: 'short', timeStyle: 'short' }) : '—');
  const brDate = (ymd) => (ymd ? ymd.split('-').reverse().join('/') : '—');
  const pct = (v) => (v === null || v === undefined ? 'não se aplica' : `${(v * 100).toFixed(1).replace('.', ',')}%`);
  function ago(iso) {
    if (!iso) return 'sem registro';
    const s = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
    if (s < 60) return `há ${s} s`;
    if (s < 3600) return `há ${Math.round(s / 60)} min`;
    if (s < 86400) return `há ${Math.round(s / 3600)} h`;
    return `há ${Math.round(s / 86400)} dia(s)`;
  }
  const keyOf = (r) => `${r.kind}:${r.id}`;
  const detailUrl = (r) => `${cfg.base}/${r.kind === 'AGENTE' ? 'agentes' : 'equipamentos'}/${Number(r.id)}`;
  const announce = (msg) => { const a = $('[data-announcer]'); a.textContent = ''; window.setTimeout(() => { a.textContent = msg; }, 50); };

  const state = {
    snapshot: JSON.parse(document.getElementById('snapshot-data').textContent),
    selected: null,
    returnFocus: null,
    paused: false,
    failures: 0,
    timer: 0,
    inFlight: false,
    lastOkAt: Date.now(),
    knownAlerts: null,
  };

  /* ---------------- Mapa ---------------- */
  let map = null;
  const markers = new Map();
  const tilesBanner = $('[data-tiles-banner]');
  if (window.L) {
    map = window.L.map('map', { minZoom: 11, maxZoom: 18, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], zoomControl: true }).setView(MARILIA, 13);
    const layer = window.L.tileLayer(cfg.tiles, { maxZoom: 19, attribution: cfg.attribution });
    let loaded = 0;
    let failed = 0;
    layer.on('tileload', () => { loaded += 1; tilesBanner.classList.remove('is-visible'); });
    layer.on('tileerror', () => { failed += 1; if (failed >= 3 && loaded === 0) tilesBanner.classList.add('is-visible'); });
    layer.addTo(map);
    // Marcadores menores na visão geral da cidade, para reduzir sobreposição no centro.
    const mapWrap = root.querySelector('.map-wrap');
    const syncZoom = () => { mapWrap.dataset.zoom = map.getZoom() <= 13 ? 'far' : 'near'; };
    map.on('zoomend', syncZoom);
    syncZoom();
    map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
  } else {
    tilesBanner.textContent = 'O mapa não pôde ser carregado. A lista, os indicadores e os alertas continuam completos.';
    tilesBanner.classList.add('is-visible');
  }

  function markerHtml(r) {
    const cls = ['mk', `mk--${r.kind}`, `mk--${ENUM.test(r.status) ? r.status : 'DESATIVADO'}`];
    if (r.position.freshness && r.position.freshness !== 'ATUAL') cls.push('is-stale');
    if (r.alerts > 0) cls.push('has-alert');
    if (state.selected === keyOf(r)) cls.push('is-selected');
    const cat = ENUM.test(r.category) ? r.category : 'RADAR';
    return `<span class="${cls.join(' ')}"><svg class="icon" aria-hidden="true"><use href="${ICONS}#i-${cat}"></use></svg><span class="mk__g" aria-hidden="true">${GLYPH[r.status] || '•'}</span></span>`;
  }
  function tooltipNode(r) {
    const lines = [el('strong', { text: r.title }), el('br'), `${r.category_label} · ${r.status_label}`];
    if (r.position.kind === 'ULTIMA_POSICAO') lines.push(el('br'), `Posição ${ago(r.position.observed_at)}`);
    if (r.position.kind === 'PLANEJADO') lines.push(el('br'), 'Local planejado');
    return el('span', {}, ...lines);
  }
  function syncMarkers(resources) {
    if (!map) return;
    const seen = new Set();
    for (const r of resources) {
      const lat = Number(r.position.lat);
      const lng = Number(r.position.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng) || r.position.lat === null) continue;
      const k = keyOf(r);
      seen.add(k);
      const html = markerHtml(r);
      const existing = markers.get(k);
      if (!existing) {
        const marker = window.L.marker([lat, lng], {
          icon: window.L.divIcon({ className: 'mk-wrap', html, iconSize: [34, 34], iconAnchor: [17, 17] }),
          keyboard: true,
          title: `${r.title} — ${r.status_label}`,
          alt: `${r.category_label} ${r.title}`,
          riseOnHover: true,
        });
        marker.bindTooltip(tooltipNode(r), { direction: 'top', offset: [0, -16] });
        marker.on('click', () => select(k, { origin: marker.getElement() }));
        marker.addTo(map);
        markers.set(k, { marker, html, status: r.status, lat, lng });
        continue;
      }
      if (existing.lat !== lat || existing.lng !== lng) {
        existing.marker.setLatLng([lat, lng]);
        existing.lat = lat;
        existing.lng = lng;
      }
      if (existing.html !== html) {
        const statusChanged = existing.status !== r.status;
        existing.marker.setIcon(window.L.divIcon({ className: 'mk-wrap', html, iconSize: [34, 34], iconAnchor: [17, 17] }));
        existing.html = html;
        existing.status = r.status;
        if (statusChanged && !reducedMotion()) existing.marker.getElement()?.firstElementChild?.classList.add('is-changed');
      }
      existing.marker.setTooltipContent(tooltipNode(r));
    }
    for (const [k, m] of markers) {
      if (!seen.has(k)) { m.marker.remove(); markers.delete(k); }
    }
  }

  /* ---------------- Indicadores ---------------- */
  function valueAt(path, snap) {
    if (path === 'maintenance.urgent') return snap.maintenance.urgent.length;
    if (path === 'maintenance.upcoming') return snap.maintenance.upcoming.length;
    return path.split('.').reduce((o, p) => (o == null ? undefined : o[p]), snap.kpis);
  }
  function tween(node, from, to) {
    if (reducedMotion() || !Number.isFinite(from) || !Number.isFinite(to) || from === to) { node.textContent = String(to); return; }
    const start = performance.now();
    const step = (t) => {
      const p = Math.min(1, (t - start) / 520);
      node.textContent = String(Math.round(from + (to - from) * (1 - (1 - p) ** 3)));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }
  function renderKpis(snap) {
    for (const node of $$('[data-kpi]')) {
      const v = valueAt(node.dataset.kpi, snap);
      const text = node.dataset.format === 'pct' ? pct(v) : String(v ?? '—');
      if (node.textContent === text) continue;
      const old = Number(node.textContent);
      if (node.dataset.format !== 'pct' && node.classList.contains('kpi__value')) tween(node, old, Number(v)); else node.textContent = text;
      if (node.classList.contains('kpi__value') && !reducedMotion()) {
        node.classList.remove('is-bump');
        void node.offsetWidth;
        node.classList.add('is-bump');
      }
    }
  }

  /* ---------------- Lista, alertas, manutenção e bairros ---------------- */
  function renderList(snap) {
    const box = $('[data-list]');
    box.replaceChildren();
    if (snap.resources.length === 0) {
      box.append(el('div', { class: 'empty' }, 'Nenhum recurso atende a estes filtros. ', el('a', { href: cfg.base + '/', 'data-clear-filters': '' }, 'Limpar filtros')));
      return;
    }
    const sorted = [...snap.resources].sort((a, b) => (b.alerts > 0) - (a.alerts > 0) || a.kind.localeCompare(b.kind) || a.title.localeCompare(b.title, 'pt-BR'));
    const list = el('ul', { class: 'res-list' });
    for (const r of sorted) {
      const noPosition = r.position.lat === null;
      const meta = el('span', { class: 'res__meta' }, badge(r.status, r.status_label, r.glyph));
      if (r.alerts > 0) meta.append(el('span', { text: `${r.alerts} alerta(s)` }));
      if (noPosition) meta.append(el('span', { text: 'sem localização' }));
      else if (r.position.freshness && r.position.freshness !== 'ATUAL') meta.append(el('span', { text: `posição ${ago(r.position.observed_at)}` }));
      const link = el('a', { class: 'res', href: detailUrl(r), 'data-key': keyOf(r), 'aria-current': state.selected === keyOf(r) ? 'true' : null },
        el('span', { class: `res__icon${r.kind === 'AGENTE' ? ' res__icon--agent' : ''}` }, icon(`i-${r.category}`)),
        el('span', {}, el('span', { class: 'res__title', text: r.title }), el('span', { class: 'res__sub', text: `${r.category_label} · ${r.region || 'sem bairro'}` })),
        meta);
      list.append(el('li', {}, link));
    }
    box.append(list);
  }

  function renderAlerts(snap, firstRender) {
    const box = $('[data-alerts]');
    const ids = new Set(snap.alerts.map((a) => a.id));
    const fresh = firstRender || !state.knownAlerts ? [] : snap.alerts.filter((a) => !state.knownAlerts.has(a.id));
    state.knownAlerts = ids;
    box.replaceChildren();
    if (snap.alerts.length === 0) { box.append(el('div', { class: 'empty', text: 'Nenhum alerta aberto neste filtro.' })); }
    for (const a of snap.alerts) {
      const item = el('article', { class: `alert-item alert-item--${ENUM.test(a.severity) ? a.severity : 'MEDIA'}${fresh.includes(a) && !reducedMotion() ? ' is-new' : ''}` },
        el('div', { class: 'alert-item__top' }, badge(a.severity, a.severity.charAt(0) + a.severity.slice(1).toLowerCase()), el('span', { class: 'small faint', text: dateTime(a.opened_at) })),
        el('p', { text: a.message }));
      const foot = el('div', { class: 'btn-row' });
      foot.append(el('span', { class: 'small muted', text: a.state === 'RECONHECIDO' ? `Reconhecido por ${a.ack_by || 'operador'}; resolve sozinho quando a causa acabar` : 'Aguardando reconhecimento' }));
      const resource = snap.resources.find((r) => r.kind === a.kind && r.id === a.resource_id);
      if (resource) foot.append(el('button', { type: 'button', class: 'btn btn--sm btn--ghost', onclick: () => select(keyOf(resource), { pan: true, origin: document.activeElement }) }, 'Ver no mapa'));
      if (cfg.canEdit && a.state === 'ABERTO') foot.append(el('button', { type: 'button', class: 'btn btn--sm', onclick: () => acknowledge(a.id) }, 'Reconhecer'));
      item.append(foot);
      box.append(item);
    }
    const count = $('[data-count="alerts"]');
    count.textContent = String(snap.alerts.length);
    count.classList.toggle('count--hot', snap.alerts.length > 0);
    if (fresh.length) announce(fresh.length === 1 ? `Novo alerta: ${fresh[0].message}` : `${fresh.length} novos alertas.`);
  }

  function renderMaintenance(snap) {
    const box = $('[data-maintenance]');
    box.replaceChildren();
    const block = (title, items, empty, urgent) => {
      box.append(el('p', { class: 'res-group', text: title }));
      if (items.length === 0) { box.append(el('div', { class: 'empty', text: empty })); return; }
      for (const o of items) {
        box.append(el('div', { class: 'order' },
          el('div', { class: 'order__head' },
            el('a', { href: `${cfg.base}/equipamentos/${Number(o.equipment_id)}` }, el('strong', { text: o.equipment_code })),
            urgent ? badge(o.urgency, o.urgency_label) : el('span', { class: 'small', text: brDate(o.due_date) })),
          el('p', { class: 'small muted', text: `${o.kind === 'PREVENTIVA' ? 'Preventiva' : 'Corretiva'}${o.state === 'EM_EXECUCAO' ? ' em execução' : ''} · ${o.reason}` })));
      }
    };
    block('Urgentes', snap.maintenance.urgent, 'Nenhuma manutenção urgente.', true);
    block('Próximos 7 dias', snap.maintenance.upcoming, 'Nada programado para os próximos 7 dias.', false);
  }

  function renderRegions(snap) {
    const tbody = $('[data-regions] tbody');
    tbody.replaceChildren();
    for (const r of snap.regions) {
      const bar = el('span', { class: 'bar', title: pct(r.availability) }, el('i'));
      bar.firstChild.style.width = r.availability === null ? '0' : `${Math.round(r.availability * 100)}%`;
      tbody.append(el('tr', {},
        el('th', { scope: 'row', text: r.name }),
        el('td', { text: r.equipment }),
        el('td', { text: r.unavailable }),
        el('td', {}, el('span', { text: pct(r.availability) }), bar),
        el('td', { text: r.agents_active }),
        el('td', { text: r.alerts })));
    }
  }

  /* ---------------- Ficha lateral ---------------- */
  const drawer = $('[data-drawer]');
  function fact(dl, label, ...value) { dl.append(el('dt', { text: label }), el('dd', {}, ...value)); }
  function renderDrawer(r) {
    $('[data-drawer-title]').textContent = r.title;
    const body = $('[data-drawer-body]');
    body.replaceChildren();
    body.append(el('figure', {},
      el('img', { src: `${cfg.mini}${r.category.toLowerCase()}.svg`, alt: `Ilustração: ${r.category_label}`, width: 320, height: 240 }),
      el('figcaption', { text: 'Ilustração da categoria — não representa o modelo comercial.' })));
    const dl = el('dl', { class: 'facts' });
    fact(dl, 'Situação', badge(r.status, r.status_label, r.glyph));
    fact(dl, 'Tipo', r.category_label);
    fact(dl, r.kind === 'AGENTE' ? 'Identificação' : 'Marca / modelo', r.subtitle);
    if (r.kind === 'EQUIPAMENTO') fact(dl, 'Código', r.code);
    fact(dl, 'Órgão', r.agency);
    fact(dl, 'Bairro', r.region || 'sem bairro');
    if (r.address) fact(dl, r.kind === 'AGENTE' ? 'Base' : 'Endereço', r.address);
    if (r.position.lat === null) fact(dl, 'Posição', 'Sem localização cadastrada');
    else {
      const p = [POSITION_LABEL[r.position.kind] || r.position.kind];
      if (r.position.observed_at) p.push(` · ${dateTime(r.position.observed_at)} (${ago(r.position.observed_at)})`);
      fact(dl, 'Posição', ...p);
    }
    if (r.freshness !== 'NAO_SE_APLICA') fact(dl, r.kind === 'AGENTE' ? 'Atualidade' : 'Último sinal', badge(r.freshness, r.freshness_label), r.last_signal_at ? ` ${ago(r.last_signal_at)}` : '');
    if (r.maintenance) fact(dl, 'Manutenção', badge(r.maintenance.urgency, r.maintenance.label), ` ${r.maintenance.due_date ? brDate(r.maintenance.due_date) + ' · ' : ''}${r.maintenance.reason}`);
    for (const h of r.highlights || []) fact(dl, h.label, h.value);
    if (r.alerts > 0) fact(dl, 'Alertas', `${r.alerts} aberto(s)`);
    body.append(dl);
    body.append(el('div', { class: 'btn-row' }, el('a', { class: 'btn btn--primary btn--sm', href: detailUrl(r) }, 'Ficha completa e histórico')));
  }
  function select(k, { pan = false, origin = null } = {}) {
    const r = state.snapshot.resources.find((x) => keyOf(x) === k);
    if (!r) return;
    const previous = state.selected;
    state.selected = k;
    state.returnFocus = origin || document.activeElement;
    for (const key of [previous, k]) {
      const res = state.snapshot.resources.find((x) => keyOf(x) === key);
      const m = key && markers.get(key);
      if (res && m) { m.html = markerHtml(res); m.marker.setIcon(window.L.divIcon({ className: 'mk-wrap', html: m.html, iconSize: [34, 34], iconAnchor: [17, 17] })); }
    }
    $$('.res[aria-current]').forEach((n) => n.removeAttribute('aria-current'));
    $(`.res[data-key="${CSS.escape(k)}"]`)?.setAttribute('aria-current', 'true');
    renderDrawer(r);
    drawer.classList.add('is-open');
    drawer.focus({ preventScroll: true });
    if (pan && map && r.position.lat !== null) map.panTo([r.position.lat, r.position.lng], { animate: !reducedMotion() });
  }
  function closeDrawer() {
    if (!drawer.classList.contains('is-open')) return;
    drawer.classList.remove('is-open');
    const previous = state.selected;
    state.selected = null;
    const res = state.snapshot.resources.find((x) => keyOf(x) === previous);
    const m = previous && markers.get(previous);
    if (res && m) { m.html = markerHtml(res); m.marker.setIcon(window.L.divIcon({ className: 'mk-wrap', html: m.html, iconSize: [34, 34], iconAnchor: [17, 17] })); }
    $$('.res[aria-current]').forEach((n) => n.removeAttribute('aria-current'));
    if (state.returnFocus && document.contains(state.returnFocus)) state.returnFocus.focus();
  }
  $('[data-drawer-close]').addEventListener('click', closeDrawer);
  root.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
  $('[data-list]').addEventListener('click', (e) => {
    const link = e.target.closest('.res');
    if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
    e.preventDefault();
    select(link.dataset.key, { pan: true, origin: link });
  });

  /* ---------------- Abas acessíveis ---------------- */
  const tabs = $$('[role="tab"]');
  function activate(tab) {
    for (const t of tabs) {
      const on = t === tab;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    }
    closeDrawer();
  }
  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => activate(tab));
    tab.addEventListener('keydown', (e) => {
      const next = e.key === 'ArrowRight' ? tabs[(i + 1) % tabs.length] : e.key === 'ArrowLeft' ? tabs[(i - 1 + tabs.length) % tabs.length] : null;
      if (next) { e.preventDefault(); next.focus(); activate(next); }
    });
  });

  /* ---------------- Sincronização ---------------- */
  const syncBar = $('[data-sync]');
  const pauseBtn = $('[data-pause]');
  const refreshBtn = $('[data-refresh]');
  pauseBtn.hidden = false;
  refreshBtn.hidden = false;
  function renderSync() {
    const s = state.snapshot.sync;
    const age = Date.now() - state.lastOkAt;
    let mode = 'live';
    let text = `Dados de ${time(state.snapshot.generated_at)} · último evento ${time(s.last_event_received_at)} (${ago(s.last_event_received_at)})`;
    if (state.failures > 0) {
      mode = 'offline';
      text = `Sem conexão com o servidor — mostrando dados de ${time(state.snapshot.generated_at)}; nova tentativa em ${Math.max(0, Math.round((state.nextAt - Date.now()) / 1000))} s`;
    } else if (state.paused) {
      mode = 'paused';
      text = `Atualização pausada — dados de ${time(state.snapshot.generated_at)}`;
    } else if (age > cfg.poll * 3) {
      mode = 'stale';
    }
    syncBar.dataset.state = mode;
    $('[data-sync-text]').textContent = text;
    $('[data-sim-text]').textContent = s.simulator_running
      ? `Simulador ativo · ${s.duplicates_ignored} duplicata(s) ignorada(s) · ${s.late_events} evento(s) atrasado(s) só no histórico`
      : 'Simulador parado: sem mudanças automáticas';
  }
  function render(snap, firstRender = false) {
    state.snapshot = snap;
    renderKpis(snap);
    syncMarkers(snap.resources);
    renderList(snap);
    renderAlerts(snap, firstRender);
    renderMaintenance(snap);
    renderRegions(snap);
    $('[data-count="resources"]').textContent = String(snap.resources.length);
    $('[data-scope-note]').textContent = !snap.scope.agents ? 'Agentes fora do filtro atual (agentes ativos = 0 por definição).' : (!snap.scope.equipment ? 'Equipamentos fora do filtro atual.' : '');
    if (state.selected) {
      const r = snap.resources.find((x) => keyOf(x) === state.selected);
      if (r) renderDrawer(r); else closeDrawer();
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
      const res = await fetch(cfg.api + window.location.search, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const snap = await res.json();
      state.failures = 0;
      state.lastOkAt = Date.now();
      render(snap);
    } catch (err) {
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
    pauseBtn.replaceChildren(icon(state.paused ? 'i-play' : 'i-pause'), el('span', { text: state.paused ? 'Retomar atualização' : 'Pausar atualização' }));
    if (state.paused) { window.clearTimeout(state.timer); renderSync(); } else refresh();
  });
  refreshBtn.addEventListener('click', () => refresh());
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
      announce(data.message || data.error || (res.ok ? 'Alerta reconhecido.' : 'Não foi possível reconhecer o alerta.'));
      if (res.status === 401 || res.status === 403) announce('Sessão expirada. Entre novamente para reconhecer alertas.');
    } catch {
      announce('Sem conexão: o alerta não foi reconhecido.');
    }
    refresh();
  }

  /* ---------------- Filtros ---------------- */
  const form = $('[data-filters]');
  // Em telas estreitas os filtros começam recolhidos para o mapa aparecer logo; o resumo mostra quantos estão ativos.
  const details = $('[data-filters-details]');
  if (window.matchMedia('(max-width: 900px)').matches && !window.location.search) details.open = false;
  $('[data-filters-submit]').hidden = true;
  function applyFilters() {
    const params = new URLSearchParams();
    for (const [k, v] of new FormData(form)) if (v !== '') params.append(k, String(v));
    const qs = params.toString();
    window.history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
    closeDrawer();
    refresh();
  }
  form.addEventListener('change', applyFilters);
  form.addEventListener('submit', (e) => { e.preventDefault(); applyFilters(); });
  root.addEventListener('click', (e) => {
    if (!e.target.closest('[data-clear-filters]')) return;
    e.preventDefault();
    form.querySelectorAll('input[type="checkbox"]').forEach((c) => { c.checked = false; });
    form.querySelectorAll('select').forEach((s) => { s.value = ''; });
    applyFilters();
  });

  render(state.snapshot, true);
  schedule();
})();
