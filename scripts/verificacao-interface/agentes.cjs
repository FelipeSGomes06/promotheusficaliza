// Cadastro de agente (base pelo mapa ou pelo endereço, rua pelo CEP): capturas (JPEG) e verificações de comportamento num banco de
// demonstração. Cadastra e edita agentes de teste: use só em banco de demonstração.
// As respostas de /api/cep e /api/localizar são trocadas no navegador (page.route) para ver cada estado da tela sem
// depender dos provedores; "consulta_real_..." e o bloco sem JavaScript usam o servidor de verdade, que os consulta.
// Uso: node scripts/verificacao-interface/agentes.cjs <pasta-de-saida>  → capturas/*.jpg + verificacao-navegador.json
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const ANSWERS = {
  17501000: { status: 200, body: { cep: '17501-000', situacao: 'ENCONTRADO', rua: 'Avenida Sampaio Vidal', bairro: 'Centro', mensagem: 'Rua encontrada pelo CEP.' } },
  17500000: { status: 200, body: { cep: '17500-000', situacao: 'SEM_RUA', rua: '', bairro: '', mensagem: 'Este é um CEP geral de Marília/SP, sem rua. Se souber, digite a rua abaixo.' } },
  17509000: { status: 200, body: { cep: '17509-000', situacao: 'ENCONTRADO', rua: 'Rua Paraíba', bairro: 'Fragata', mensagem: 'Rua encontrada pelo CEP.' } },
};
const waitStreet = (pg, v) => pg.waitForFunction((x) => document.getElementById('f-base_street').value === x, v, { timeout: 5000 });
const waitStatus = (pg, re) => pg.waitForFunction((src) => new RegExp(src).test(document.querySelector('[data-cep-status]').textContent), re, { timeout: 5000 });
const POINTS = { 'Avenida Sampaio Vidal': { lat: '-22.213900', lng: '-49.945800' }, 'Rua Paraíba': { lat: '-22.226000', lng: '-49.930000' } };

