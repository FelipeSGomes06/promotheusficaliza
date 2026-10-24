// Acompanhamento de alerta de agente, percurso completo: alerta aberto → ciência → responsável recém-cadastrado em Contas →
// providência com próxima conferência → conflito com outra aba → conta desativada → posição simulada → encerramento
// explicado. Também a ficha resumida da central, celular (390/360 px), teclado e movimento reduzido.
// Cadastra uma conta de teste, registra ciência e providências e simula posição: use só em banco de demonstração.
// Uso: PF_AGENTE=<id do agente com alerta SEM_POSICAO> node scripts/verificacao-interface/acompanhamento.cjs <pasta-de-saida>
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const AGENT = process.env.PF_AGENTE;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  fs.mkdirSync(`${OUT}/capturas`, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, agente: AGENT, quando: new Date().toISOString(), capturas: [], verificacoes: {}, console: [] };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  async function page(size, { reduced = false } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, reducedMotion: reduced ? 'reduce' : 'no-preference' });
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    const p = await ctx.newPage();
    p.on('console', (m) => { if (m.type() === 'error' && !/^Failed to load resource/.test(m.text())) report.console.push(m.text()); });
    p.on('pageerror', (e) => report.console.push(`pageerror: ${e.message}`));
    await p.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP: ${e.violatedDirective} ${e.blockedURI}`)));
    await entrar(p);
    return { ctx, p };
  }
  const shot = async (p, name, full = true) => { await sleep(300); await p.screenshot({ path: `${OUT}/capturas/${name}.jpg`, type: 'jpeg', quality: 80, fullPage: full }); report.capturas.push(name); };
  const noHScroll = (p) => p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
  const flash = (p) => p.locator('[data-toasts], [data-flash-stack]').allTextContents().then((t) => t.join(' | '));
  const submit = (p, selector) => Promise.all([p.waitForNavigation(), p.click(selector)]);
  const ficha = `${BASE}/agentes/${AGENT}`;
  const D = { width: 1440, height: 900 };

  const { p } = await page(D);

  // Conta nova cadastrada pela área Contas: deve aparecer na seleção sem mudar código.
  const login = `acomp.${Date.now().toString(36)}`;
  await p.goto(`${BASE}/contas`);
  await p.fill('#f-username', login);
  await p.fill('#f-display_name', 'Operadora do Acompanhamento');
  await submit(p, 'form[action$="/contas"] button[type=submit]');
  await p.goto(ficha);
  check('ficha_proxima_acao_no_topo', /Registrar ciência e conferir/.test(await p.locator('#prox').innerText()));
  check('sem_responsavel_antes_de_atribuir', /Sem responsável/.test(await p.locator('#acompanhamento').innerText()));
  const option = await p.locator('#f-assignee option', { hasText: login }).getAttribute('value');
  check('conta_nova_na_selecao', !!option, option);
  await shot(p, '01-aberto-1440');

  // Ciência pelo teclado: Tab até o botão principal e Enter.
  await p.fill('#f-ack-note', 'Equipe chamada pelo rádio às 12h.');
  await p.focus('#f-ack-note');
  await p.keyboard.press('Tab');
  check('foco_visivel_no_botao_de_ciencia', await p.evaluate(() => document.activeElement?.textContent.includes('Registrar ciência') && getComputedStyle(document.activeElement).outlineStyle !== 'none'));
  await Promise.all([p.waitForNavigation(), p.keyboard.press('Enter')]);
  check('ciencia_registrada_com_autor', /Ciência registrada/.test(await flash(p)) && !/Ainda não registrada/.test(await p.locator('.follow-facts').innerText()), await flash(p));
  check('ciencia_nao_atribui', /Sem responsável/.test(await p.locator('.follow-facts').innerText()));

  // Atribuição à conta nova.
  await p.selectOption('#f-assignee', option);
  await submit(p, 'form.assign-form button[type=submit]');
  check('atribuicao_com_mensagem', /atribuído a Operadora do Acompanhamento/.test(await flash(p)), await flash(p));

  // Providência sem escolher o resultado: 422 com o relato mantido; depois, com próxima conferência.
  await p.fill('#f-note', 'Rádio sem resposta; tentar telefone.');
  await p.evaluate(() => document.querySelector('#f-action').removeAttribute('required'));
  await submit(p, 'form[action$="/providencias"] button[type=submit]');
  check('erro_junto_ao_campo_e_relato_mantido', (await p.inputValue('#f-note')) === 'Rádio sem resposta; tentar telefone.' && await p.locator('#err-action').isVisible());
  await p.selectOption('#f-action', 'CONTATO_TENTADO');
  const later = await p.evaluate(() => { const d = new Date(Date.now() + 45 * 60000); const z = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`; });
  await p.fill('#f-next_check', later);
  await submit(p, 'form[action$="/providencias"] button[type=submit]');
  check('providencia_registrada', /Providência registrada/.test(await flash(p)), await flash(p));
  check('proxima_acao_aguarda_conferencia', /Aguardar a próxima conferência/.test(await p.locator('#prox').innerText()), await p.locator('#prox .next-action__what').innerText());
  await shot(p, '02-acompanhamento-1440');

  // Outra aba com o formulário aberto antes de uma reatribuição: 409 e digitação preservada.
  const { p: other } = await page(D);
  await other.goto(ficha);
  await p.selectOption('#f-assignee', await p.evaluate(() => [...document.querySelectorAll('#f-assignee option')].find((o) => o.textContent.includes('· você')).value));
  await submit(p, 'form.assign-form button[type=submit]');
  await other.selectOption('#f-action', 'CONTATO_CONFIRMADO');
  await other.fill('#f-note', 'Contato confirmado pelo telefone.');
  await submit(other, 'form[action$="/providencias"] button[type=submit]');
  check('conflito_preserva_digitacao', /mudou depois que você abriu/.test(await other.locator('[role=alert]').first().innerText()) && (await other.inputValue('#f-note')) === 'Contato confirmado pelo telefone.');
  await shot(other, '03-conflito-1440', false);
  await submit(other, 'form[action$="/providencias"] button[type=submit]');
  check('reenvio_consciente_apos_conflito', /Providência registrada/.test(await flash(other)));
  check('contato_confirmado_nao_encerra', /ainda aguardando posição recente/.test(await other.locator('#prox').innerText()));

  // Conta desativada: vínculo preservado e aviso para reatribuir.
  await p.goto(ficha);
  await p.selectOption('#f-assignee', option);
  await submit(p, 'form.assign-form button[type=submit]');
  await p.goto(`${BASE}/contas`);
  const row = p.locator('tr', { hasText: login });
  await Promise.all([p.waitForNavigation(), row.locator('button', { hasText: 'Desativar' }).click()]);
  await p.goto(ficha);
  check('conta_desativada_pede_reatribuicao', /Conta desativada: reatribua/.test(await p.locator('.follow-facts').innerText()));
  check('conta_desativada_fora_da_selecao', (await p.locator('#f-assignee option', { hasText: login }).count()) === 0);

  // Central: ficha resumida com a mesma próxima ação e o responsável.
  const { p: central } = await page(D);
  await central.goto(`${BASE}/?selecionar=AGENTE:${AGENT}`);
  await central.waitForSelector('.detail .next-action__what:not(:empty)', { timeout: 8000 });
  const panel = await central.locator('.detail').innerText();
  check('central_proxima_acao_coerente', panel.includes(await p.locator('#prox .next-action__what').innerText()));
  check('central_mostra_responsavel_e_providencia', /Responsável/.test(panel) && /Última providência/.test(panel));
  await shot(central, '04-central-1440', false);

  // Celular, com movimento reduzido.
  for (const width of [390, 360]) {
    const { p: m } = await page({ width, height: 844 }, { reduced: true });
    await m.goto(ficha);
    check(`celular_${width}_sem_rolagem_horizontal`, await noHScroll(m));
    await shot(m, `05-ficha-${width}`);
    await m.goto(`${BASE}/?selecionar=AGENTE:${AGENT}`);
    await sleep(1200);
    check(`central_${width}_sem_rolagem_horizontal`, await noHScroll(m));
    await shot(m, `06-central-${width}`, false);
  }

  // Posição simulada: encerra com o motivo explicado.
  await p.goto(ficha);
  await p.click('#mais-acoes summary');
  await submit(p, 'form[action$="/simular-posicao"] button[type=submit]');
  check('posicao_simulada_encerra', /encerrado por posição recente/.test(await flash(p)), await flash(p));
  await p.click('section[aria-labelledby=encerrados] summary');
  check('encerramento_explicado', /Encerrado: posição recente recebida/.test(await p.locator('section[aria-labelledby=encerrados]').innerText()));
  check('historico_identifica_simulacao', /recebimento simulado pela demonstração/.test(await p.locator('section[aria-labelledby=historico]').textContent()));
  await shot(p, '07-encerrado-1440');

  report.ok = Object.values(report.verificacoes).every((v) => v.ok) && report.console.length === 0;
  fs.writeFileSync(`${OUT}/verificacao-navegador.json`, JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ ok: report.ok, falhas: Object.entries(report.verificacoes).filter(([, v]) => !v.ok), console: report.console }, null, 1));
  await browser.close();
})();
