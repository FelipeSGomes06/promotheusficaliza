// Regressões de interface da auditoria de 25/10/2026 (A01, A10, A11). Só lê: as respostas do servidor que mudariam
// dados são simuladas no navegador. Uso: node scripts/verificacao-interface/auditoria.cjs <pasta-de-saida>
//   A01 — grupo do mapa acompanha o polling (posição, atenção, rótulo e situações do popup);
//   A10 — aviso com a mesma chave troca de erro para sucesso (classe e ícone);
//   A11 — landing liga as cenas abaixo de 900 px com movimento normal.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const ROOT = BASE.replace(/promotheusfiscaliza\/?$/, '');

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, quando: new Date().toISOString(), verificacoes: {} };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  // A01: o 1º polling junta dois recursos num grupo; o 2º muda a atenção e a situação de um deles (mesmo grupo).
  {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    const p = await ctx.newPage();
    await entrar(p);
    let n = 0; let a = null; let b = null; let base = null;
    const key = (r) => `${r.kind}:${r.id}`;
    await p.route(/\/api\/central/, async (route) => {
      const res = await route.fetch();
      const snap = await res.json();
      n += 1;
      const located = snap.resources.filter((r) => r.position && r.position.lat);
      if (!a) { a = key(located[0]); b = key(located.find((r) => !r.attention.length && key(r) !== a)); base = { ...located[0].position }; }
      const A = snap.resources.find((r) => key(r) === a);
      const B = snap.resources.find((r) => key(r) === b);
      A.position = { ...A.position, lat: base.lat, lng: base.lng };
      B.position = { ...B.position, lat: String(Number(base.lat) + 0.00005), lng: base.lng };
      if (n >= 2) {
        B.attention = (snap.resources.find((r) => r.attention.length) || { attention: [{ code: 'TESTE', label: 'teste' }] }).attention;
        B.status = 'FALHA'; B.status_label = 'Situação do 2º instantâneo';
      }
      await route.fulfill({ response: res, json: snap });
    });
    await p.goto(`${BASE}/`);
    await p.waitForSelector('.cluster-wrap');
    const groups = () => p.evaluate(() => [...document.querySelectorAll('.cluster-wrap')].map((m) => ({ label: m.getAttribute('aria-label'), t: m.style.transform })));
    const initial = (await groups()).map((g) => g.t);
    const until = async (k) => { const t0 = Date.now(); while (n < k && Date.now() - t0 < 30000) await p.waitForTimeout(200); await p.waitForTimeout(1200); };
    await until(1);
    const g1 = (await groups()).find((g) => !initial.includes(g.t));
    await until(2);
    const g2 = (await groups()).find((g) => !initial.includes(g.t));
    await p.evaluate((t) => [...document.querySelectorAll('.cluster-wrap')].find((m) => m.style.transform === t).click(), g2.t);
    await p.waitForSelector('.cluster-list', { timeout: 5000 }).catch(() => {});
    const popup = await p.evaluate(() => document.querySelector('.cluster-list')?.innerText.replace(/\s+/g, ' ') || '');
    check('a01_grupo_acompanha_o_polling', /1 precisam/.test(g1.label) && /2 precisam/.test(g2.label) && popup.includes('Situação do 2º instantâneo'), { antes: g1.label, depois: g2.label, popup });
    await p.unrouteAll({ behavior: 'ignoreErrors' });
    await ctx.close();
  }

  // A10: ciência recusada e depois aceita, mesma chave (ack-{id}).
  {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    const p = await ctx.newPage();
    await entrar(p);
    await p.goto(`${BASE}/`);
    // Um agente em alerta (precisa de atenção); a ficha só mostra o botão se a ciência ainda não foi registrada.
    const candidates = await p.evaluate(() => JSON.parse(document.getElementById('snapshot-data').textContent).resources.filter((r) => r.kind === 'AGENTE' && r.attention.length).map((r) => r.id));
    let n = 0;
    await p.route(/\/alertas\/\d+\/reconhecer/, (route) => {
      n += 1;
      return route.fulfill({ status: n === 1 ? 409 : 200, contentType: 'application/json', body: JSON.stringify(n === 1 ? { ok: false, message: 'Recusa simulada.' } : { ok: true, message: 'Aceita na segunda tentativa.' }) });
    });
    const btn = p.locator('button:has-text("Registrar ciência"):visible').first();
    for (const id of candidates) {
      await p.goto(`${BASE}/?selecionar=AGENTE:${id}`);
      if (await btn.waitFor({ timeout: 3000 }).then(() => true, () => false)) break;
    }
    const toast = () => p.evaluate(() => { const t = document.querySelector('.toast'); return t && { classe: t.className, icone: t.querySelector('.toast__icon use')?.getAttribute('href')?.split('#')[1], texto: t.innerText.replace(/\s+/g, ' ').trim() }; });
    let erro = null; let sucesso = null;
    if (await btn.count()) {
      await btn.click(); await p.waitForTimeout(700); erro = await toast();
      await btn.click(); await p.waitForTimeout(700); sucesso = await toast();
    }
    check('a10_aviso_troca_de_erro_para_sucesso', erro?.classe.includes('toast--error') && sucesso?.classe.includes('toast--success') && sucesso?.icone === 'i-check-circle', { erro, sucesso });
    await p.unrouteAll({ behavior: 'ignoreErrors' });
    await ctx.close();
  }

  // A11: cenas da landing (is-ativa) em cada largura e preferência de movimento.
  {
    const res = {};
    for (const [w, motion] of [[390, 'no-preference'], [899, 'no-preference'], [900, 'no-preference'], [390, 'reduce']]) {
      const ctx = await browser.newContext({ viewport: { width: w, height: 800 }, reducedMotion: motion });
      const p = await ctx.newPage();
      await p.goto(ROOT, { waitUntil: 'load' });
      await p.waitForTimeout(600);
      const seen = new Set();
      for (let i = 0; i < 30; i += 1) {
        await p.mouse.wheel(0, 500);
        await p.waitForTimeout(100);
        (await p.evaluate(() => [...document.querySelectorAll('[data-cena]')].map((c, k) => (c.classList.contains('is-ativa') ? k : -1)))).filter((k) => k >= 0).forEach((k) => seen.add(k));
      }
      res[`${w}px ${motion}`] = [seen.size, await p.evaluate(() => document.querySelectorAll('[data-cena]').length)];
      await ctx.close();
    }
    check('a11_landing_ativa_as_cenas_em_toda_largura', Object.values(res).every(([seen, total]) => total > 0 && seen === total), res);
  }

  fs.writeFileSync(`${OUT}/auditoria-navegador.json`, JSON.stringify(report, null, 1));
  await browser.close();
  const failed = Object.entries(report.verificacoes).filter(([, v]) => !v.ok);
  console.log(failed.length ? `FALHOU: ${failed.map(([k]) => k).join(', ')}` : `ok: ${Object.keys(report.verificacoes).length} verificações`);
  process.exitCode = failed.length ? 1 : 0;
})().catch((e) => { console.error(e); process.exit(1); });
