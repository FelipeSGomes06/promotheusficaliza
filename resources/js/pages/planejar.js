/*
 * Planejar reforço: dois mapas a partir do JSON que o servidor deixa na página (#plan-data).
 * - #plan-map: os quatro setores e os agentes livres;
 * - #cell-map: a grade inteira de quadrados de 2 km (linhas finas) e só os prioritários, numerados.
 * Sem JavaScript, as listas ao lado oferecem a mesma informação. Formas e cores vêm de classes CSS (prioridade
 * relativa, nunca vermelho de alerta); texto do servidor entra por textContent, nunca por innerHTML.
 */
import { motion } from '../lib/motion.js';

const text = (value) => {
  const span = document.createElement('span');
  span.textContent = value;
  return span;
};

function baseMap(box, data) {
  const L = window.L;
  const animate = !motion.reduced();
  // zoomSnap fracionário: o retângulo de estudo (~16 km) cabe inteiro sem cair no zoom 11, que o deixa pequeno.
  const map = L.map(box, { minZoom: 10, maxZoom: 17, zoomSnap: 0.25, maxBounds: [[-22.42, -50.22], [-22.0, -49.68]], scrollWheelZoom: false, zoomAnimation: animate, fadeAnimation: animate });
  L.tileLayer(data.tiles, { maxZoom: 19, attribution: data.attribution }).addTo(map);
  map.attributionControl.setPrefix('<a href="https://leafletjs.com" rel="noopener">Leaflet</a>');
  return map;
}

function sectorsMap(box, data) {
  const L = window.L;
  const map = baseMap(box, data);
  const corners = [];
  data.sectors.forEach((s) => {
    const bounds = [[s.south, s.west], [s.north, s.east]];
    corners.push(...bounds);
    const selected = s.id === data.selected;
    const shape = L.rectangle(bounds, { className: `sector-shape sector-shape--${s.rank}${selected ? ' is-selected' : ''}` }).addTo(map);
    // Rótulo curto (posição, código, estimativa) para não encavalar; o nome completo está na lista e no título.
    shape.bindTooltip(text(`${s.rank}º · ${s.id} · ${s.value}`), { permanent: true, direction: 'center', className: `sector-label${selected ? ' is-selected' : ''}` });
    shape.on('click', () => window.location.assign(s.href));
  });
  data.agents.forEach((a) => {
    L.circleMarker([a.lat, a.lng], { radius: 8, className: `agent-dot agent-dot--${a.tier}` })
      .bindTooltip(text(`${a.call_sign} (fictício) · ${a.point}`))
      .addTo(map);
  });
  map.fitBounds(corners, { padding: [8, 8], animate: false });
}

function cellsMap(box, data) {
  const L = window.L;
  const g = data.grid;
  const map = baseMap(box, data);
  const dLat = (g.north - g.south) / g.size;
  const dLng = (g.east - g.west) / g.size;
  for (let r = 0; r < g.size; r += 1) {
    for (let c = 0; c < g.size; c += 1) {
      L.rectangle([[g.south + r * dLat, g.west + c * dLng], [g.south + (r + 1) * dLat, g.west + (c + 1) * dLng]], { className: 'grid-line', interactive: false }).addTo(map);
    }
  }
  g.cells.forEach((cell) => {
    L.rectangle([[cell.south, cell.west], [cell.north, cell.east]], { className: 'cell-shape', interactive: false })
      .bindTooltip(text(String(cell.rank)), { permanent: true, direction: 'center', className: 'cell-label' })
      .addTo(map);
  });
  map.fitBounds([[g.south, g.west], [g.north, g.east]], { padding: [4, 4], animate: false });
}

export function init() {
  const source = document.getElementById('plan-data');
  if (!source || !window.L) return;
  const data = JSON.parse(source.textContent);
  const sectors = document.getElementById('plan-map');
  const cells = document.getElementById('cell-map');
  if (sectors) sectorsMap(sectors, data);
  if (cells && data.grid && data.grid.cells.length) cellsMap(cells, data);
}
