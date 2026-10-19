const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const { BASE, entrar } = require('./comum.cjs');
const measure = (page, action) => page.evaluate(async (ms) => {
  const t = []; let run = true; const loop = (ts) => { t.push(ts); if (run) requestAnimationFrame(loop); }; requestAnimationFrame(loop);
  await new Promise((r) => setTimeout(r, 30)); window.__go?.(); await new Promise((r) => setTimeout(r, ms)); run = false;
  const d = t.slice(1).map((x, i) => x - t[i]).sort((a, b) => a - b);
  return { frames: d.length, median: +d[Math.floor(d.length / 2)].toFixed(1), p95: +d[Math.floor(d.length * 0.95)].toFixed(1), max: +d[d.length - 1].toFixed(1) };
}, 700);
(async () => {
  const b = await chromium.launch();
  const res = {};
  for (const [name, size, prep, action] of [
    ['ficha (desktop)', { width: 1440, height: 900 }, null, '.listpanel a.row >> nth=2'],
    ['filtro + Flip (desktop)', { width: 1440, height: 900 }, null, 'label.chip--attention'],
    ['gaveta (celular)', { width: 390, height: 844 }, null, '[data-menu-btn]'],
    ['folha da ficha (celular)', { width: 390, height: 844 }, '.seg button[aria-label="Lista"]', '.listpanel a.row >> nth=2'],
  ]) {
    const p = await b.newPage({ viewport: size });
    await p.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    await entrar(p);
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    if (prep) { await p.click(prep); await p.waitForTimeout(500); }
    await p.evaluate((sel) => { window.__go = () => document.querySelector(sel.replace(/ >> nth=\d+/, '')) && (sel.includes('nth=2') ? document.querySelectorAll('.listpanel a.row')[2].click() : document.querySelector(sel).click()); }, action);
    res[name] = await measure(p);
    await p.close();
  }
  console.log(JSON.stringify(res, null, 1));
  await b.close();
})();
