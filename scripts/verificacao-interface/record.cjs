// Gravações curtas das interações (Playwright recordVideo, WebM) — o cursor não aparece no vídeo; o hover aparece pela mudança do elemento.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.PF_BASE || 'http://127.0.0.1:8091/promotheusfiscaliza';
const PASS = (process.env.PF_SENHA || '');
const OUT = process.argv[2];
const only = process.argv[3] || '';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
fs.mkdirSync(OUT, { recursive: true });

async function clip(browser, name, size, fn) {
  if (only && !name.startsWith(only)) return;
  const dir = fs.mkdtempSync(path.join(require('os').tmpdir(), 'rec-'));
  const ctx = await browser.newContext({ viewport: size, recordVideo: { dir, size }, hasTouch: size.width < 500, isMobile: size.width < 500 });
  const page = await ctx.newPage();
  await page.route(/tile\.openstreetmap\.org/, (r) => r.abort());
  const logs = [];
  page.on('pageerror', (e) => logs.push(e.message));
  await fn(page, ctx);
  await sleep(400);
  const video = page.video();
  await ctx.close();
  fs.renameSync(await video.path(), path.join(OUT, `${name}.webm`));
  console.log(name, logs.length ? logs : 'ok');
}
async function login(page) {
  await page.goto(`${BASE}/entrar`);
  await page.fill('#u', (process.env.PF_USUARIO || 'avaliador'));
  await page.fill('#p', PASS);
  await Promise.all([page.waitForNavigation(), page.click('form[action$="/entrar"] button[type=submit]')]);
}
const hover = async (page, sel, ms = 500) => { const b = await page.locator(sel).first().boundingBox(); if (b) { await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2, { steps: 12 }); await sleep(ms); } };

(async () => {
  const browser = await chromium.launch();
  const desk = { width: 1440, height: 900 };
  const data = await (await fetch(`${BASE}/api/central`)).json();
  const radar = data.resources.find((r) => r.code === 'RAD-0001');

  await clip(browser, '01-hover-e-pressao', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(500);
    for (const sel of ['.rail__link:has-text("Equipamentos")', '.rail__link:has-text("Manutenção")', 'summary.chip:has-text("Tipo")', 'label.chip--attention', 'button[aria-label="Atualizar agora"]', '.rail .btn--primary', '.listpanel a.row >> nth=2']) await hover(page, sel, 650);
    const b = await page.locator('.rail .btn--primary').boundingBox();
    await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2);
    await page.mouse.down(); await sleep(450); await page.mouse.up(); await sleep(150);
    await page.goBack().catch(() => {});
    await sleep(600);
  });

  await clip(browser, '02-filtro-e-reorganizacao', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(600);
    await page.click('summary:has-text("Tipo")'); await sleep(450);
    await page.check('input[name="categoria[]"][value="RADAR"]'); await sleep(900);
    await page.check('input[name="categoria[]"][value="CAMERA"]'); await sleep(900);
    await page.keyboard.press('Escape'); await sleep(400);
    await page.click('label.chip--attention'); await sleep(1100);
    await page.click('.active-filters .chip >> nth=0'); await sleep(1000);
    await page.click('.active-filters button:has-text("Limpar tudo")'); await sleep(1100);
    await page.click('.listpanel__more button'); await sleep(1000);
  });

  await clip(browser, '03-mapa-selecao-e-ficha', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(600);
    await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(1600);
    await page.click('.listpanel a.row >> nth=1'); await sleep(1300);
    await page.click('.detail button[aria-label="Fechar ficha"]'); await sleep(900);
    // Interrupção: abre e fecha seguidos; termina aberto (último pedido).
    const row = page.locator(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`);
    await row.click(); await sleep(90); await page.keyboard.press('Escape'); await sleep(90); await row.click(); await sleep(1200);
    await page.click('.seg button[aria-label="Lista"]'); await sleep(1100);
    await page.click('.detail button:has-text("Mostrar no mapa")'); await sleep(1400);
  });

  await clip(browser, '04-envio-carregando-e-sucesso', desk, async (page) => {
    await login(page);
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await page.route(/simular-falha/, async (r) => { await sleep(1200); r.continue(); });
    const fresh = await (await fetch(`${BASE}/api/central`)).json();
    const target = fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL' && r.category === 'BALANCA') || fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL');
    await sleep(500);
    const row = page.locator(`.listpanel a.row[data-key="EQUIPAMENTO:${target.id}"]`);
    if (!(await row.count())) await page.click('.listpanel__more button');
    await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${target.id}"]`); await sleep(1100);
    await hover(page, '.detail button:has-text("Simular falha")', 600);
    await page.click('.detail button:has-text("Simular falha")'); await sleep(2600);
    await hover(page, '.toast', 1500);
    await page.mouse.move(700, 450, { steps: 8 }); await sleep(5500);
  });

  await clip(browser, '05-erro-sem-conexao-e-recuperacao', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(500);
    await page.route(/\/api\/central/, (r) => r.abort());
    await page.waitForSelector('.toast--error', { timeout: 15000 });
    await sleep(3500);
    await page.unroute(/\/api\/central/);
    await page.click('.toast--error button:has-text("Tentar agora")');
    await sleep(3000);
  });

  await clip(browser, '06-dados-recebidos-realce', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(800);
    // Outra sessão (operador) registra uma falha simulada; esta tela só observa a próxima consulta periódica.
    const other = await browser.newContext();
    const op = await other.newPage();
    await login(op);
    const fresh = await (await fetch(`${BASE}/api/central`)).json();
    const target = fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL' && r.category === 'CANCELA') || fresh.resources.find((r) => r.kind === 'EQUIPAMENTO' && r.status === 'OPERACIONAL');
    const csrf = await op.evaluate(() => document.querySelector('input[name="_csrf"]').value);
    await op.evaluate(async ({ url, csrf }) => fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrf, Accept: 'application/json' } }), { url: `${BASE}/equipamentos/${target.id}/simular-falha`, csrf });
    await other.close();
    await sleep(8000);
  });

  await clip(browser, '07-celular-menu-lista-e-folha', { width: 390, height: 844 }, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(700);
    await page.tap('[data-menu-btn]'); await sleep(900);
    await page.keyboard.press('Escape'); await sleep(700);
    await page.tap('.seg button[aria-label="Lista"]'); await sleep(800);
    await page.tap(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(1300);
    await page.tap('.detail button[aria-label="Fechar ficha"]'); await sleep(900);
    await page.tap('.seg button[aria-label="Mapa"]'); await sleep(900);
  });

  await clip(browser, '08-movimento-reduzido', desk, async (page) => {
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await sleep(500);
    await page.click('.motion-pref'); await sleep(800);
    await page.click(`.listpanel a.row[data-key="EQUIPAMENTO:${radar.id}"]`); await sleep(1000);
    await page.click('.detail button[aria-label="Fechar ficha"]'); await sleep(700);
    await page.click('.motion-pref'); await sleep(800);
  });

  await browser.close();
})();
