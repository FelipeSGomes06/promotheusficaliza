/*
 * Seletor de local nos formulários: tocar no mapa marca o ponto e preenche as coordenadas (que continuam editáveis).
 * O Leaflet é o dono do contêiner do mapa; movimento do mapa segue a preferência de movimento.
 */
import { motion } from './motion.js';

export function initPicker() {
  document.querySelectorAll('[data-picker]').forEach((box) => {
    const L = window.L;
    if (!L) { box.hidden = true; return; }
    const lat = document.getElementById(box.dataset.lat);
    const lng = document.getElementById(box.dataset.lng);
    const status = document.getElementById(box.dataset.status);
    const start = [Number(lat.value) || -22.2185, Number(lng.value) || -49.944];
    const map = L.map(box, { minZoom: 11, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], zoomAnimation: !motion.reduced(), fadeAnimation: !motion.reduced() }).setView(start, lat.value ? 16 : 13);
    L.tileLayer(box.dataset.tiles, { maxZoom: 19, attribution: box.dataset.attribution }).addTo(map);
    map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
    const pin = L.circleMarker(start, { radius: 10, color: '#0f1a2b', weight: 3, fillColor: '#2152e8', fillOpacity: 0.95 });
    const say = () => {
      if (status) status.textContent = lat.value && lng.value ? `Local marcado: ${lat.value}, ${lng.value}.` : 'Nenhum local marcado ainda.';
    };
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
