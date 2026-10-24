/*
 * Visão geral: filtros, lista, mapa e ficha do recurso a partir do instantâneo do servidor (/api/central).
 *
 * Responsabilidades (sem duas camadas na mesma propriedade do mesmo elemento):
 * - Alpine (build CSP): estado — filtros, linhas, seleção, ficha, sincronização. Templates só usam x-text/:attr.
 * - GSAP: movimento coordenado — ficha (abre/fecha/troca, interrompível), reorganização da lista com Flip,
 *   realce do que mudou numa atualização, destaque finito do ponto escolhido.
 * - Leaflet: posição geográfica. Animamos só o interior do ícone (.pin), nunca o contêiner que o Leaflet posiciona.
 * - Um único responsável pelo polling: este componente. Requisição nova cancela a anterior (AbortController) e só
 *   a resposta mais recente é aplicada (número de sequência).
 * Texto vindo do servidor entra por x-text/textContent, nunca por innerHTML.
 */
import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';
import { motion } from '../lib/motion.js';
import { show as toast, dismiss as dismissToast, announce } from '../lib/toasts.js';
import { el, icon, iconsUrl, setBusy } from '../lib/dom.js';

const TZ = 'America/Sao_Paulo';
const ENUM = /^[A-Z_]+$/;
const GLYPH = { NAO_INSTALADO: '◌', OPERACIONAL: '✓', FALHA: '!', MANUTENCAO: '⚙', DESATIVADO: '–', FURTADO: '✕', EM_ATIVIDADE: '●', EM_PAUSA: '❚❚', INDISPONIVEL: '!', FORA_DE_SERVICO: '–' };
const POSITION = {
  INSTALADO: 'Local de instalação',
  PLANEJADO: 'Local planejado: ainda não instalado',
  BASE: 'Base de trabalho (não é posição recente)',
  ULTIMA_POSICAO: 'Última posição recebida',
};
/** O que o ponto do mapa representa. Furtado: o ponto é de onde foi levado, não onde o equipamento está. */
const positionText = (r) => (r.status === 'FURTADO' && r.position.kind === 'INSTALADO'
  ? 'Local de instalação; localização atual desconhecida após o furto'
  : POSITION[r.position.kind] || '');
const MARILIA = [-22.2185, -49.944];
const CLUSTER_PX = 34;
const CLUSTER_UNTIL_ZOOM = 17;
const OTHERS_LIMIT = 6;
const CATEGORY_ORDER = ['RADAR', 'CAMERA', 'BALANCA', 'CANCELA', 'TOTEM', 'VEICULO', 'PATINETE', 'AG_TRANSITO', 'AG_GUARDA', 'AG_POLICIA'];
const SHEET = '(max-width: 1099px)';

const safe = (code, fallback) => (ENUM.test(code || '') ? code : fallback);
const keyOf = (r) => `${r.kind}:${r.id}`;
const hasPosition = (r) => r.position.lat !== null && Number.isFinite(Number(r.position.lat)) && Number.isFinite(Number(r.position.lng));
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

export function register(Alpine) {
  Alpine.data('central', central);
}