(async () => {
  fs.mkdirSync(`${OUT}/capturas`, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, quando: new Date().toISOString(), capturas: [], verificacoes: {}, console: [], recursos: [], consultas: [] };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  async function page(size, { reduced = false, js = true, simulated = true } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, javaScriptEnabled: js, reducedMotion: reduced ? 'reduce' : 'no-preference' });
    const p = await ctx.newPage();
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    if (simulated) {
      await ctx.route(/\/api\/cep\//, async (r) => {
        const digits = r.request().url().split('/api/cep/')[1];
        report.consultas.push(digits);
        const a = ANSWERS[digits] || { status: 404, body: { cep: '', situacao: 'NAO_ENCONTRADO', rua: '', bairro: '', mensagem: 'CEP não encontrado. Confira o número ou digite a rua abaixo.' } };
        await sleep(300); // deixa o "Procurando…" aparecer
        await r.fulfill({ status: a.status, contentType: 'application/json; charset=utf-8', body: JSON.stringify(a.body) });
      });
      await ctx.route(/\/api\/localizar\?/, async (r) => {
        const rua = new URL(r.request().url()).searchParams.get('rua');
        report.consultas.push(`localizar:${rua}`);
        const found = POINTS[rua];
        await sleep(300);
        await r.fulfill({ status: found ? 200 : 404, contentType: 'application/json; charset=utf-8', body: JSON.stringify(found
          ? { situacao: 'ENCONTRADO', ...found, rua, mensagem: 'Base posta no endereço (ponto aproximado, no meio da rua). Confira no mapa; para ajustar, clique no ponto certo.' }
          : { situacao: 'NAO_ENCONTRADO', lat: null, lng: null, rua: '', mensagem: 'Não achamos esta rua em Marília. Confira a rua e o CEP ou marque a base no mapa.' }) });
      });
    }
    p.on('console', (m) => { if (['error', 'warning'].includes(m.type())) (/^Failed to load resource/.test(m.text()) ? report.recursos : report.console).push(`${m.type()}: ${m.text()}`); });
    p.on('pageerror', (e) => report.console.push(`pageerror: ${e.message}`));
    await p.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP: ${e.violatedDirective} ${e.blockedURI}`)));
    await entrar(p);
    return { ctx, p };
  }
  const shot = async (p, name, full = false) => { await sleep(400); await p.screenshot({ path: `${OUT}/capturas/${name}.jpg`, type: 'jpeg', quality: 80, fullPage: full }); report.capturas.push(name); };
  const noHScroll = (p) => p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
  const status = (p) => p.locator('[data-cep-status]').innerText();
  const typeCep = async (p, cep) => { await p.fill('#f-base_cep', ''); await p.locator('#f-base_cep').pressSequentially(cep, { delay: 20 }); };
  const D = { width: 1440, height: 900 };

  let created = null;
  { const { ctx, p } = await page(D);
    await p.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
    check('instrucao_do_mapa', await p.isVisible('text=Clique no mapa para marcar ou mudar a base.') && !(await p.isVisible('[data-without-map]')));
    check('estado_inicial_do_mapa', (await p.locator('#picker-status').innerText()) === 'Clique no mapa para marcar a base.', await p.locator('#picker-status').innerText());
    check('coordenadas_sem_campos', (await p.locator('#f-base_lat').getAttribute('type')) === 'hidden' && (await p.locator('#f-base_lng').getAttribute('type')) === 'hidden' && !(await p.isVisible('text=Latitude')) && (await p.locator('details.advanced').count()) === 0);
    check('sem_logradouro_nem_bairro_com_numero', (await p.locator('[name="region_id"]').count()) === 0 && !(await p.isVisible('text=Logradouro')) && await p.isVisible('#f-base_ref'));
    // Envio sem ponto: erro no ponto; ao marcar no mapa, o erro some (não fica "Marque a base…" com o ponto marcado).
    await p.fill('#f-call_sign', 'Hermes Erro'); await p.selectOption('#f-agency_id', { index: 1 }); await p.fill('#f-base_name', 'Base erro');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]:has-text("Cadastrar agente")')]);
    const hadError = await p.isVisible('#err-base_lat');
    const pb = await p.locator('#picker').boundingBox(); await p.locator('#picker').scrollIntoViewIfNeeded();
    const pb2 = await p.locator('#picker').boundingBox(); await p.mouse.click(pb2.x + pb2.width / 2, pb2.y + pb2.height / 2); await sleep(200);
    check('erro_do_ponto_some_ao_marcar', pb !== null && hadError && !(await p.isVisible('#err-base_lat')) && (await p.inputValue('#f-base_lat')) !== '');
    await p.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
    check('rua_e_cidade_editaveis', await p.isEditable('#f-base_street') && await p.isEditable('#f-base_city') && (await p.inputValue('#f-base_city')) === 'Marília');
    const cols = await p.evaluate(() => ['f-base_cep', 'f-base_city', 'f-base_street', 'f-base_ref'].map((id) => { const r = document.getElementById(id).getBoundingClientRect(); return [Math.round(r.left), Math.round(r.width), Math.round(r.top)]; }));
    check('endereco_em_duas_colunas_iguais', cols[0][0] === cols[2][0] && cols[1][0] === cols[3][0] && cols[0][1] === cols[1][1] && cols[2][1] === cols[3][1] && cols[0][2] === cols[1][2] && cols[2][2] === cols[3][2], cols);
    await p.locator('.picker').scrollIntoViewIfNeeded(); await shot(p, '01-novo-agente');

    // Sem marcar no mapa: o CEP traz a rua e a base vai para o endereço sozinha (respostas simuladas).
    { const n = await ctx.newPage();
      await n.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
      await typeCep(n, '17501-000'); await n.waitForFunction(() => document.getElementById('f-base_lat').value !== '', null, { timeout: 5000 }).catch(() => {});
      const picker = await n.locator('#picker-status').innerText();
      check('endereco_poe_a_base_sem_clicar', (await n.inputValue('#f-base_lat')) === '-22.213900' && (await n.inputValue('#f-base_lng')) === '-49.945800'
        && picker.startsWith('Base marcada em -22.213900, -49.945800') && /Base posta no endereço/.test(await n.locator('[data-locate-status]').innerText()), picker);
      await n.locator('.base-address').evaluate((e) => e.scrollIntoView({ block: 'center' })); await shot(n, '01b-base-pelo-endereco');
      const box = await n.locator('#picker').boundingBox(); await n.locator('#picker').scrollIntoViewIfNeeded();
      const b2 = await n.locator('#picker').boundingBox(); await n.mouse.click(b2.x + b2.width * 0.3, b2.y + b2.height * 0.6); await sleep(200);
      const clicked = await n.inputValue('#f-base_lat');
      await n.click('[data-locate]'); await n.waitForFunction(() => document.getElementById('f-base_lat').value === '-22.213900', null, { timeout: 5000 }).catch(() => {});
      check('clique_ajusta_e_botao_volta_ao_endereco', box !== null && clicked !== '-22.213900' && (await n.inputValue('#f-base_lat')) === '-22.213900', { clicked });
      await n.fill('#f-base_cep', ''); await n.locator('#f-base_cep').pressSequentially('17500000', { delay: 20 }); await waitStatus(n, 'CEP geral');
      await n.fill('#f-base_street', 'Rua Inexistente'); await n.click('[data-locate]'); await n.waitForSelector('[data-locate-status].is-note', { timeout: 5000 }).catch(() => {});
      check('rua_nao_achada_explica', /Não achamos esta rua/.test(await n.locator('[data-locate-status]').innerText()) && (await n.inputValue('#f-base_lat')) === '-22.213900');
      await n.close(); }

    // Clique marca; outro clique muda o ponto (latitude e longitude acompanham).
    const box = await p.locator('#picker').boundingBox();
    await p.mouse.click(box.x + box.width * 0.4, box.y + box.height * 0.5); await sleep(200);
    const first = [await p.inputValue('#f-base_lat'), await p.inputValue('#f-base_lng')];
    await p.mouse.click(box.x + box.width * 0.7, box.y + box.height * 0.3); await sleep(200);
    const second = [await p.inputValue('#f-base_lat'), await p.inputValue('#f-base_lng')];
    check('clique_marca_e_muda_o_ponto', first[0] !== '' && second[0] !== '' && (first[0] !== second[0] || first[1] !== second[1]), { first, second });
    check('estado_do_mapa_depois_do_clique', (await p.locator('#picker-status').innerText()).startsWith(`Base marcada em ${second[0]}, ${second[1]}.`));
    // Latitude e longitude só pelo mapa: travadas, digitar não muda; pelo teclado, Enter no mapa marca o centro.
    await p.locator('#picker').focus(); await p.keyboard.press('ArrowUp'); await sleep(400); await p.keyboard.press('Enter'); await sleep(200);
    const keyed = [await p.inputValue('#f-base_lat'), await p.inputValue('#f-base_lng')];
    check('teclado_enter_marca_o_centro', keyed[0] !== second[0] && keyed[0] !== '', { keyed });

    // CEP com rua (simulada): "Rua: …", campo oculto mas preenchido; apagar um dígito tira a rua.
    await typeCep(p, '17501-000'); await sleep(350);
    const loading = await status(p);
    await waitStreet(p, 'Avenida Sampaio Vidal');
    check('carregando_com_texto', /Procurando a rua do CEP 17501-000/.test(loading), loading);
    check('cep_preenche_a_rua_editavel', /Rua preenchida pelo CEP/.test(await status(p)) && await p.isEditable('#f-base_street') && (await p.inputValue('#f-base_street')) === 'Avenida Sampaio Vidal', await status(p));
    await p.locator('.base-address').evaluate((e) => e.scrollIntoView({ block: 'center' })); await shot(p, '02-cep-com-rua');
    await p.press('#f-base_cep', 'Backspace'); await sleep(100);
    check('cep_incompleto_tira_a_rua', (await p.inputValue('#f-base_street')) === '');

    // CEP geral e CEP inexistente (simulados): campo "Rua" à vista, com o motivo.
    await typeCep(p, '17500-000'); await waitStatus(p, 'CEP geral');
    check('cep_geral_pede_a_rua', /CEP geral de Marília/.test(await status(p)), await status(p));
    await p.fill('#f-base_street', 'Rua digitada');
    await typeCep(p, '99999-999'); await sleep(800);
    check('cep_inexistente_pede_a_rua_e_mantem_digitada', /CEP não encontrado/.test(await status(p)) && (await p.inputValue('#f-base_street')) === 'Rua digitada', await status(p));
    await shot(p, '03-cep-sem-rua');

    // Envio com CEP encontrado: ficha com a rua, o bairro derivado e "Marília/SP".
    await p.fill('#f-base_street', ''); await typeCep(p, '17501000'); await waitStreet(p, 'Avenida Sampaio Vidal');
    await p.fill('#f-call_sign', 'Hermes Teste CEP'); await p.selectOption('#f-agency_id', { index: 1 }); await p.fill('#f-base_name', 'Base de teste (fictícia)');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]:has-text("Cadastrar agente")')]);
    created = (p.url().match(/\/agentes\/(\d+)/) || [])[1] || null;
    const facts = await p.locator('#onde + .facts').innerText().catch(() => '');
    check('cadastro_so_com_nome_ponto_e_cep', created !== null && /Rua: Avenida Sampaio Vidal/.test(facts) && /Bairro: \S/.test(facts) && /Marília\/SP/.test(facts), facts.split('\n').slice(0, 3));
    await shot(p, '04-ficha-nova');

    // Edição: mostra a rua gravada sem consultar de novo; mudar o CEP refaz a consulta.
    const before = report.consultas.length;
    await p.goto(`${BASE}/agentes/${created}/editar`, { waitUntil: 'networkidle' });
    check('edicao_mostra_rua_gravada_sem_consultar', (await p.inputValue('#f-base_street')) === 'Avenida Sampaio Vidal' && report.consultas.length === before, { status: await status(p), consultas: report.consultas.slice(before) });
    await typeCep(p, '17509-000'); await waitStreet(p, 'Rua Paraíba');
    check('edicao_mudar_cep_refaz_consulta', report.consultas.slice(before).includes('17509000') && (await p.inputValue('#f-base_street')) === 'Rua Paraíba');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]:has-text("Salvar alterações")')]);
    const history = await p.locator('.timeline').innerText();
    check('historico_com_rotulos', /Cadastro alterado: rua, CEP\./.test(history) && !/base_/.test(history), history.split('\n').slice(0, 4));
    await ctx.close();
  }

  // Consulta de verdade (servidor → provedor). Neste ambiente o provedor pode estar inalcançável: aí a resposta
  // amigável aparece e o campo "Rua" fica à vista. O resultado (qualquer um) fica no relatório.
  { const { ctx, p } = await page(D, { simulated: false });
    await p.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
    const answer = p.waitForResponse(/\/api\/cep\//, { timeout: 15000 });
    await typeCep(p, '17501-000');
    const res = await answer; const body = await res.json().catch(() => null);
    await sleep(300);
    check('consulta_real_nunca_trava_o_formulario', body !== null && await p.isEditable('#f-base_street'), { http: res.status(), situacao: body?.situacao, status: await status(p) });
    await p.locator('.base-address').evaluate((e) => e.scrollIntoView({ block: 'center' })); await shot(p, '05-consulta-real');
    await ctx.close();
  }

  // Ficha e edição de um agente da carga de demonstração (rua, referência e bairro antigos).
  { const { ctx, p } = await page(D);
    await p.goto(`${BASE}/agentes?ordem=codigo`, { waitUntil: 'networkidle' });
    const href = await p.locator('a[href*="/agentes/"]:not([href$="/novo"])').first().getAttribute('href');
    await p.goto(new URL(href, BASE).href.replace(/\/?$/, '') + '/editar', { waitUntil: 'networkidle' });
    check('antigo_abre_com_rua_e_referencia', (await p.inputValue('#f-base_street')) !== '' && (await p.inputValue('#f-base_ref')) === 'Endereço de base ilustrativo', await status(p));
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]:has-text("Salvar alterações")')]);
    check('antigo_salva_sem_erro', /\/agentes\/\d+$/.test(p.url()) && /Endereço de base ilustrativo/.test(await p.locator('#onde + .facts').innerText()), p.url());
    await ctx.close();
  }

  // Sem JavaScript: campo "Rua" visível e digitável; envio só com nome e ponto digitados.
  { const { ctx, p } = await page(D, { js: false });
    await p.goto(`${BASE}/agentes/novo`);
    check('sem_js_rua_visivel', await p.isVisible('#f-base_street') && await p.isEditable('#f-base_street'));
    check('sem_js_coordenadas_sem_campos', (await p.locator('#f-base_lat').getAttribute('type')) === 'hidden' && await p.isEditable('#f-base_city'));
    check('sem_js_sem_mapa_vazio', !(await p.isVisible('#picker')) && !(await p.isVisible('#picker-howto')) && await p.isVisible('[data-without-map]'));
    await p.fill('#f-call_sign', 'Hermes Sem JS'); await p.selectOption('#f-agency_id', { index: 1 }); await p.fill('#f-base_name', 'Base sem JS (fictícia)');
    await p.fill('#f-base_cep', '17501-000'); await p.fill('#f-base_street', 'Avenida Sampaio Vidal');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]:has-text("Cadastrar agente")')]);
    // Servidor de verdade: localiza pelo endereço ao salvar. Sem saída para a internet (como aqui), explica e mantém o que foi digitado.
    const created2 = /\/agentes\/\d+$/.test(p.url());
    const why = created2 ? '' : await p.locator('.error-summary').innerText().catch(() => '');
    check('sem_js_salva_pelo_endereco_ou_explica', created2 || (/Ponto da base: A localização pelo endereço não respondeu agora/.test(why) && (await p.inputValue('#f-base_street')) === 'Avenida Sampaio Vidal'), { cadastrou: created2, resumo: why });
    await shot(p, '08-sem-js-endereco', true);
    await ctx.close();
  }

  // Movimento reduzido: o campo "Rua" aparece sem animação e o indicador de carregamento não gira.
  { const { ctx, p } = await page(D, { reduced: true });
    await p.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
    await typeCep(p, '17501-000'); await sleep(320);
    const spin = await p.locator('[data-cep-status] .spinner').evaluate((s) => getComputedStyle(s).animationName).catch(() => 'sem indicador');
    await p.waitForSelector('[data-locate-box]', { state: 'visible' }); await sleep(30);
    const op = await p.locator('[data-locate-box]').evaluate((m) => getComputedStyle(m).opacity);
    check('movimento_reduzido', spin === 'none' && op === '1', { spin, op });
    await ctx.close();
  }

  // Celular: sem rolagem lateral; formulário de equipamentos mantém o texto antigo do mapa.
  for (const [size, name] of [[{ width: 390, height: 844 }, '06-celular-390'], [{ width: 360, height: 780 }, '07-celular-360']]) {
    const { ctx, p } = await page(size);
    await p.goto(`${BASE}/agentes/novo`, { waitUntil: 'networkidle' });
    await typeCep(p, '17501-000'); await waitStreet(p, 'Avenida Sampaio Vidal');
    check(`sem_rolagem_lateral_${size.width}`, await noHScroll(p));
    await p.locator('.base-address').evaluate((e) => e.scrollIntoView({ block: 'center' })); await shot(p, name);
    await ctx.close();
  }
  { const { ctx, p } = await page(D);
    await p.goto(`${BASE}/equipamentos/novo`, { waitUntil: 'networkidle' });
    check('equipamentos_sem_mudanca', (await p.locator('#picker-status').innerText()) === 'Nenhum local marcado ainda.' && (await p.locator('[data-cep-lookup]').count()) === 0);
    await ctx.close();
  }

  report.criado = created;
  report.resumo = { total: Object.keys(report.verificacoes).length, ok: Object.values(report.verificacoes).filter((v) => v.ok).length, erros_console: report.console.length };
  fs.writeFileSync(`${OUT}/verificacao-navegador.json`, JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report.resumo), Object.entries(report.verificacoes).filter(([, v]) => !v.ok).map(([k, v]) => `${k}: ${JSON.stringify(v.detalhe)}`).join('\n'));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
