/*
 * Planejar reforço: mapa da grade de quadrados de 2 km (linhas finas) com só os prioritários numerados, a partir do
 * JSON que o servidor deixa na página (#plan-data). Sem JavaScript, a lista ao lado tem a mesma informação.
 * Texto do servidor entra por textContent, nunca por innerHTML.
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
  // Quadrados prioritários: número no centro; tocar abre o passo 2 para aquele quadrado (a lista faz o mesmo).
  g.cells.forEach((cell) => {
    const shape = L.rectangle([[cell.south, cell.west], [cell.north, cell.east]], { className: `cell-shape${cell.selected ? ' cell-shape--selected' : ''}` })
      .bindTooltip(text(String(cell.rank)), { permanent: true, direction: 'center', className: `cell-label${cell.selected ? ' is-selected' : ''}` })
      .addTo(map);
    shape.on('click', () => window.location.assign(cell.href));
  });
  map.fitBounds([[g.south, g.west], [g.north, g.east]], { padding: [4, 4], animate: false });
}

export function init() {
  const source = document.getElementById('plan-data');
  if (!source || !window.L) return;
  const data = JSON.parse(source.textContent);
  const cells = document.getElementById('cell-map');
  if (cells && data.grid && data.grid.cells.length) cellsMap(cells, data);
}
