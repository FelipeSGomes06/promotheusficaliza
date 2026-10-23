/*
 * Seletor de local nos formulários: tocar no mapa marca o ponto (ou o muda) e preenche as coordenadas (editáveis ou
 * não, conforme o formulário). O Leaflet é o dono do contêiner do mapa; movimento do mapa segue a preferência de movimento.
 * Textos do estado por formulário: data-empty (nada marcado) e data-marked ("{ponto}" vira "lat, lng").
 * data-mark-center: pelo teclado, Enter ou espaço no mapa marca o centro (quem não usa mouse também marca o ponto).
 * data-needs-map="<id do mapa>": elemento que só faz sentido com o mapa; vem oculto no HTML e aparece aqui.
 * data-without-map="<id do mapa>": explicação para quando não há mapa; some aqui.
 */
import { motion } from './motion.js';

export function initPicker() {
  document.querySelectorAll('[data-picker]').forEach((box) => {
    const L = window.L;
    if (!L) { box.hidden = true; return; }
    // Mapa, instrução e estado que vêm ocultos no HTML (sem JavaScript não há mapa) aparecem agora.
    box.hidden = false;
    document.querySelectorAll(`[data-needs-map="${box.id}"]`).forEach((n) => { n.hidden = false; });
    document.querySelectorAll(`[data-without-map="${box.id}"]`).forEach((n) => { n.hidden = true; });
    const lat = document.getElementById(box.dataset.lat);
    const lng = document.getElementById(box.dataset.lng);
    const status = document.getElementById(box.dataset.status);
    const start = [Number(lat.value) || -22.2185, Number(lng.value) || -49.944];
    const map = L.map(box, { minZoom: 11, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], zoomAnimation: !motion.reduced(), fadeAnimation: !motion.reduced() }).setView(start, lat.value ? 16 : 13);
    L.tileLayer(box.dataset.tiles, { maxZoom: 19, attribution: box.dataset.attribution }).addTo(map);
    map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
    const pin = L.circleMarker(start, { radius: 10, color: '#0f1a2b', weight: 3, fillColor: '#2152e8', fillOpacity: 0.95 });
    const marked = box.dataset.marked || 'Local marcado: {ponto}.';
    const say = () => {
      // Erro do servidor sobre o ponto ("Marque a base…") deixa de valer quando há ponto.
      if ('markCenter' in box.dataset && lat.value && lng.value) document.getElementById(`err-${lat.name}`)?.remove();
      if (status) status.textContent = lat.value && lng.value ? marked.replace('{ponto}', `${lat.value}, ${lng.value}`) : (box.dataset.empty || 'Nenhum local marcado ainda.');
    };
    if (lat.value && lng.value) pin.addTo(map);
    const mark = (latlng) => {
      lat.value = latlng.lat.toFixed(6);
      lng.value = latlng.lng.toFixed(6);
      pin.setLatLng(latlng).addTo(map);
      say();
    };
    map.on('click', (e) => mark(e.latlng));
    if ('markCenter' in box.dataset) {
      map.on('keydown', (e) => {
        if (e.originalEvent.key !== 'Enter' && e.originalEvent.key !== ' ') return;
        e.originalEvent.preventDefault();
        mark(map.getCenter());
      });
    }
    const fromInputs = () => {
      const a = Number(lat.value.replace(',', '.'));
      const b = Number(lng.value.replace(',', '.'));
      if (Number.isFinite(a) && Number.isFinite(b) && lat.value && lng.value) {
        pin.setLatLng([a, b]).addTo(map);
        map.panTo([a, b], { animate: !motion.reduced() });
      }
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
}
