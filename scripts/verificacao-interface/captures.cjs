// Capturas finais (JPEG) para docs/evidencias/2026-10-18-identidade-e-motion/capturas.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const BASE = process.env.PF_BASE || 'http://127.0.0.1:8091/promotheusfiscaliza';
const PASS = (process.env.PF_SENHA || '');
const OUT = process.argv[2];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  const browser = await chromium.launch();
  const data = await (await fetch(`${BASE}/api/central`)).json();
  const radar = data.resources.find((r) => r.code === 'RAD-0001');
  const agent = data.resources.find((r) => r.kind === 'AGENTE' && r.title === 'Hermes 01');
  const shots = [];
  async function page(size, { auth = false, touch = false } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, hasTouch: touch, isMobile: touch });
    const p = await ctx.newPage();
    await p.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    if (auth) {
      await p.goto(`${BASE}/entrar`);
      await p.fill('#u', (process.env.PF_USUARIO || 'avaliador'));
      await p.fill('#p', PASS);
      await Promise.all([p.waitForNavigation(), p.click('form[action$="/entrar"] button[type=submit]')]);
    }
    return { ctx, p };
  }
  const shot = async (p, name, full = false) => { await sleep(500); await p.screenshot({ path: `${OUT}/${name}.jpg`, type: 'jpeg', quality: 82, fullPage: full }); shots.push(name); };
  const D = { width: 1440, height: 900 };
  const M = { width: 390, height: 844 };

  { const { ctx, p } = await page(D);
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' }); await shot(p, '01-central-desktop');
    await p.click(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(1200); await shot(p, '02-central-radar-selecionado');
    await p.click('summary:has-text("Tipo")'); await sleep(400); await shot(p, '03-central-menu-tipo');
    await p.check('input[name="categoria[]"][value="RADAR"]'); await p.keyboard.press('Escape'); await sleep(900); await shot(p, '04-central-filtro-radar');
    await p.click('.seg button[aria-label="Lista"]'); await sleep(900); await shot(p, '05-central-lista-com-ficha');
    await p.click('.detail button[aria-label="Fechar ficha"]'); await p.click('.active-filters button:has-text("Limpar tudo")'); await sleep(900);
    await p.click('.listpanel__kpis summary'); await sleep(500); await shot(p, '06-central-indicadores');
    await ctx.close(); }

  { const { ctx, p } = await page(D, { auth: true });
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    const fresh = await (await fetch(`${BASE}/api/central`)).json();
    const t = fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL' && r.category === 'BALANCA') || fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL');
    if (!(await p.locator(`.listpanel a.row[data-key="EQUIPAMENTO:${t.id}"]`).count())) await p.click('.listpanel__more button');
    await p.click(`.listpanel a.row[data-key="EQUIPAMENTO:${t.id}"]`); await sleep(900);
    await p.route(/simular-falha/, async (r) => { await sleep(1500); r.continue(); });
    await p.click('.detail button:has-text("Simular falha")'); await sleep(350); await shot(p, '07-envio-carregando');
    await p.waitForSelector('.toast--success'); await sleep(400); await shot(p, '08-aviso-sucesso');
    await p.route(/\/api\/central/, (r) => r.abort()); await p.waitForSelector('.toast--error', { timeout: 15000 }); await sleep(600); await shot(p, '09-aviso-erro-sem-conexao');
    await p.unroute(/\/api\/central/);
    await p.goto(`${BASE}/equipamentos/${radar.id}`, { waitUntil: 'networkidle' }); await shot(p, '10-ficha-equipamento');
    await p.goto(`${BASE}/equipamentos/novo`, { waitUntil: 'networkidle' });
    await p.selectOption('#f-type_code', 'RADAR'); await p.fill('#f-manufacturer', 'Marca de teste'); await p.fill('#f-model', 'Modelo X');
    await p.selectOption('#f-agency_id', { index: 1 });
    await p.check('input[name="specs[functions][]"][value="VELOCIDADE"]').catch(() => {});
    await Promise.all([p.waitForNavigation(), p.click('.form-actions button[type=submit]')]); await shot(p, '11-cadastro-com-erro');
    await p.goto(`${BASE}/manutencao`, { waitUntil: 'networkidle' }); await shot(p, '12-manutencao');
    await p.goto(`${BASE}/antecipacao`, { waitUntil: 'networkidle' }); await shot(p, '13-revisoes');
    await p.goto(`${BASE}/agentes/${agent.id}`, { waitUntil: 'networkidle' }); await shot(p, '14-agente');
    await ctx.close(); }

  { const { ctx, p } = await page(D);
    await p.goto(`${BASE}/equipamentos`, { waitUntil: 'networkidle' }); await shot(p, '15-equipamentos');
    await p.goto(`${BASE}/sobre`, { waitUntil: 'networkidle' }); await shot(p, '16-sobre');
    await p.goto(`${BASE}/entrar`, { waitUntil: 'networkidle' }); await shot(p, '17-entrar');
    await ctx.close(); }

  { const { ctx, p } = await page(M, { touch: true });
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' }); await shot(p, '20-celular-central');
    await p.tap('[data-menu-btn]'); await sleep(700); await shot(p, '21-celular-menu');
    await p.keyboard.press('Escape'); await sleep(500);
    await p.tap('.seg button[aria-label="Lista"]'); await sleep(700); await shot(p, '22-celular-lista');
    await p.tap(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(1000); await shot(p, '23-celular-ficha');
    await p.goto(`${BASE}/equipamentos/${radar.id}`, { waitUntil: 'networkidle' }); await shot(p, '24-celular-ficha-completa');
    await p.goto(`${BASE}/manutencao`, { waitUntil: 'networkidle' }); await shot(p, '25-celular-manutencao');
    await p.goto(`${BASE}/entrar`, { waitUntil: 'networkidle' }); await shot(p, '26-celular-entrar');
    await ctx.close(); }

  { const ctx = await browser.newContext({ viewport: D, reducedMotion: 'reduce' });
    const p = await ctx.newPage(); await p.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await p.click(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(100); await shot(p, '27-movimento-reduzido-sistema');
    await ctx.close(); }

  await browser.close();
  console.log(shots.length, 'capturas');
})();
