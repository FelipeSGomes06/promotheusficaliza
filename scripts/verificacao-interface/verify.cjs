// Verificação de comportamento da central e das telas (Chromium + cabeçalhos reais do servidor).
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const BASE = process.env.PF_BASE || 'http://127.0.0.1:8091/promotheusfiscaliza';
const PASS = (process.env.PF_SENHA || '');
const OUT = process.argv[2] || __dirname + '/shots';
const report = {};
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const tileSvg = (url) => {
  const m = url.match(/(\d+)\/(\d+)\/(\d+)\.png/);
  return `<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#e9eef5" stroke="#9baec8"/><text x="128" y="132" font-size="16" text-anchor="middle" fill="#4a5a70">tile de teste ${m ? m.slice(1).join('/') : ''}</text></svg>`;
};

async function ctx(browser, opts = {}) {
  const c = await browser.newContext({ viewport: { width: opts.w || 1440, height: opts.h || 900 }, reducedMotion: opts.reduced ? 'reduce' : 'no-preference', hasTouch: !!opts.touch, isMobile: !!opts.mobile });
  await c.addInitScript(() => {
    window.__csp = [];
    document.addEventListener('securitypolicyviolation', (e) => window.__csp.push(`${e.violatedDirective} ${e.blockedURI}`));
  });
  const page = await c.newPage();
  page.logs = [];
  page.on('console', (m) => { if (['error', 'warning'].includes(m.type()) && !/tile\.openstreetmap/.test(m.location().url || '')) page.logs.push(`${m.type()}: ${m.text()}`); });
  page.on('pageerror', (e) => page.logs.push(`pageerror: ${e.message}`));
  if (opts.tiles) await page.route(/tile\.openstreetmap\.org/, (r) => r.fulfill({ status: 200, contentType: 'image/svg+xml', body: tileSvg(r.request().url()) }));
  else await page.route(/tile\.openstreetmap\.org/, (r) => r.abort());
  return { c, page };
}
async function login(page) {
  await page.goto(`${BASE}/entrar`);
  await page.fill('#u', (process.env.PF_USUARIO || 'avaliador'));
  await page.fill('#p', PASS);
  await Promise.all([page.waitForNavigation(), page.click('form[action$="/entrar"] button[type=submit]')]);
}
const central = async (page, q = '') => { await page.goto(`${BASE}/${q}`, { waitUntil: 'networkidle' }); await page.waitForFunction(() => window.__pfCentral); };
const diag = (page) => page.evaluate(() => ({ polls: window.__pfCentral.polls, applied: window.__pfCentral.applied, aborted: window.__pfCentral.aborted, tweens: window.__pfCentral.tweens(), layers: window.__pfCentral.layers() }));
const panelState = (page) => page.evaluate(() => { const p = document.querySelector('.detail'); return { visibility: getComputedStyle(p).visibility, opacity: +getComputedStyle(p).opacity, inert: p.inert, title: document.querySelector('#panel-title').textContent, url: location.search }; });

