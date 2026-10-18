/*
 * Comportamentos comuns: menu no celular, avisos que abrem por cima (demonstração, mais filtros), preferência de
 * movimento, campos por tipo de equipamento, seletor de local no mapa e confirmação de ações irreversíveis.
 * Melhoria progressiva: sem JavaScript, menus abrem por <details>/links e os formulários continuam válidos.
 */
(() => {
  'use strict';

  /* Preferência de movimento: segue o sistema; o botão do rodapé permite forçar "reduzido" ou "completo". */
  const html = document.documentElement;
  const read = () => { try { return localStorage.getItem('pf-motion'); } catch { return null; } };
  const write = (v) => { try { localStorage.setItem('pf-motion', v); } catch { /* armazenamento indisponível: vale só nesta página */ } };
  const systemReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saved = read();
  if (saved === 'reduced' || saved === 'full') html.dataset.motion = saved;
  const isReduced = () => html.dataset.motion === 'reduced' || (html.dataset.motion !== 'full' && systemReduced);

  document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-motion-toggle]');
    if (toggle) {
      const sync = () => {
        toggle.setAttribute('aria-pressed', String(isReduced()));
        toggle.querySelector('span').textContent = isReduced() ? 'Animações reduzidas (ativar)' : 'Reduzir animações';
      };
      toggle.addEventListener('click', () => {
        html.dataset.motion = isReduced() ? 'full' : 'reduced';
        write(html.dataset.motion);
        sync();
      });
      sync();
    }

    /* Menu principal no celular: botão com estado aberto/fechado; Esc fecha e devolve o foco ao botão. */
    const menuBtn = document.querySelector('[data-menu-toggle]');
    const nav = document.querySelector('[data-nav]');
    if (menuBtn && nav) {
      const setOpen = (open, returnFocus = false) => {
        nav.classList.toggle('is-open', open);
        menuBtn.setAttribute('aria-expanded', String(open));
        menuBtn.querySelector('span').textContent = open ? 'Fechar menu' : 'Menu';
        if (open) nav.querySelector('a')?.focus();
        else if (returnFocus) menuBtn.focus();
      };
      menuBtn.addEventListener('click', () => setOpen(menuBtn.getAttribute('aria-expanded') !== 'true'));
      nav.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nav.classList.contains('is-open')) { e.preventDefault(); setOpen(false, true); } });
      window.matchMedia('(min-width: 1241px)').addEventListener('change', (e) => { if (e.matches) setOpen(false); });
    }

    /* Avisos que abrem por cima: fecham com Esc (foco volta ao botão) ou com clique fora. */
    const popovers = Array.from(document.querySelectorAll('details.demo-badge, details.legend, details.more-filters:not(.more-filters--inline)'));
    document.addEventListener('click', (e) => {
      for (const d of popovers) if (d.open && !d.contains(e.target)) d.open = false;
    });
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      for (const d of popovers) {
        if (d.open) { d.open = false; d.querySelector('summary')?.focus(); }
      }
    });

    /* Link para uma seção recolhida (ex.: "Mudar situação" → "Mais ações") abre a seção de destino. */
    const openTarget = (id) => { const target = id && document.getElementById(id); if (target && target.tagName === 'DETAILS') target.open = true; };
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[href^="#"]');
      if (link) openTarget(link.getAttribute('href').slice(1));
    });
    openTarget(window.location.hash.slice(1));

    /* Resumo de erros: recebe o foco para que leitores de tela anunciem o que corrigir. */
    document.querySelector('[data-error-summary]')?.focus();

    /* Confirmação para ações irreversíveis (arquivar, cancelar ordem). */
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
      form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
      });
    });

    /* Formulário de equipamento: mostra só as características do tipo escolhido e desabilita as demais (não são enviadas). */
    const typeSelect = document.querySelector('[data-type-select]');
    if (typeSelect) {
      const update = () => {
        document.querySelectorAll('[data-spec-type]').forEach((fs) => {
          const on = fs.dataset.specType === typeSelect.value;
          fs.hidden = !on;
          fs.disabled = !on;
        });
        const mobile = typeSelect.selectedOptions[0]?.dataset.mobile === '1';
        document.querySelectorAll('[data-location-kind] option').forEach((o) => {
          o.disabled = mobile ? o.value !== 'BASE' : o.value === 'BASE';
        });
        const kind = document.querySelector('[data-location-kind]');
        if (kind && mobile) kind.value = 'BASE';
        if (kind && !mobile && kind.value === 'BASE') kind.value = 'PLANEJADO';
        document.querySelectorAll('[data-mobile-note]').forEach((n) => { n.hidden = !mobile; });
      };
      typeSelect.addEventListener('change', update);
      update();
    }

    /* Seletor de local: tocar no mapa marca o ponto e preenche as coordenadas (que continuam editáveis). */
    document.querySelectorAll('[data-picker]').forEach((box) => {
      if (!window.L) { box.hidden = true; return; }
      const lat = document.getElementById(box.dataset.lat);
      const lng = document.getElementById(box.dataset.lng);
      const status = document.getElementById(box.dataset.status);
      const start = [Number(lat.value) || -22.2185, Number(lng.value) || -49.944];
      const map = window.L.map(box, { minZoom: 11, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]] }).setView(start, lat.value ? 16 : 13);
      window.L.tileLayer(box.dataset.tiles, { maxZoom: 19, attribution: box.dataset.attribution }).addTo(map);
      map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
      const pin = window.L.circleMarker(start, { radius: 10, color: '#18212c', weight: 3, fillColor: '#1b5eab', fillOpacity: 0.9 });
      const say = () => { if (status) status.textContent = lat.value && lng.value ? `Local marcado: ${lat.value}, ${lng.value}.` : 'Nenhum local marcado ainda.'; };
      if (lat.value && lng.value) pin.addTo(map);
      map.on('click', (e) => {
        lat.value = e.latlng.lat.toFixed(6);
        lng.value = e.latlng.lng.toFixed(6);
        pin.setLatLng(e.latlng).addTo(map);
        say();
      });
      const fromInputs = () => {
        const a = Number(lat.value.replace(',', '.'));
        const b = Number(lng.value.replace(',', '.'));
        if (Number.isFinite(a) && Number.isFinite(b) && lat.value && lng.value) { pin.setLatLng([a, b]).addTo(map); map.panTo([a, b], { animate: !isReduced() }); }
        say();
      };
      lat.addEventListener('change', fromInputs);
      lng.addEventListener('change', fromInputs);
      document.querySelectorAll(`[data-clear-point="${box.id}"]`).forEach((b) => b.addEventListener('click', () => {
        lat.value = '';
        lng.value = '';
        pin.remove();
        say();
      }));
      say();
    });
  });
})();