function central() {
  // Estado não reativo (mapa, camadas, temporizadores): fica fora do proxy do Alpine.
  const root = document.getElementById('central');
  const cfg = {
    api: root.dataset.api,
    base: root.dataset.base,
    mini: root.dataset.mini,
    miniVersion: root.dataset.miniV || '0',
    tiles: root.dataset.tiles,
    attribution: root.dataset.attribution,
    poll: Math.max(3, Number(root.dataset.poll) || 5) * 1000,
    csrf: root.dataset.csrf,
    canEdit: root.dataset.canEdit === '1',
  };
  const ICONS = iconsUrl();
  let snapshot = JSON.parse(document.getElementById('snapshot-data').textContent);
  let map = null;
  const layers = new Map(); // chave → { marker, html, label }
  let panelTl = null;
  let panelMode = null;
  let mmPanel = null;
  let timer = 0;
  let ticker = 0;
  let inflight = null; // { controller, seq, reason }
  let reqSeq = 0;
  let failures = 0;
  let unconfirmed = null; // { key, what }: ação sem resposta, conferida no próximo retrato que chegar
  let nextAt = 0;
  let lastOkAt = Date.now();
  let knownAlerts = null;
  let returnFocus = null;
  let resizeObs = null;
  const cleanups = [];
  const on = (target, type, fn, opts) => { target.addEventListener(type, fn, opts); cleanups.push(() => target.removeEventListener(type, fn, opts)); };

  return {
    kpi: {},
    count: 0,
    countLabel: 'recursos',
    filterNote: '',
    attentionRows: [],
    otherRows: [],
    othersHidden: 0,
    showAllOthers: false,
    selectedKey: null,
    // A ficha nunca fica nula: ao fechar, o conteúdo anterior permanece invisível (inert) até a próxima escolha.
    panel: { key: '', title: '', line: '', mini: '', miniCaption: '', stClass: 'st', glyph: '', statusLabel: '', fresh: '', freshClass: 'fresh', where: '', attention: '', next: { what: '', detail: '', tone: '' }, alerts: [], facts: [], detailUrl: '#', canSimulate: false, simulateId: null, located: false, gone: false },
    panelOpen: false,
    view: 'mapa',
    loading: false,
    refreshing: false,
    paused: false,
    syncState: 'live',
    syncText: '',
    simText: '',
    chips: [],
    noLocation: 0,
    regions: [],
    emptyList: false,

    /* ------------------------------------------------------------ ciclo de vida */
    init() {
      const params = new URLSearchParams(window.location.search);
      this.view = params.get('visao') === 'lista' ? 'lista' : 'mapa';
      this.selectedKey = params.get('selecionar');
      // Linhas renderizadas no servidor servem a quem está sem JavaScript; com Alpine, a lista é reativa.
      root.querySelectorAll('[data-ssr]').forEach((n) => n.remove());
      this.readChips();
      this.applySnapshot(snapshot, 'first');
      this.$nextTick(() => {
        this.initMap();
        this.initPanel();
        if (this.selectedKey && this.find(this.selectedKey)) this.select(this.selectedKey, { from: 'url', focus: false });
        else this.selectedKey = null;
        this.schedule();
      });
      on(document, 'visibilitychange', () => {
        if (document.hidden) window.clearTimeout(timer);
        else if (!this.paused) this.load('poll');
      });
      // Voltar pelo histórico (bfcache): para tudo ao sair e retoma ao voltar, sem duplicar consultas.
      on(window, 'pagehide', () => { window.clearTimeout(timer); inflight?.controller.abort('saida'); });
      on(window, 'pageshow', (e) => { if (e.persisted && !this.paused) this.load('poll'); });
      on(document, 'keydown', (e) => {
        if (e.key !== 'Escape' || !this.panelOpen) return;
        if (document.querySelector('details[data-popover][open]')) return;
        e.preventDefault();
        this.close();
      });
      ticker = window.setInterval(() => this.renderSync(), 1000);
      motion.onChange(() => this.onMotionChange());
      // Diagnóstico de verificação (somente leitura): contagem de consultas e animações ativas.
      window.__pfCentral = { polls: 0, applied: 0, aborted: 0, tweens: () => gsap.globalTimeline.getChildren(true, true, false).filter((t) => t.isActive()).length, layers: () => layers.size };
    },
    destroy() {
      window.clearTimeout(timer);
      window.clearInterval(ticker);
      inflight?.controller.abort('saida');
      resizeObs?.disconnect();
      mmPanel?.revert();
      cleanups.forEach((fn) => fn());
      map?.remove();
    },

    /* ------------------------------------------------------------ dados → estado */
    find(k) {
      return snapshot.resources.find((x) => keyOf(x) === k) || null;
    },
    rowVM(r) {
      const key = keyOf(r);
      const reasons = r.attention.filter((t) => t !== r.status_label);
      let note = '';
      let noteClass = 'fresh';
      if (!hasPosition(r)) note = 'Sem localização cadastrada';
      else if (r.freshness === 'ATRASADA') { note = freshnessText(r); noteClass = 'fresh fresh--ATRASADA'; }
      return {
        key,
        url: `${cfg.base}/${r.kind === 'AGENTE' ? 'agentes' : 'equipamentos'}/${Number(r.id)}`,
        title: r.headline,
        // Código à vista: dois recursos na mesma base ou endereço têm o mesmo título.
        sub: `${r.kind === 'EQUIPAMENTO' ? `${r.code} · ` : ''}${r.region || 'sem bairro'}`,
        iconHref: `${ICONS}#i-${safe(r.category, 'RADAR')}`,
        thumbClass: r.kind === 'AGENTE' ? 'row__thumb row__thumb--agent' : 'row__thumb',
        stClass: `st st--${safe(r.status, 'NAO_SE_APLICA')}`,
        glyph: r.glyph || GLYPH[r.status] || '•',
        statusLabel: r.status_label,
        note,
        noteClass,
        reasons: reasons.join(' · '),
        sig: JSON.stringify([r.status, r.attention, r.freshness]),
      };
    },
    panelVM(r) {
      // Acompanhamento (responsável, conferência, última providência) vem pronto do servidor, como na ficha completa.
      const alerts = snapshot.alerts.filter((a) => a.kind === r.kind && a.resource_id === r.id).map((a) => ({
        id: a.id,
        message: a.message,
        cls: `alert-box alert-box--${safe(a.severity, 'MEDIA')}`,
        meta: a.state === 'RECONHECIDO'
          ? `Ciência registrada por ${a.ack_by || 'operador'}${a.ack_at ? ` em ${dateTime(a.ack_at)}` : ''}; o alerta se resolve sozinho quando a causa acabar.`
          : `Aberto em ${dateTime(a.opened_at)}. Registrar ciência não resolve a causa.`,
        follow: a.kind !== 'AGENTE' ? [] : [
          { label: 'Responsável', value: a.assignee ? `${a.assignee}${a.assignee_active ? '' : ' · conta desativada: reatribua'}` : 'Sem responsável' },
          { label: 'Próxima conferência', value: a.next_check_at ? `${dateTime(a.next_check_at)}${a.check_overdue ? ' · vencida' : ''}` : 'Não definida' },
          { label: 'Última providência', value: a.last_follow_up ? `${a.last_follow_up.label} · ${a.last_follow_up.by}, ${dateTime(a.last_follow_up.at)}` : 'Nenhuma registrada' },
        ],
        canAck: cfg.canEdit && a.state === 'ABERTO',
        followUrl: cfg.canEdit && a.kind === 'AGENTE' && a.state === 'RECONHECIDO' ? `${cfg.base}/agentes/${Number(a.resource_id)}#providencia` : '',
      }));
      const next = r.next_action || null;
      const facts = [];
      if (r.kind === 'EQUIPAMENTO') facts.push({ label: 'Código', value: r.code });
      if (r.maintenance) {
        const m = r.maintenance;
        facts.push({ label: 'Manutenção', value: `${m.label}${m.state === 'EM_EXECUCAO' ? ' · em execução' : ''}${m.due_date ? ` · ${brDate(m.due_date)}` : ''} · ${m.reason}` });
      }
      facts.push({ label: r.kind === 'AGENTE' ? 'Identificação' : 'Marca e modelo', value: r.subtitle });
      facts.push({ label: 'Órgão', value: r.agency });
      if (r.place) facts.push({ label: r.kind === 'AGENTE' ? 'Base' : 'Endereço', value: r.kind === 'AGENTE' ? r.place : r.address || r.place });
      for (const h of r.highlights || []) facts.push({ label: h.label, value: h.value });
      const fresh = freshnessText(r);
      return {
        key: keyOf(r),
        title: r.headline,
        line: `${r.category_label} · ${r.region || 'sem bairro'}`,
        mini: `${cfg.mini}${safe(r.category, 'RADAR').toLowerCase()}.svg?v=${cfg.miniVersion}`,
        miniCaption: r.kind === 'AGENTE' ? 'Ilustração da função; pessoa fictícia.' : 'Ilustração da categoria; ponto ilustrativo.',
        stClass: `st st--${safe(r.status, 'NAO_SE_APLICA')}`,
        glyph: r.glyph || GLYPH[r.status] || '•',
        statusLabel: r.status_label,
        fresh: fresh || '',
        freshClass: `fresh fresh--${safe(r.freshness, 'X')}`,
        where: hasPosition(r) ? `${positionText(r)}${r.position.observed_at ? ` · ${dateTime(r.position.observed_at)}` : ''} · ponto ilustrativo` : 'Sem localização cadastrada: aparece só na lista.',
        attention: r.attention.join(' · '),
        next: { what: next ? next.what : '', detail: next ? next.detail : '', tone: next ? ({ urgent: 'next-action--urgent', calm: 'next-action--calm' }[next.tone] || '') : '' },
        alerts,
        facts,
        detailUrl: `${cfg.base}/${r.kind === 'AGENTE' ? 'agentes' : 'equipamentos'}/${Number(r.id)}`,
        canSimulate: cfg.canEdit && r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL',
        simulateId: r.kind === 'EQUIPAMENTO' ? r.id : null,
        located: hasPosition(r),
        gone: false,
      };
    },
    applySnapshot(snap, reason) {
      const before = new Map([...this.attentionRows, ...this.otherRows].map((r) => [r.key, r.sig]));
      const beforeKpi = { ...this.kpi };
      snapshot = snap;
      const k = snap.kpis.equipment;
      this.kpi = {
        operational: k.operational, in_service: k.in_service, needs_attention: snap.needs_attention, urgent: snap.maintenance.urgent.length,
        availability: pct(k.availability), availability_confirmed: pct(k.availability_confirmed), operational_unconfirmed: k.operational_unconfirmed, operational_confirmed: k.operational_confirmed,
        total: k.total, not_installed: k.not_installed, deactivated: k.deactivated, unavailable: k.unavailable, failure: k.failure,
        maintenance: k.maintenance, stolen: k.stolen, agents_active: snap.kpis.agents.active, agents_total: snap.kpis.agents.total,
        agents_stale: snap.kpis.agents.position_not_current, upcoming: snap.maintenance.upcoming.length,
      };
      this.count = snap.resources.length;
      this.countLabel = this.count === 1 ? 'recurso' : 'recursos';
      this.noLocation = snap.without_location;
      this.regions = snap.regions.map((g) => ({ ...g, availabilityText: pct(g.availability) }));
      // Ordem de leitura: categoria (radares primeiro, agentes por último) e depois o título.
      const byTitle = (a, b) => (CATEGORY_ORDER.indexOf(a.category) - CATEGORY_ORDER.indexOf(b.category)) || a.headline.localeCompare(b.headline, 'pt-BR');
      const attention = snap.resources.filter((r) => r.attention.length > 0).sort(byTitle).map((r) => this.rowVM(r));
      const others = snap.resources.filter((r) => r.attention.length === 0).sort(byTitle).map((r) => this.rowVM(r));
      // A escolha atual fica sempre visível na lista curta.
      const selIdx = others.findIndex((r) => r.key === this.selectedKey);
      if (selIdx >= OTHERS_LIMIT) this.showAllOthers = true;
      const visibleOthers = this.showAllOthers ? others : others.slice(0, OTHERS_LIMIT);
      this.othersHidden = others.length - visibleOthers.length;
      this.emptyList = snap.resources.length === 0;

      const listEl = this.$refs.list;
      const flipState = reason === 'filter' && listEl && !motion.reduced() ? Flip.getState(listEl.querySelectorAll('[data-flip-id]')) : null;
      this.attentionRows = attention;
      this.otherRows = visibleOthers;
      this.allOthers = others;

      this.$nextTick(() => {
        if (flipState) {
          Flip.from(flipState, {
            targets: listEl.querySelectorAll('[data-flip-id]'),
            duration: 0.34,
            ease: 'power2.inOut',
            onEnter: (els) => gsap.fromTo(els, { autoAlpha: 0, y: 10 }, { autoAlpha: 1, y: 0, duration: 0.26, stagger: 0.03, ease: 'power3.out', clearProps: 'transform,opacity,visibility' }),
          });
        }
        if (reason === 'poll' || reason === 'action') this.highlightChanges(before, beforeKpi);
        else if (reason === 'filter' || reason === 'manual') this.bumpCount();
      });
      this.syncMarkers();
      // Só a consulta periódica anuncia alertas novos: numa ação da própria pessoa, o aviso de sucesso já diz o que mudou.
      this.announceNewAlerts(snap, reason === 'poll');
      if (this.selectedKey) {
        const r = this.find(this.selectedKey);
        if (r) this.panel = this.panelVM(r);
        else if (reason !== 'first') this.panel = { ...this.panel, gone: true };
      }
      this.renderSync();
    },

    /* ------------------------------------------------------------ realce do que mudou */
    highlightChanges(before, beforeKpi) {
      if (motion.reduced()) return;
      const listEl = this.$refs.list;
      const changed = [...this.attentionRows, ...this.otherRows].filter((r) => before.has(r.key) && before.get(r.key) !== r.sig);
      for (const r of changed) {
        const row = listEl?.querySelector(`[data-key="${CSS.escape(r.key)}"]`);
        if (row) gsap.fromTo(row, { backgroundColor: 'rgba(45, 212, 191, 0.28)' }, { backgroundColor: 'rgba(45, 212, 191, 0)', duration: 1.4, ease: 'power2.out', clearProps: 'backgroundColor' });
        const pin = layers.get(r.key)?.marker.getElement()?.querySelector('.pin');
        if (pin) this.ring(pin, 1);
      }
      for (const [name, value] of Object.entries(this.kpi)) {
        if (beforeKpi[name] !== undefined && beforeKpi[name] !== value) {
          root.querySelectorAll(`[data-kpi="${name}"]`).forEach((n) => gsap.fromTo(n, { yPercent: -40, autoAlpha: 0, color: '#2152e8' }, { yPercent: 0, autoAlpha: 1, color: '', duration: 0.32, ease: 'power3.out', clearProps: 'transform,color,opacity,visibility' }));
        }
      }
    },
    bumpCount() {
      if (motion.reduced()) return;
      const n = root.querySelector('[data-count]');
      if (n) gsap.fromTo(n, { yPercent: -45, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.26, ease: 'power3.out', clearProps: 'transform,opacity,visibility' });
    },

    /* ------------------------------------------------------------ mapa */
    initMap() {
      const L = window.L;
      const banner = root.querySelector('[data-tiles-banner]');
      if (!L) {
        banner.querySelector('span').textContent = 'O mapa não pôde ser carregado. A lista traz os mesmos recursos.';
        banner.classList.add('is-visible');
        return;
      }
      const animate = !motion.reduced();
      map = L.map(root.querySelector('#map'), { minZoom: 11, maxZoom: 18, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], zoomAnimation: animate, fadeAnimation: animate, markerZoomAnimation: animate }).setView(MARILIA, 13);
      const tiles = L.tileLayer(cfg.tiles, { maxZoom: 19, attribution: cfg.attribution });
      let loaded = 0;
      let failed = 0;
      tiles.on('tileload', () => { loaded += 1; banner.classList.remove('is-visible'); });
      tiles.on('tileerror', () => { failed += 1; if (failed >= 3 && loaded === 0) banner.classList.add('is-visible'); });
      tiles.addTo(map);
      map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
      map.on('zoomend', () => this.syncMarkers());
      // O Leaflet precisa saber o tamanho real sempre que o layout muda (filtros ativos, troca de visão, celular).
      resizeObs = new ResizeObserver(() => map?.invalidateSize({ pan: false }));
      resizeObs.observe(root.querySelector('#map'));
      this.syncMarkers();
    },
    pinHtml(r) {
      const cls = ['pin', `pin--${safe(r.status, 'DESATIVADO')}`];
      if (r.kind === 'AGENTE' || r.position.kind === 'ULTIMA_POSICAO' || r.position.kind === 'BASE') cls.push('pin--agent');
      if (r.position.kind === 'PLANEJADO') cls.push('pin--planned');
      if (r.position.kind === 'BASE') cls.push('pin--base');
      if (r.position.freshness && r.position.freshness !== 'ATUAL') cls.push('pin--stale');
      const cat = safe(r.category, 'RADAR');
      const corner = r.position.kind === 'BASE' ? '<span class="pin__kind">B</span>' : (r.position.freshness && r.position.freshness !== 'ATUAL' ? '<span class="pin__kind">⌛</span>' : '');
      const alert = r.attention.length > 0 ? '<span class="pin__badge">!</span>' : '';
      // Só códigos validados (ENUM) e o endereço do sprite entram nesta marcação; nenhum texto livre do servidor.
      return `<span class="${cls.join(' ')}"><span class="pin__ring"></span><svg class="icon" aria-hidden="true"><use href="${ICONS}#i-${cat}"></use></svg>${alert}${corner}</span>`;
    },
    pinLabel(r) {
      return `${r.headline}${r.kind === 'EQUIPAMENTO' ? ` (${r.code})` : ''}. ${r.status_label}. ${positionText(r)}${r.attention.length ? `. Precisa de atenção: ${r.attention.join(', ')}` : ''}`;
    },
    asButton(marker, label, activate) {
      const node = marker.getElement();
      if (!node) return;
      node.setAttribute('role', 'button');
      node.setAttribute('aria-label', label);
      // O Leaflet trata só o antigo "keypress": Enter e Espaço ativam pelo keydown.
      node.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        e.stopPropagation();
        activate();
      });
    },
    groups() {
      const located = snapshot.resources.filter(hasPosition);
      const out = [];
      const radius = map.getZoom() < CLUSTER_UNTIL_ZOOM ? CLUSTER_PX : 6;
      for (const r of located) {
        const p = map.latLngToLayerPoint([Number(r.position.lat), Number(r.position.lng)]);
        if (keyOf(r) === this.selectedKey) { out.push({ p, members: [r] }); continue; }
        const g = out.find((x) => keyOf(x.members[0]) !== this.selectedKey && Math.abs(x.p.x - p.x) < radius && Math.abs(x.p.y - p.y) < radius);
        if (g) g.members.push(r); else out.push({ p, members: [r] });
      }
      return out;
    },
    syncMarkers() {
      if (!map) return;
      const L = window.L;
      const seen = new Set();
      for (const g of this.groups()) {
        if (g.members.length === 1) {
          const r = g.members[0];
          const k = keyOf(r);
          seen.add(k);
          const latlng = [Number(r.position.lat), Number(r.position.lng)];
          const html = this.pinHtml(r);
          const label = this.pinLabel(r);
          const existing = layers.get(k);
          if (existing) {
            existing.marker.setLatLng(latlng);
            if (existing.html !== html) {
              existing.marker.setIcon(L.divIcon({ className: 'pin-wrap', html, iconSize: [34, 34], iconAnchor: [17, 17] }));
              existing.html = html;
              this.asButton(existing.marker, label, existing.activate);
              existing.label = label;
            }
            if (existing.label !== label) { existing.marker.getElement()?.setAttribute('aria-label', label); existing.label = label; }
            this.paintSelection(k, existing.marker);
            continue;
          }
          const marker = L.marker(latlng, { icon: L.divIcon({ className: 'pin-wrap', html, iconSize: [34, 34], iconAnchor: [17, 17] }), keyboard: true, title: r.headline, riseOnHover: true });
          const activate = () => this.select(k, { origin: marker.getElement(), from: 'map' });
          marker.on('click', activate);
          marker.addTo(map);
          this.asButton(marker, label, activate);
          layers.set(k, { marker, html, label, activate });
          this.paintSelection(k, marker);
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
        const marker = L.marker([lat, lng], { icon: L.divIcon({ className: 'cluster-wrap', html, iconSize: [42, 42], iconAnchor: [21, 21] }), keyboard: true, title: `${g.members.length} recursos próximos` });
        const activate = () => this.openGroup(g.members, marker);
        marker.on('click', activate);
        marker.addTo(map);
        this.asButton(marker, `${g.members.length} recursos próximos${attention ? `, ${attention} precisam de atenção` : ''}. Ative para aproximar ou escolher.`, activate);
        layers.set(k, { marker, html, label: '' });
      }
      for (const [k, m] of layers) {
        if (!seen.has(k)) { gsap.killTweensOf(m.marker.getElement()?.querySelectorAll('.pin, .pin__ring') || []); m.marker.remove(); layers.delete(k); }
      }
    },
    paintSelection(k, marker) {
      const pin = marker.getElement()?.querySelector('.pin');
      if (!pin) return;
      const selected = k === this.selectedKey;
      pin.classList.toggle('is-selected', selected);
      marker.setZIndexOffset(selected ? 1000 : 0);
    },
    /** Destaque finito: o anel se expande e some (uma ou duas vezes) — nada pulsa sem parar. */
    ring(pin, times = 2) {
      const ring = pin.querySelector('.pin__ring');
      if (!ring || motion.reduced()) return;
      gsap.killTweensOf(ring);
      gsap.fromTo(ring, { scale: 0.7, autoAlpha: 0.9 }, { scale: 1.6, autoAlpha: 0, duration: 0.7, ease: 'power2.out', repeat: times - 1, clearProps: 'transform,opacity,visibility' });
    },
    popPin(k) {
      const pin = layers.get(k)?.marker.getElement()?.querySelector('.pin');
      if (!pin) return;
      if (!motion.reduced()) {
        gsap.killTweensOf(pin);
        gsap.fromTo(pin, { scale: 0.82 }, { scale: 1, duration: 0.36, ease: 'back.out(2.6)', clearProps: 'transform' });
      }
      this.ring(pin, 2);
    },
    openGroup(members, marker) {
      const L = window.L;
      const bounds = L.latLngBounds(members.map((r) => [Number(r.position.lat), Number(r.position.lng)]));
      const span = map.latLngToLayerPoint(bounds.getNorthEast()).distanceTo(map.latLngToLayerPoint(bounds.getSouthWest()));
      if (map.getZoom() < CLUSTER_UNTIL_ZOOM && span > 2) {
        map.fitBounds(bounds.pad(0.4), { maxZoom: CLUSTER_UNTIL_ZOOM, animate: !motion.reduced() });
        return;
      }
      const list = el('ul', { class: 'cluster-list', 'aria-label': 'Recursos neste ponto' });
      for (const r of members) {
        list.append(el('li', {}, el('button', { type: 'button', onclick: () => { map.closePopup(); this.select(keyOf(r), { origin: marker.getElement(), from: 'map' }); } },
          icon(`i-${safe(r.category, 'RADAR')}`), el('span', {}, el('strong', { text: r.headline }), el('br'),
            el('span', { class: `st st--${safe(r.status, 'NAO_SE_APLICA')}` }, el('span', { class: 'st__g', 'aria-hidden': 'true', text: r.glyph || GLYPH[r.status] || '•' }), r.status_label)))));
      }
      L.popup({ maxWidth: 320 }).setLatLng(marker.getLatLng()).setContent(list).openOn(map);
      list.querySelector('button')?.focus();
    },
    /** Centraliza o ponto na parte visível do mapa (descontando a ficha aberta ao lado ou embaixo). */
    panTo(r, fly = true) {
      if (!map || !hasPosition(r)) return;
      const zoom = Math.max(map.getZoom(), 15);
      const panel = this.$refs.panel;
      let dx = 0;
      let dy = 0;
      if (this.panelOpen && panelMode === 'side') dx = (panel.offsetWidth + 12) / 2;
      if (this.panelOpen && panelMode === 'sheet') dy = Math.min(panel.offsetHeight, map.getSize().y * 0.7) / 2;
      const center = map.unproject(map.project([Number(r.position.lat), Number(r.position.lng)], zoom).add([dx, dy]), zoom);
      if (motion.reduced()) map.setView(center, zoom, { animate: false });
      else if (fly) map.flyTo(center, zoom, { duration: 0.6, easeLinearity: 0.35 });
      else map.panTo(center, { animate: true, duration: 0.35 });
    },

    /* ------------------------------------------------------------ ficha (painel) */
    initPanel() {
      const panel = this.$refs.panel;
      panel.inert = true;
      mmPanel = gsap.matchMedia();
      mmPanel.add({ sheet: SHEET, side: '(min-width: 1100px)' }, (ctx) => {
        panelMode = ctx.conditions.sheet ? 'sheet' : 'side';
        const parts = panel.querySelectorAll('[data-stagger]');
        panelTl = gsap.timeline({ paused: true, defaults: { ease: 'power3.out' } });
        if (panelMode === 'sheet') panelTl.fromTo(panel, { autoAlpha: 0, yPercent: 16 }, { autoAlpha: 1, yPercent: 0, duration: 0.34, ease: 'expo.out' });
        else panelTl.fromTo(panel, { autoAlpha: 0, x: 32 }, { autoAlpha: 1, x: 0, duration: 0.3 });
        // Só opacidade nas partes: a visibilidade é do contêiner (o título precisa poder receber foco já no início).
        panelTl.fromTo(parts, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: 0.22, stagger: 0.035 }, '<0.08');
        panelTl.eventCallback('onReverseComplete', () => {
          if (!this.panelOpen) panel.inert = true;
        });
        if (this.panelOpen) panelTl.progress(1);
        return () => { panelTl?.kill(); panelTl = null; };
      });
    },
    playPanel(open) {
      const panel = this.$refs.panel;
      if (!panelTl) return;
      if (open) {
        panel.inert = false;
        if (motion.reduced()) panelTl.pause().progress(1);
        else panelTl.timeScale(1).play();
      } else {
        panel.inert = true;
        if (motion.reduced()) panelTl.pause().progress(0);
        else panelTl.timeScale(1.5).reverse();
      }
    },
    select(k, { origin = null, from = 'list', focus = true } = {}) {
      const r = this.find(k);
      if (!r) return;
      const switching = this.panelOpen && this.selectedKey !== k;
      const previous = this.selectedKey;
      this.selectedKey = k;
      this.panel = this.panelVM(r);
      returnFocus = origin || document.activeElement;
      if (previous && layers.get(previous)) this.paintSelection(previous, layers.get(previous).marker);
      // O ponto escolhido sai de qualquer grupo e ganha destaque próprio.
      this.syncMarkers();
      this.popPin(k);
      if (!this.panelOpen) {
        this.panelOpen = true;
        this.playPanel(true);
      } else if (switching && !motion.reduced()) {
        gsap.fromTo(this.$refs.panelBody, { autoAlpha: 0.35, x: 10 }, { autoAlpha: 1, x: 0, duration: 0.2, ease: 'power2.out', clearProps: 'transform,opacity,visibility' });
      }
      if (from === 'list' && this.view === 'mapa') this.panTo(r);
      if (from === 'list' || from === 'map') this.ensureRowVisible(k);
      this.writeUrl();
      // Elemento invisível não recebe foco: espera o primeiro quadro da entrada da ficha.
      if (focus) gsap.delayedCall(motion.reduced() ? 0 : 0.05, () => { if (this.panelOpen) this.$refs.panelTitle?.focus({ preventScroll: true }); });
    },
    close() {
      if (!this.selectedKey && !this.panelOpen) return;
      const previous = this.selectedKey;
      this.selectedKey = null;
      this.panelOpen = false;
      this.playPanel(false);
      if (previous && layers.get(previous)) this.paintSelection(previous, layers.get(previous).marker);
      this.syncMarkers();
      this.writeUrl();
      const back = returnFocus && document.contains(returnFocus) ? returnFocus : this.$refs.list?.querySelector(`[data-key="${CSS.escape(previous || '')}"]`);
      back?.focus?.({ preventScroll: true });
    },
    ensureRowVisible(k) {
      if (this.otherRows.some((r) => r.key === k) || this.attentionRows.some((r) => r.key === k)) {
        this.$nextTick(() => this.$refs.list?.querySelector(`[data-key="${CSS.escape(k)}"]`)?.scrollIntoView({ block: 'nearest', behavior: motion.reduced() ? 'auto' : 'smooth' }));
        return;
      }
      this.expandOthers();
      this.$nextTick(() => this.$refs.list?.querySelector(`[data-key="${CSS.escape(k)}"]`)?.scrollIntoView({ block: 'nearest' }));
    },
    rowClick(e, key) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      this.select(key, { origin: e.currentTarget, from: 'list' });
    },
    isSelected(key) {
      return this.selectedKey === key ? 'true' : null;
    },
    expandOthers() {
      const listEl = this.$refs.list;
      const state = listEl && !motion.reduced() ? Flip.getState(listEl.querySelectorAll('[data-flip-id]')) : null;
      this.showAllOthers = true;
      this.otherRows = this.allOthers || this.otherRows;
      this.othersHidden = 0;
      this.$nextTick(() => {
        if (state) Flip.from(state, { targets: listEl.querySelectorAll('[data-flip-id]'), duration: 0.3, ease: 'power2.inOut', onEnter: (els) => gsap.fromTo(els, { autoAlpha: 0, y: 8 }, { autoAlpha: 1, y: 0, duration: 0.24, stagger: 0.025, clearProps: 'transform,opacity,visibility' }) });
      });
    },
    showOnMap() {
      const r = this.selectedKey && this.find(this.selectedKey);
      this.setView('mapa');
      if (r) this.$nextTick(() => { map?.invalidateSize({ pan: false }); this.panTo(r, false); this.popPin(keyOf(r)); });
    },

    /* ------------------------------------------------------------ mapa × lista */
    setView(view) {
      if (view === this.view) return;
      this.view = view;
      this.writeUrl();
      this.$nextTick(() => {
        const area = view === 'mapa' ? this.$refs.mapbox : this.$refs.listpanel;
        if (!motion.reduced()) gsap.fromTo(area, { autoAlpha: 0.4 }, { autoAlpha: 1, duration: 0.22, ease: 'power2.out', clearProps: 'opacity,visibility' });
        if (view === 'mapa' && map) map.invalidateSize({ pan: false });
        if (view === 'lista' && this.selectedKey) this.$refs.list?.querySelector(`[data-key="${CSS.escape(this.selectedKey)}"]`)?.scrollIntoView({ block: 'nearest' });
      });
    },
    viewMap() { this.setView('mapa'); },
    viewList() { this.setView('lista'); },
    get mapPressed() { return this.view === 'mapa' ? 'true' : 'false'; },
    get listPressed() { return this.view === 'lista' ? 'true' : 'false'; },

    /* ------------------------------------------------------------ filtros */
    readChips() {
      const form = this.$refs.filters;
      const chips = [];
      form.querySelectorAll('input[type="checkbox"]:checked').forEach((i) => chips.push({ id: `${i.name}=${i.value}`, name: i.name, value: i.value, label: i.dataset.label || i.value }));
      form.querySelectorAll('select').forEach((s) => { if (s.value) chips.push({ id: `${s.name}=${s.value}`, name: s.name, value: s.value, label: `${s.dataset.label}: ${s.selectedOptions[0]?.textContent.trim()}` }); });
      const q = form.querySelector('input[name="q"]').value.trim();
      if (q) chips.unshift({ id: `q=${q}`, name: 'q', value: q, label: `Busca: “${q}”` });
      this.chips = chips;
      this.filterNote = chips.length ? ' no filtro' : '';
      root.querySelectorAll('[data-menu-count]').forEach((n) => {
        const names = n.dataset.menuCount.split(',');
        const total = chips.filter((c) => names.includes(c.name)).length;
        n.textContent = String(total);
        n.hidden = total === 0;
      });
    },
    queryString() {
      const q = new URLSearchParams();
      for (const [k, v] of new FormData(this.$refs.filters)) if (v !== '') q.append(k, String(v));
      return q;
    },
    writeUrl() {
      const q = this.queryString();
      if (this.view === 'lista') q.set('visao', 'lista');
      if (this.selectedKey) q.set('selecionar', this.selectedKey);
      const qs = q.toString();
      window.history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
    },
    onFilterChange(e) {
      if (e?.target?.name === 'q') return;
      this.applyFilters();
    },
    onSearchInput() {
      window.clearTimeout(this.typing);
      this.typing = window.setTimeout(() => this.applyFilters(), 320);
    },
    onSubmit(e) {
      e.preventDefault();
      window.clearTimeout(this.typing);
      this.applyFilters();
    },
    applyFilters() {
      this.readChips();
      this.writeUrl();
      this.load('filter');
    },
    removeChip(id) {
      const chip = this.chips.find((c) => c.id === id);
      if (!chip) return;
      const form = this.$refs.filters;
      if (chip.name === 'q') form.querySelector('input[name="q"]').value = '';
      form.querySelectorAll(`input[type="checkbox"][name="${CSS.escape(chip.name)}"]`).forEach((i) => { if (i.value === chip.value) i.checked = false; });
      form.querySelectorAll(`select[name="${CSS.escape(chip.name)}"]`).forEach((s) => { s.value = ''; });
      this.applyFilters();
      announce(`Filtro removido: ${chip.label}.`);
    },
    clearFilters() {
      const form = this.$refs.filters;
      form.reset();
      form.querySelectorAll('input[type="checkbox"]').forEach((c) => { c.checked = false; });
      form.querySelectorAll('select').forEach((s) => { s.value = ''; });
      form.querySelector('input[name="q"]').value = '';
      this.applyFilters();
      announce('Filtros limpos.');
    },
    showAttention() {
      const box = this.$refs.filters.querySelector('input[name="atencao"]');
      box.checked = true;
      this.applyFilters();
      announce('Mostrando só o que precisa de atenção.');
    },
    get hasChips() { return this.chips.length > 0; },

    /* ------------------------------------------------------------ sincronização (um único dono do polling) */
    schedule() {
      window.clearTimeout(timer);
      if (this.paused || document.hidden) return;
      const delay = failures ? Math.min(60000, cfg.poll * 2 ** failures) : cfg.poll;
      nextAt = Date.now() + delay;
      timer = window.setTimeout(() => this.load('poll'), delay);
    },
    async load(reason) {
      // Polling não atropela pedido em andamento; filtro e ações cancelam o anterior (só o último vale).
      if (inflight) {
        if (reason === 'poll') return;
        inflight.controller.abort('substituida');
        window.__pfCentral && (window.__pfCentral.aborted += 1);
      }
      window.clearTimeout(timer);
      const seq = ++reqSeq;
      const controller = new AbortController();
      const timeout = window.setTimeout(() => controller.abort('tempo'), 10000);
      inflight = { controller, seq, reason };
      if (reason === 'filter') this.loading = true;
      if (reason === 'manual') this.refreshing = true;
      if (window.__pfCentral) window.__pfCentral.polls += 1;
      try {
        const res = await fetch(`${cfg.api}${this.queryString().toString() ? `?${this.queryString()}` : ''}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
        // Sessão expirada ou conta desativada: recarrega para o servidor levar ao login (o sistema não tem leitura pública).
        if (res.status === 401) { window.location.reload(); return; }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const snap = await res.json();
        if (seq !== reqSeq) return;
        const recovered = failures > 0;
        failures = 0;
        lastOkAt = Date.now();
        this.applySnapshot(snap, reason);
        if (window.__pfCentral) window.__pfCentral.applied += 1;
        if (unconfirmed) {
          // Retrato novo depois de uma ação sem resposta: a ficha e a lista já mostram se ela foi gravada.
          toast({ type: 'warning', title: `${unconfirmed.what}: confira o resultado`, key: unconfirmed.key,
            body: `O servidor não confirmou a ação. Dados atualizados às ${clock(snap.generated_at)}: veja na ficha se ela aparece antes de tentar novamente.` });
          unconfirmed = null;
        }
        if (recovered) {
          dismissToast('offline');
          toast({ type: 'success', title: 'Conexão restabelecida', body: `Dados atualizados às ${clock(snap.generated_at)}.`, key: 'online' });
        }
      } catch (err) {
        if (seq !== reqSeq || controller.signal.reason === 'substituida' || controller.signal.reason === 'saida') return;
        failures += 1;
        this.renderSync();
        this.reportOffline();
      } finally {
        window.clearTimeout(timeout);
        if (seq === reqSeq) {
          inflight = null;
          this.loading = false;
          this.refreshing = false;
          this.schedule();
        }
      }
    },
    reportOffline() {
      toast({
        type: 'error',
        key: 'offline',
        title: 'Sem conexão com o servidor',
        body: `Mostrando os dados das ${clock(snapshot.generated_at)}. Nova tentativa automática em ${Math.round(Math.min(60000, cfg.poll * 2 ** failures) / 1000)} s; os filtros e a escolha continuam.`,
        actions: [{ label: 'Tentar agora', onClick: (btn) => { setBusy(btn, true, 'Tentando…'); this.load('manual').finally(() => setBusy(btn, false)); } }],
      });
    },
    refreshNow() {
      this.load('manual');
    },
    togglePause() {
      this.paused = !this.paused;
      if (this.paused) { window.clearTimeout(timer); this.renderSync(); } else this.load('poll');
    },
    get pauseLabel() { return this.paused ? 'Retomar' : 'Pausar'; },
    get pausePressed() { return this.paused ? 'true' : 'false'; },
    renderSync() {
      const s = snapshot.sync;
      let state = 'live';
      let text = `Atualizado às ${clock(snapshot.generated_at)}`;
      if (failures > 0) {
        state = 'offline';
        text = `Sem conexão · nova tentativa em ${Math.max(0, Math.round((nextAt - Date.now()) / 1000))} s`;
      } else if (this.paused) {
        state = 'paused';
        text = `Pausado · dados das ${clock(snapshot.generated_at)}`;
      } else if (Date.now() - lastOkAt > cfg.poll * 3) {
        state = 'stale';
      }
      if (state !== this.syncState && state === 'live' && !motion.reduced()) this.$nextTick(() => this.ping());
      this.syncState = state;
      this.syncText = text;
      this.simText = s.simulator_running ? 'simulador ativo' : 'simulador parado';
    },
    ping() {
      const p = root.querySelector('.sync__ping');
      if (p && !motion.reduced()) gsap.fromTo(p, { scale: 0.6, autoAlpha: 0.9 }, { scale: 2.2, autoAlpha: 0, duration: 0.8, ease: 'power2.out' });
    },
    /** Só consultas periódicas/ações anunciam: mudar o filtro revela alertas antigos, que não são "novos". */
    announceNewAlerts(snap, mayAnnounce) {
      knownAlerts ??= new Set();
      const fresh = mayAnnounce ? snap.alerts.filter((a) => !knownAlerts.has(a.id)) : [];
      snap.alerts.forEach((a) => knownAlerts.add(a.id));
      if (!fresh.length) return;
      const first = fresh[0];
      const target = `${first.kind}:${first.resource_id}`;
      // Um aviso por atualização, com a mesma chave: novos alertas atualizam o texto em vez de empilhar.
      toast({
        type: 'alert',
        key: 'new-alerts',
        title: fresh.length === 1 ? 'Novo alerta' : `${fresh.length} novos alertas`,
        body: first.message,
        actions: this.find(target) ? [{ label: 'Ver no mapa', onClick: () => { dismissToast('new-alerts'); this.select(target, { from: 'list' }); } }] : [],
      });
    },

    /* ------------------------------------------------------------ ações com o servidor */
    async post(url, btn, busyLabel) {
      setBusy(btn, true, busyLabel);
      try {
        const res = await fetch(url, {
          method: 'POST', credentials: 'same-origin',
          headers: { 'X-CSRF-Token': cfg.csrf, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ note: '' }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.status === 401 || res.status === 403) return { ok: false, message: data.error || 'Sua sessão expirou. Entre novamente para continuar.' };
        return { ok: res.ok && data.ok !== false, message: data.message || data.error || (res.ok ? '' : 'O servidor recusou a ação.') };
      } catch {
        // Sem resposta não dá para saber se o servidor gravou (a resposta pode ter se perdido depois do registro).
        return { ok: false, unknown: true, message: 'Não foi possível confirmar o resultado. Atualize os dados antes de tentar novamente.' };
      } finally {
        setBusy(btn, false);
      }
    },
    async acknowledge(e, id) {
      const btn = e.currentTarget;
      if (btn.getAttribute('aria-busy') === 'true') return;
      const result = await this.post(`${cfg.base}/alertas/${Number(id)}/reconhecer`, btn, 'Registrando…');
      if (result.ok) {
        toast({ type: 'success', title: 'Ciência registrada', body: result.message, key: `ack-${id}` });
        await this.load('action');
      } else if (result.unknown) {
        await this.unconfirmed(`ack-${id}`, 'Ciência', result.message);
      } else {
        toast({ type: 'error', title: 'Ciência não registrada', body: result.message, key: `ack-${id}` });
      }
    },
    /** Resultado desconhecido: avisa sem afirmar que nada mudou e reconfere o estado (de novo quando a conexão voltar). */
    async unconfirmed(key, what, message) {
      toast({ type: 'warning', title: `${what}: resultado não confirmado`, body: message, key, timeout: false,
        actions: [{ label: 'Atualizar agora', onClick: (btn) => { setBusy(btn, true, 'Atualizando…'); this.load('manual').finally(() => setBusy(btn, false)); } }] });
      unconfirmed = { key, what };
      await this.load('action');
    },
    async simulateFailure(e) {
      const btn = e.currentTarget;
      const id = this.panel?.simulateId;
      if (!id || btn.getAttribute('aria-busy') === 'true') return;
      const result = await this.post(`${cfg.base}/equipamentos/${Number(id)}/simular-falha`, btn, 'Simulando…');
      if (result.ok) {
        toast({ type: 'success', title: 'Falha simulada registrada', body: result.message, key: `sim-${id}` });
        await this.load('action');
      } else if (result.unknown) {
        await this.unconfirmed(`sim-${id}`, 'Falha simulada', result.message);
      } else {
        toast({ type: 'error', title: 'Falha simulada não registrada', body: result.message, key: `sim-${id}` });
      }
    },

    /* ------------------------------------------------------------ preferências */
    onMotionChange() {
      if (!map) return;
      // Zoom/fade do Leaflet são lidos na criação: o modo reduzido também vale para os próximos movimentos (animate:false).
      map.options.zoomAnimation = !motion.reduced();
      map.options.fadeAnimation = !motion.reduced();
      if (panelTl && motion.reduced()) panelTl.progress(this.panelOpen ? 1 : 0).pause();
    },
  };
}
