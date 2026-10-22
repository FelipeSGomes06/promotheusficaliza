/*
 * Planejar reforço: mapa de calor da grade de quadrados de 2 km (cor = sinistros esperados na semana) com os
 * prioritários numerados por cima, a partir do JSON que o servidor deixa na página (#plan-data). Sem JavaScript, a lista ao lado tem a mesma informação.
 * Texto do servidor entra por textContent, nunca por innerHTML.
 */
import { motion } from '../lib/motion.js';
import { reveal } from '../lib/reveal.js';

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

/*
 * Faixa de cor 0–4 do quadrado. A previsão é muito concentrada (poucos quadrados no centro somam boa parte dos
 * registros); em escala linear quase tudo cairia na faixa mais clara. A raiz da posição entre o mínimo e o máximo
 * da semana espalha os valores baixos e mantém o maior sempre na faixa vermelha e o menor na mais clara. Quantis
 * dariam faixas do mesmo tamanho, mas pintariam do mesmo vermelho quadrados com valores muito diferentes.
 */
const band = (value, min, max) => (max > min ? Math.min(4, Math.floor(Math.sqrt((value - min) / (max - min)) * 5)) : 0);

function cellsMap(box, data) {
  const L = window.L;
  const g = data.grid;
  const map = baseMap(box, data);
  const values = g.all.map((cell) => cell.expected);
  const [min, max] = [Math.min(...values), Math.max(...values)];
  const rect = (cell) => [[cell.south, cell.west], [cell.north, cell.east]];
  const priority = new Set(g.cells.map((cell) => cell.rank));
  // Demais quadrados: só cor e, ao tocar ou passar o mouse, o valor e o bairro (no mesmo texto da lista).
  g.all.filter((cell) => !priority.has(cell.rank)).forEach((cell) => {
    L.rectangle(rect(cell), { className: `heat heat--${band(cell.expected, min, max)}` })
      .bindTooltip(text(cell.label), { direction: 'top', className: 'heat-tip' })
      .addTo(map);
  });
  // Quadrados prioritários: número no centro; tocar abre o passo 2 para aquele quadrado (a lista faz o mesmo).
  g.cells.forEach((cell) => {
    const shape = L.rectangle(rect(cell), { className: `heat heat--${band(cell.expected, min, max)} cell-shape${cell.selected ? ' cell-shape--selected' : ''}` })
      .bindTooltip(text(String(cell.rank)), { permanent: true, direction: 'center', className: `cell-label${cell.selected ? ' is-selected' : ''}` })
      .addTo(map);
    shape.on('click', () => window.location.assign(cell.href));
  });
  map.fitBounds([[g.south, g.west], [g.north, g.east]], { padding: [4, 4], animate: false });
}

// Passo 3: a justificativa só aparece (e só é obrigatória) quando o agente escolhido não é o sugerido pela tela.
function missionForm(form) {
  const field = form.querySelector('.js-justification');
  const box = field?.querySelector('textarea');
  const suggested = form.querySelector('input[name="suggested_agent_id"]')?.value;
  if (!box || !suggested) return;
  const update = (animate = true) => {
    const on = (form.querySelector('input[name="agent_id"]:checked')?.value ?? suggested) !== suggested;
    reveal(field, on, animate);
    box.disabled = !on;
    box.required = on;
  };
  form.querySelectorAll('input[name="agent_id"]').forEach((r) => r.addEventListener('change', () => update()));
  update(false);
}

export function init() {
  document.querySelectorAll('[data-mission-form]').forEach(missionForm);
  const source = document.getElementById('plan-data');
  if (!source || !window.L) return;
  const data = JSON.parse(source.textContent);
  const cells = document.getElementById('cell-map');
  if (cells && data.grid && data.grid.all.length) cellsMap(cells, data);
}