(async () => {
  const browser = await chromium.launch();
  const ids = await (await fetch(`${BASE}/api/central`)).json();
  const eq = ids.resources.find((r) => r.code === 'RAD-0001');
  const ag = ids.resources.find((r) => r.kind === 'AGENTE');

  // A. CSP e console em todas as rotas, desktop e celular, com e sem conta.
  try {
    const routes = ['/', '/equipamentos', `/equipamentos/${eq.id}`, '/agentes', `/agentes/${ag.id}`, '/manutencao', '/antecipacao', '/sobre', '/entrar', '/nao-existe', '/?visao=lista&selecionar=EQUIPAMENTO:' + eq.id];
    const authRoutes = ['/', '/equipamentos/novo', `/equipamentos/${eq.id}/editar`, `/agentes/novo`, '/contas', '/manutencao'];
    const res = [];
    for (const [w, h] of [[1440, 900], [390, 844]]) {
      const { c, page } = await ctx(browser, { w, h });
      for (const r of routes) {
        await page.goto(BASE + r, { waitUntil: 'networkidle' });
        await sleep(250);
        const m = await page.evaluate(() => ({ csp: window.__csp, hscroll: document.documentElement.scrollWidth > window.innerWidth }));
        res.push({ w, r, csp: m.csp, hscroll: m.hscroll, logs: page.logs.splice(0) });
      }
      await login(page);
      for (const r of authRoutes) {
        await page.goto(BASE + r, { waitUntil: 'networkidle' });
        await sleep(250);
        const m = await page.evaluate(() => ({ csp: window.__csp, hscroll: document.documentElement.scrollWidth > window.innerWidth }));
        res.push({ w, r: r + ' (conta)', csp: m.csp, hscroll: m.hscroll, logs: page.logs.splice(0) });
      }
      await c.close();
    }
    report.A_csp_console = { pages: res.length, problems: res.filter((x) => x.csp.length || x.hscroll || x.logs.filter((l) => !/404 \(Not Found\)/.test(l)).length) };
  } catch (e) { report['A_erro'] = String(e).slice(0, 400); }

  // B. Filtro, seleção, foco e mapa sobrevivem a vários ciclos de atualização (simulador ligado).
  try {
    const { c, page } = await ctx(browser);
    await central(page, '?categoria%5B%5D=RADAR&categoria%5B%5D=VEICULO');
    await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${eq.id}"]`);
    await sleep(900);
    const before = { panel: await panelState(page), chips: await page.locator('.active-filters .chip').allInnerTexts(), focus: await page.evaluate(() => document.activeElement.id), pane: await page.evaluate(() => document.querySelector('.leaflet-map-pane').style.transform), d: await diag(page), count: await page.locator('[data-count]').innerText() };
    await sleep(17000);
    const after = { panel: await panelState(page), chips: await page.locator('.active-filters .chip').allInnerTexts(), focus: await page.evaluate(() => document.activeElement.id), pane: await page.evaluate(() => document.querySelector('.leaflet-map-pane').style.transform), d: await diag(page), count: await page.locator('[data-count]').innerText() };
    report.B_polling = { before, after, ok: before.panel.url === after.panel.url && JSON.stringify(before.chips) === JSON.stringify(after.chips) && after.focus === 'panel-title' && before.pane === after.pane && after.d.applied - before.d.applied >= 3 && after.panel.visibility === 'visible', logs: page.logs };
    await c.close();
  } catch (e) { report['B_erro'] = String(e).slice(0, 400); }

  // C. Mudar o filtro antes da resposta: só a última resposta vale.
  try {
    const { c, page } = await ctx(browser);
    await central(page);
    await page.route(/\/api\/central/, async (r) => { await sleep(1200); r.continue(); });
    await page.click('summary:has-text("Tipo")');
    await page.check('input[name="categoria[]"][value="RADAR"]');
    await sleep(200);
    await page.uncheck('input[name="categoria[]"][value="RADAR"]');
    await page.check('input[name="categoria[]"][value="CAMERA"]');
    await sleep(2600);
    const titles = await page.locator('.listpanel [data-key]').evaluateAll((els) => els.map((e) => e.querySelector('.row__title').textContent));
    report.C_filter_race = { titles, ok: titles.length > 0 && titles.every((t) => t.startsWith('Câmera')), d: await diag(page), chips: await page.locator('.active-filters .chip').allInnerTexts(), logs: page.logs };
    await c.close();
  } catch (e) { report['C_erro'] = String(e).slice(0, 400); }

  // D. Abrir/fechar rapidamente termina no último estado pedido, sem animações penduradas.
  try {
    const { c, page } = await ctx(browser);
    await central(page);
    const row = page.locator(`.listpanel a.row[data-key="EQUIPAMENTO:${eq.id}"]`);
    for (let i = 0; i < 6; i++) { await row.click(); await sleep(40); await page.keyboard.press('Escape'); await sleep(40); }
    await row.click();
    await sleep(80);
    const midOpen = await panelState(page);
    await sleep(700);
    const endOpen = await panelState(page);
    await page.keyboard.press('Escape');
    await sleep(40);
    await row.click();
    await sleep(30);
    await page.keyboard.press('Escape');
    await sleep(800);
    const endClosed = await panelState(page);
    await sleep(1500); // o anel do ponto (finito, 1,4 s) termina; depois disso nada deve continuar animando
    const d = await diag(page);
    const clickThrough = await page.evaluate(() => { const r = document.querySelector('.detail').getBoundingClientRect(); const el = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return el ? (el.closest('.detail') ? 'ficha' : (el.closest('#map') ? 'mapa' : el.className)) : null; });
    report.D_rapid_toggle = { midOpen, endOpen, endClosed, d, clickThrough, ok: endOpen.visibility === 'visible' && endOpen.opacity === 1 && endClosed.visibility === 'hidden' && endClosed.inert && d.tweens === 0 && clickThrough === 'mapa', logs: page.logs };
    await c.close();
  } catch (e) { report['D_erro'] = String(e).slice(0, 400); }

  // E. Falha de rede e recuperação compreensíveis; aviso de erro não some por temporizador.
  try {
    const { c, page } = await ctx(browser);
    await central(page, '?atencao=1');
    await page.click('.listpanel a.row >> nth=0');
    await sleep(600);
    const sel = (await panelState(page)).url;
    await page.route(/\/api\/central/, (r) => r.abort());
    await page.waitForSelector('.toast--error', { timeout: 15000 });
    const offline = { sync: await page.locator('.sync.desktop-only').getAttribute('data-state'), toast: await page.locator('.toast--error').innerText() };
    await sleep(12000);
    const stillThere = await page.locator('.toast--error').count();
    await page.unroute(/\/api\/central/);
    await page.click('.toast--error button:has-text("Tentar agora")');
    await page.waitForSelector('.toast--success', { timeout: 15000 });
    await sleep(500);
    report.E_offline = { offline, errorToastAfter12s: stillThere, recovered: await page.locator('.toast--success').innerText(), errorToastAfterRecovery: await page.locator('.toast--error').count(), sync: await page.locator('.sync.desktop-only').getAttribute('data-state'), selectionKept: (await panelState(page)).url === sel, chips: await page.locator('.active-filters .chip').allInnerTexts(), logs: page.logs.filter((l) => !/ERR_FAILED|Failed to load resource/.test(l)) };
    await c.close();
  } catch (e) { report['E_erro'] = String(e).slice(0, 400); }

  // F. Envio: rótulo de carregamento, sem envio duplicado, sucesso só após a resposta do servidor.
  try {
    const { c, page } = await ctx(browser);
    await login(page);
    await central(page);
    const target = ids.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL' && r.category === 'CAMERA');
    let posts = 0;
    await page.route(/simular-falha/, async (r) => { posts += 1; await sleep(1500); r.continue(); });
    await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${target.id}"]`).catch(async () => { await page.click('.listpanel__more button'); await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${target.id}"]`); });
    await sleep(600);
    const btn = page.locator('.detail button:has-text("Simular falha")');
    await btn.click();
    await sleep(100);
    await btn.click({ force: true }).catch(() => {});
    await sleep(200);
    const during = { busy: await btn.getAttribute('aria-busy'), label: await btn.locator('.btn__spinner').innerText(), toastsBefore: await page.locator('.toast--success').count() };
    await page.waitForSelector('.toast--success', { timeout: 6000 });
    report.F_submit = { during, posts, toast: await page.locator('.toast--success').first().innerText(), ok: during.busy === 'true' && during.toastsBefore === 0 && posts === 1, logs: page.logs };
    await c.close();
  } catch (e) { report['F_erro'] = String(e).slice(0, 400); }

  // G. Formulário clássico (POST + redirecionamento): mensagem do servidor vira aviso animado.
  try {
    const { c, page } = await ctx(browser);
    await login(page);
    const snap = await (await fetch(`${BASE}/api/central`)).json();
    const al = snap.alerts.find((a) => a.kind === 'EQUIPAMENTO' && a.state === 'ABERTO');
    await page.goto(`${BASE}/equipamentos/${al ? al.resource_id : eq.id}`, { waitUntil: 'networkidle' });
    const ack = page.locator('form[action*="/reconhecer"] button');
    if (await ack.count()) {
      await Promise.all([page.waitForNavigation(), ack.first().click()]);
      await page.waitForSelector('.toast', { timeout: 4000 });
      report.G_flash = { toast: await page.locator('.toast').first().innerText(), stackRemoved: (await page.locator('[data-flash-stack]').count()) === 0, logs: page.logs };
    } else report.G_flash = { skipped: 'sem alerta aberto para registrar ciência' };
    await c.close();
  } catch (e) { report['G_erro'] = String(e).slice(0, 400); }

  // H. Movimento reduzido: sistema, escolha salva e escolha "completo" sobre o sistema.
  try {
    const res = {};
    for (const [name, opts, saved] of [['sistema', { reduced: true }, null], ['salvo', {}, 'reduced'], ['completo_sobre_sistema', { reduced: true }, 'full']]) {
      const { c, page } = await ctx(browser, opts);
      if (saved) await page.addInitScript((v) => localStorage.setItem('pf-motion', v), saved);
      await central(page);
      await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${eq.id}"]`);
      await sleep(30);
      const p = await panelState(page);
      res[name] = { panelAt30ms: { visibility: p.visibility, opacity: p.opacity }, label: await page.locator('.motion-pref').innerText(), html: await page.evaluate(() => document.documentElement.dataset.motion || '(sem atributo)'), stored: await page.evaluate(() => localStorage.getItem('pf-motion')) };
      await c.close();
    }
    report.H_reduced = res;
  } catch (e) { report['H_erro'] = String(e).slice(0, 400); }

  // I. Trocar de rota e voltar: uma só consulta periódica (sem duplicar timers).
  try {
    const { c, page } = await ctx(browser);
    await central(page);
    await page.click('.rail__link:has-text("Equipamentos")');
    await page.waitForLoadState('networkidle');
    await page.goBack({ waitUntil: 'networkidle' });
    await page.waitForFunction(() => window.__pfCentral);
    const a = await diag(page);
    await sleep(11000);
    const b = await diag(page);
    report.I_back = { pollsIn11s: b.polls - a.polls, ok: b.polls - a.polls <= 3, logs: page.logs };
    await c.close();
  } catch (e) { report['I_erro'] = String(e).slice(0, 400); }

  // J. Celular com toque: lista → folha da ficha → fechar; menu.
  try {
    const { c, page } = await ctx(browser, { w: 390, h: 844, touch: true, mobile: true });
    await central(page, '?visao=lista');
    await page.tap(`.listpanel a.row[data-key="EQUIPAMENTO:${eq.id}"]`);
    await sleep(700);
    const open = await panelState(page);
    await page.screenshot({ path: `${OUT}/v-celular-folha.png` });
    await page.tap('.detail button[aria-label="Fechar ficha"]');
    await sleep(700);
    const closed = await panelState(page);
    await page.tap('[data-menu-btn]');
    await sleep(600);
    const nav = await page.evaluate(() => ({ expanded: document.querySelector('[data-menu-btn]').getAttribute('aria-expanded'), left: document.querySelector('.rail').getBoundingClientRect().left, focus: document.activeElement.textContent.trim() }));
    report.J_mobile = { open, closed, nav, ok: open.visibility === 'visible' && closed.visibility === 'hidden' && nav.left === 0, logs: page.logs };
    await c.close();
  } catch (e) { report['J_erro'] = String(e).slice(0, 400); }

  // K. Teclado: Tab até um marcador, Enter abre a ficha, Esc devolve o foco ao marcador.
  try {
    const { c, page } = await ctx(browser);
    await central(page);
    const marker = page.locator('.pin-wrap').first();
    await marker.focus();
    const label = await marker.getAttribute('aria-label');
    await page.keyboard.press('Enter');
    await sleep(600);
    const opened = await page.evaluate(() => document.activeElement.id);
    await page.keyboard.press('Escape');
    await sleep(500);
    const back = await page.evaluate(() => document.activeElement.classList.contains('pin-wrap') || document.activeElement.classList.contains('leaflet-marker-icon'));
    report.K_keyboard = { label, focusAfterEnter: opened, focusBackOnMarker: back, logs: page.logs };
    await c.close();
  } catch (e) { report['K_erro'] = String(e).slice(0, 400); }

  // L. Tiles com o reset do Tailwind: imagens 256×256 lado a lado (tiles sintéticos só para medir o layout).
  try {
    const { c, page } = await ctx(browser, { tiles: true });
    await central(page);
    await sleep(800);
    const tiles = await page.evaluate(() => [...document.querySelectorAll('.leaflet-tile-loaded')].slice(0, 6).map((t) => { const r = t.getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height), getComputedStyle(t).maxWidth]; }));
    await page.screenshot({ path: `${OUT}/v-tiles-sinteticos.png` });
    report.L_tiles = { tiles, ok: tiles.length > 0 && tiles.every((t) => t[0] === 256 && t[1] === 256), banner: await page.locator('[data-tiles-banner]').isVisible() };
    await c.close();
  } catch (e) { report['L_erro'] = String(e).slice(0, 400); }

  await browser.close();
  fs.writeFileSync(`${OUT}/verify-report.json`, JSON.stringify(report, null, 2));
  for (const [k, v] of Object.entries(report)) console.log(k, JSON.stringify(v).slice(0, 600));
})();
