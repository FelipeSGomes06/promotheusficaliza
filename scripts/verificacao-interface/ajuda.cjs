// Ajuda contextual (o "?" de ui.help): presença nas telas, mouse, teclado, toque, Esc, posição perto das bordas
// (1440, 375 e 360 px), leitor de tela (descrição acessível), movimento reduzido e sem JavaScript. Só lê: não grava.
// Uso: node scripts/verificacao-interface/ajuda.cjs <pasta-de-saida>  → capturas/*.jpg + verificacao-navegador.json
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  fs.mkdirSync(`${OUT}/capturas`, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, quando: new Date().toISOString(), capturas: [], verificacoes: {}, telas: {}, console: [] };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  async function page(size, { reduced = false, js = true, touch = false } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, javaScriptEnabled: js, hasTouch: touch, isMobile: touch, reducedMotion: reduced ? 'reduce' : 'no-preference' });
    const p = await ctx.newPage();
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    p.on('console', (m) => { if (m.type() === 'error' && !/^Failed to load resource/.test(m.text())) report.console.push(`${m.type()}: ${m.text()}`); });
    p.on('pageerror', (e) => report.console.push(`pageerror: ${e.message}`));
    await p.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP: ${e.violatedDirective} ${e.blockedURI}`)));
    await entrar(p);
    return { ctx, p };
  }
  const shot = async (p, name) => { await sleep(300); await p.screenshot({ path: `${OUT}/capturas/${name}.jpg`, type: 'jpeg', quality: 80 }); report.capturas.push(name); };
  const open = (p, id) => p.evaluate((x) => document.getElementById(x).previousElementSibling.open, id); // caixinha = irmã do <details>
  // Caixinha inteira dentro da janela (com folga de 1 px para arredondamento).
  const inside = (p, id) => p.evaluate((x) => {
    const r = document.getElementById(x).getBoundingClientRect();
    return { ok: r.left >= -1 && r.top >= -1 && r.right <= document.documentElement.clientWidth + 1 && r.bottom <= window.innerHeight + 1 && r.width > 0, r: [r.left, r.top, r.right, r.bottom].map(Math.round) };
  }, id);
  const firstLink = async (p, path, sel) => { await p.goto(`${BASE}${path}`); return p.locator(sel).first().getAttribute('href'); };

  const D = { width: 1440, height: 900 };
  const { ctx, p } = await page(D);

  // 1. Presença: toda ajuda tem botão com aria-label "Ajuda sobre …" ligado a uma caixinha role=tooltip existente.
  const equipment = await firstLink(p, '/equipamentos', '.rows a[href*="/equipamentos/"]');
  const stolen = await firstLink(p, '/equipamentos?situacao[]=FURTADO', '.rows a[href*="/equipamentos/"]');
  const agent = await firstLink(p, '/agentes', '.rows a[href*="/agentes/"]');
  const strip = (h) => h && h.replace(/^.*promotheusfiscaliza/, '');
  const screens = ['/', '/equipamentos', '/equipamentos/novo', strip(equipment), strip(equipment) + '/editar', strip(stolen), '/agentes', '/agentes/novo', strip(agent),
    '/multas', '/multas/novo', '/manutencao', '/antecipacao', '/planejar', '/planejar/retrospecto', '/contas'].filter((s) => s && !s.startsWith('null'));
  for (const s of screens) {
    await p.goto(`${BASE}${s}`);
    report.telas[s] = await p.evaluate(() => Array.from(document.querySelectorAll('details[data-help]')).map((d) => {
      const b = d.querySelector('summary');
      const box = document.getElementById(b.getAttribute('aria-describedby'));
      return { label: b.getAttribute('aria-label'), ok: /^Ajuda sobre ./.test(b.getAttribute('aria-label') || '') && !!box && box.getAttribute('role') === 'tooltip' && box.textContent.trim().length > 10 };
    }));
  }
  const all = Object.values(report.telas).flat();
  check('presenca_em_todas_as_telas', Object.values(report.telas).every((l) => l.length > 0) && all.every((h) => h.ok), { total: all.length, sem_ajuda: Object.keys(report.telas).filter((k) => report.telas[k].length === 0) });
  const ids = await p.evaluate(() => Array.from(document.querySelectorAll('[id]')).map((e) => e.id));
  check('ids_unicos_ultima_tela', ids.length === new Set(ids).size);

  // 2. Mouse: passar abre (espiada), sair fecha; clique fixa; clique fora fecha.
  await p.goto(`${BASE}/equipamentos/novo`);
  const btn = p.locator('summary[aria-describedby="f-address_ref-tip"]');
  await btn.scrollIntoViewIfNeeded();
  await btn.hover(); await sleep(400);
  const hoverOpen = await open(p, 'f-address_ref-tip');
  const text = await p.locator('#f-address_ref-tip').innerText();
  await shot(p, 'd1440-hover-numero-ou-referencia');
  await p.mouse.move(5, 5); await sleep(500);
  check('mouse_passar_abre_e_sair_fecha', hoverOpen && !(await open(p, 'f-address_ref-tip')));
  check('texto_numero_ou_referencia', /oposto ao número 181/.test(text) && /KM 203/.test(text) && /S\/N/.test(text) && /Mercado do Tio/.test(text), text);
  await btn.click(); await sleep(300); await p.mouse.move(5, 5); await sleep(500);
  const pinned = await open(p, 'f-address_ref-tip');
  await p.mouse.click(700, 60); await sleep(400);
  check('clique_fixa_e_clique_fora_fecha', pinned && !(await open(p, 'f-address_ref-tip')));

  // 3. Teclado: Tab até o "?" abre; Esc fecha e o foco fica no "?"; Tab adiante fecha.
  await p.locator('#f-street').focus();
  await p.keyboard.press('Tab'); // "?" do número ou referência
  await sleep(300);
  const focusOpen = await open(p, 'f-address_ref-tip');
  await p.keyboard.press('Escape'); await sleep(300);
  const escClosed = !(await open(p, 'f-address_ref-tip'));
  const focusKept = await p.evaluate(() => document.activeElement?.getAttribute('aria-describedby'));
  await p.keyboard.press('Enter'); await sleep(300);
  const enterOpen = await open(p, 'f-address_ref-tip');
  await p.keyboard.press('Tab'); await sleep(400);
  check('teclado_foco_abre_esc_fecha_tab_sai', focusOpen && escClosed && focusKept === 'f-address_ref-tip' && enterOpen && !(await open(p, 'f-address_ref-tip')), { focusOpen, escClosed, focusKept, enterOpen });

  // 4. Leitor de tela: o "?" e o campo têm o texto da ajuda como descrição acessível.
  const cdp = await ctx.newCDPSession(p);
  const describe = async (sel) => {
    const { root } = await cdp.send('DOM.getDocument');
    const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: sel });
    const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
    return { name: nodes[0].name?.value, description: nodes[0].description?.value || '' };
  };
  const axBtn = await describe('summary[aria-describedby="f-address_ref-tip"]');
  const axInput = await describe('#f-address_ref');
  check('leitor_de_tela_le_o_texto', axBtn.name === 'Ajuda sobre Número ou referência' && /Mercado do Tio/.test(axBtn.description) && /Mercado do Tio/.test(axInput.description), { axBtn, axInput });

  // 5. Características geradas do catálogo: câmera (visão noturna, cor, resolução) e radar (funções, limite).
  await p.selectOption('#f-type_code', 'CAMERA'); await sleep(200);
  const camTips = await p.evaluate(() => ['night_vision', 'color_image', 'resolution'].map((f) => document.getElementById(`s-CAMERA-${f}-tip`)?.textContent.replace(/\s+/g, ' ').trim()));
  const radTips = await p.evaluate(() => ['functions', 'speed_limit_kmh'].map((f) => document.getElementById(`s-RADAR-${f}-tip`)?.textContent.replace(/\s+/g, ' ').trim()));
  check('caracteristicas_do_catalogo', /infravermelha/.test(camTips[0]) && /preto e branco/.test(camTips[1]) && /4K/.test(camTips[2]) && /Conversão proibida/.test(radTips[0]) && /Avanço de semáforo/.test(radTips[0]) && /20 a 120 km\/h/.test(radTips[1]), { camTips, radTips });
  const statusTip = await p.locator('#f-status-tip').textContent();
  check('situacoes_do_equipment_status', ['Não instalado', 'Operacional', 'Mau funcionamento', 'Em manutenção', 'Desativado', 'Furtado'].every((s) => statusTip.includes(s)), statusTip);
  await ctx.close();

  // 6. Bordas e celular: abre cada "?" visível e confere que a caixinha cabe na tela.
  for (const [w, h] of [[1440, 900], [375, 812], [360, 740]]) {
    const { ctx: c, p: m } = await page({ width: w, height: h }, { touch: w < 768 });
    const outside = [];
    for (const s of ['/equipamentos/novo', '/multas/novo', '/', '/antecipacao', '/planejar']) {
      await m.goto(`${BASE}${s}`);
      const tips = await m.evaluate(() => Array.from(document.querySelectorAll('details[data-help] > summary')).filter((b) => b.offsetParent && !b.closest('details:not([data-help]):not([open])')).map((b) => b.getAttribute('aria-describedby')));
      for (const id of tips) {
        const b = m.locator(`summary[aria-describedby="${id}"]`);
        if (!(await b.isVisible())) continue; // bloco escondido pelo script da página depois do carregamento
        try {
          await b.scrollIntoViewIfNeeded({ timeout: 3000 });
          if (w < 768) await b.tap({ timeout: 3000 }); else await b.click({ timeout: 3000 });
        } catch (e) { outside.push({ tela: s, id, erro: e.message.split('\n')[0] }); continue; }
        await sleep(250);
        const r = await inside(m, id);
        if (!(await open(m, id)) || !r.ok) outside.push({ tela: s, id, ...r });
        await m.keyboard.press('Escape'); await sleep(150);
      }
      if (s === '/equipamentos/novo') {
        const b = m.locator('summary[aria-describedby="f-address_ref-tip"]');
        await b.scrollIntoViewIfNeeded();
        if (w < 768) await b.tap(); else await b.click();
        await shot(m, `d${w}-numero-ou-referencia`);
        await m.keyboard.press('Escape');
      }
    }
    check(`caixinha_dentro_da_tela_${w}px`, outside.length === 0, outside);
    check(`sem_rolagem_horizontal_${w}px`, await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1));
    if (w < 768) {
      // Toque: abre ao tocar, fecha ao tocar fora (não depende de hover).
      await m.goto(`${BASE}/equipamentos/novo`);
      const b = m.locator('summary[aria-describedby="f-cep-tip"]');
      await b.scrollIntoViewIfNeeded(); await b.tap(); await sleep(300);
      const t1 = await open(m, 'f-cep-tip');
      await m.locator('h1').tap(); await sleep(400);
      check(`toque_abre_e_toque_fora_fecha_${w}px`, t1 && !(await open(m, 'f-cep-tip')));
    }
    await c.close();
  }

  // 7. Dentro do menu de filtros da central (overflow e painel aninhado): o menu continua aberto.
  {
    const { ctx: c, p: m } = await page({ width: 375, height: 812 }, { touch: true });
    await m.goto(`${BASE}/`);
    await m.locator('summary.chip', { hasText: 'Situação' }).tap(); await sleep(300);
    await m.locator('summary[aria-describedby="ajuda-situacao-1"]').tap(); await sleep(300);
    const r = await inside(m, 'ajuda-situacao-1');
    const menuOpen = await m.evaluate(() => document.getElementById('ajuda-situacao-1').closest('details.menu').open);
    await shot(m, 'd375-central-situacoes');
    check('ajuda_dentro_do_menu_da_central', r.ok && menuOpen && (await open(m, 'ajuda-situacao-1')), r);
    await c.close();
  }

  // 8. Movimento reduzido: abre sem animação (opacidade final 1 logo de cara).
  {
    const { ctx: c, p: m } = await page(D, { reduced: true });
    await m.goto(`${BASE}/equipamentos/novo`);
    const b = m.locator('summary[aria-describedby="f-cep-tip"]');
    await b.scrollIntoViewIfNeeded(); await b.click();
    const opacity = await m.evaluate(() => getComputedStyle(document.getElementById('f-cep-tip')).opacity);
    check('movimento_reduzido_sem_animacao', opacity === '1', opacity);
    await c.close();
  }

  // 9. Sem JavaScript: <details> nativo abre pelo clique e mostra o texto.
  {
    const { ctx: c, p: m } = await page(D, { js: false });
    await m.goto(`${BASE}/equipamentos/novo`);
    await m.locator('summary[aria-describedby="f-address_ref-tip"]').click();
    check('sem_javascript_details_nativo', await m.locator('#f-address_ref-tip').isVisible());
    await c.close();
  }

  check('console_sem_erros_nem_csp', report.console.length === 0, report.console);
  fs.writeFileSync(`${OUT}/verificacao-navegador.json`, JSON.stringify(report, null, 2));
  const failed = Object.entries(report.verificacoes).filter(([, v]) => !v.ok);
  console.log(`${Object.keys(report.verificacoes).length - failed.length} OK, ${failed.length} com falha`);
  for (const [k, v] of failed) console.log('FALHA', k, JSON.stringify(v.detalhe).slice(0, 600));
  await browser.close();
  process.exitCode = failed.length ? 1 : 0;
})();
