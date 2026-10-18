/*
 * Comportamentos comuns: preferência de movimento, campos por tipo de equipamento, seletor de coordenadas
 * e confirmação de ações irreversíveis. Melhoria progressiva: sem JavaScript, os formulários continuam válidos.
 */
(() => {
  'use strict';

  /* Preferência de movimento: segue o sistema; o botão permite forçar "reduzido" ou "completo". */
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
        toggle.title = isReduced() ? 'Animações reduzidas (clique para ativar)' : 'Animações ativas (clique para reduzir)';
        toggle.querySelector('span').textContent = isReduced() ? 'Movimento reduzido' : 'Movimento';
      };
      toggle.addEventListener('click', () => {
        html.dataset.motion = isReduced() ? 'full' : 'reduced';
        write(html.dataset.motion);
        sync();
      });
      sync();
    }

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
        const kind = document.querySelector('[name="location_kind"]');
        if (kind && mobile) kind.value = 'BASE';
        if (kind && !mobile && kind.value === 'BASE') kind.value = 'PLANEJADO';
      };
      typeSelect.addEventListener('change', update);
      update();
    }

    /* Seletor de coordenadas: clicar no mapa preenche latitude/longitude (digitação continua possível). */
    document.querySelectorAll('[data-picker]').forEach((box) => {
      if (!window.L) { box.hidden = true; return; }
      const lat = document.getElementById(box.dataset.lat);
      const lng = document.getElementById(box.dataset.lng);
      const start = [Number(lat.value) || -22.2185, Number(lng.value) || -49.944];
      const map = window.L.map(box, { minZoom: 11, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]] }).setView(start, lat.value ? 16 : 13);
      window.L.tileLayer(box.dataset.tiles, { maxZoom: 19, attribution: box.dataset.attribution }).addTo(map);
      map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
      const pin = window.L.circleMarker(start, { radius: 9, color: '#ffc94d', weight: 3, fillColor: '#ff7a1a', fillOpacity: 0.85 });
      if (lat.value && lng.value) pin.addTo(map);
      map.on('click', (e) => {
        lat.value = e.latlng.lat.toFixed(6);
        lng.value = e.latlng.lng.toFixed(6);
        pin.setLatLng(e.latlng).addTo(map);
      });
      const fromInputs = () => {
        const a = Number(lat.value.replace(',', '.'));
        const b = Number(lng.value.replace(',', '.'));
        if (Number.isFinite(a) && Number.isFinite(b) && lat.value && lng.value) { pin.setLatLng([a, b]).addTo(map); map.panTo([a, b], { animate: !isReduced() }); }
      };
      lat.addEventListener('change', fromInputs);
      lng.addEventListener('change', fromInputs);
    });
  });
})();
