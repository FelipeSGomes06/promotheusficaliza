// Revisão de lógica e fluxos de 24/10/2026 (R01–R08, ADR-0023), percurso no navegador pelos caminhos reais:
// manutenção em situação que não permite iniciar (R02), ordem antiga atrás de 31 encerradas (R03), lembrete da
// conferência posterior (R04), ação cuja resposta se perde (R05), redefinição de senha com conta de teste (R06),
// 22 missões vigentes (R07), resumo "operacionais · N com sinal" (R08) e retificação que conflita com o andamento (R01).
// Grava ordens, conferências, uma falha simulada, uma conta de teste (desativada ao fim) e encerra missões: use só em
// banco de demonstração, depois de `php scripts/verificacao-interface/preparar-revisao.php <usuario>`.
// Uso: PF_PREPARO=<json do preparo> PF_MULTA_PAGA=<id> PF_MULTA_CONFERIR=<id> node scripts/verificacao-interface/revisao-fluxos.cjs <saida>
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const PREP = JSON.parse(fs.readFileSync(process.env.PF_PREPARO, 'utf8'));
const PAID = process.env.PF_MULTA_PAGA;
const CHECK = process.env.PF_MULTA_CONFERIR;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  fs.mkdirSync(`${OUT}/capturas`, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, quando: new Date().toISOString(), capturas: [], verificacoes: {}, console: [] };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  async function page(size, { reduced = false, login = true } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, reducedMotion: reduced ? 'reduce' : 'no-preference' });
    await ctx.route(/tile\.openstreetmap\.org/, (r) => r.abort());
    const p = await ctx.newPage();
    p.on('console', (m) => { if (m.type() === 'error' && !/^Failed to load resource/.test(m.text())) report.console.push(m.text()); });
    p.on('pageerror', (e) => report.console.push(`pageerror: ${e.message}`));
    p.on('dialog', (d) => d.accept());
    await p.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP: ${e.violatedDirective} ${e.blockedURI}`)));
    if (login) await entrar(p);
    return { ctx, p };
  }
  const shot = async (p, name, full = true) => { await sleep(350); await p.screenshot({ path: `${OUT}/capturas/${name}.jpg`, type: 'jpeg', quality: 78, fullPage: full }); report.capturas.push(name); };
  const noHScroll = (p) => p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
  const flash = (p) => p.locator('[data-toasts], [data-flash-stack], .flash').allTextContents().then((t) => t.join(' | '));
  const submit = (p, selector) => Promise.all([p.waitForNavigation(), p.click(selector)]);
  const text = (p, selector) => p.locator(selector).first().innerText();
  const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo' }).format(new Date());
  const D = { width: 1440, height: 900 };
  const { p } = await page(D);

  // R02 — ordem aberta pela ficha de um equipamento não instalado: agendar pode, iniciar não é oferecido.
  const planned = `${BASE}/equipamentos/${PREP.nao_instalado}`;
  await p.goto(planned);
  await p.click('#nova-ordem summary');
  check('r02_preRequisito_junto_do_formulario', await p.locator('#nova-ordem', { hasText: 'Nesta situação dá para agendar, mas não para iniciar.' }).isVisible());
  await p.fill('#mr', 'Inspeção antes da instalação (verificação)');
  await submit(p, '#nova-ordem button[type=submit]');
  const prox = await text(p, 'section:has(#prox)').catch(() => '');
  check('r02_proxima_acao_continua_instalacao', /Registrar a instalação/.test(prox) && /agendada\. A intervenção só pode começar depois da instalação/.test(prox), prox);
  check('r02_sem_botao_iniciar', await p.locator('#manutencao button', { hasText: 'Iniciar intervenção' }).count() === 0);
  check('r02_impedimento_na_ordem_e_cancelar', await p.locator('#manutencao .order__effect', { hasText: 'Ainda não pode ser iniciada.' }).count() === 1
    && await p.locator('#manutencao summary', { hasText: 'Cancelar esta ordem' }).count() === 1);
  await shot(p, '01-r02-nao-instalado-1440');
  await p.goto(`${BASE}/manutencao`);
  const blocked = p.locator('.order', { hasText: 'Inspeção antes da instalação (verificação)' });
  check('r02_quadro_sem_iniciar_com_link', await blocked.locator('button', { hasText: 'Iniciar intervenção' }).count() === 0
    && await blocked.locator('a', { hasText: 'Abrir a ficha' }).count() === 1);
  await blocked.scrollIntoViewIfNeeded();
  await shot(p, '02-r02-quadro-1440', false);

  // R03 — a pendência antiga aparece e pode ser cancelada; a fila leva direto a ela; o limite do histórico é explícito.
  const old = PREP.ordem_antiga;
  await p.goto(`${BASE}/manutencao`);
  await Promise.all([p.waitForNavigation(), p.click(`a[href$="/equipamentos/${PREP.historico}#ordem-${old}"]`)]);
  check('r03_fila_leva_a_ordem', p.url().endsWith(`#ordem-${old}`) && await p.locator(`#ordem-${old}`).isVisible());
  check('r03_proxima_acao_ve_a_antiga', new RegExp(`ordem #${old}`).test(await text(p, 'section:has(#prox)')));
  await p.click('#manutencao summary:has-text("Ver ordens encerradas")');
  check('r03_limite_explicito', await p.locator('#manutencao', { hasText: 'Mostrando as 30 encerradas mais recentes de 31.' }).isVisible());
  await p.click('#mais-acoes summary').catch(() => {});
  check('r03_arquivar_pede_cancelar_a_antiga', await p.locator('form[action$="/arquivar"]', { hasText: `#${old}` }).count() === 1);
  await shot(p, '03-r03-pendencia-antiga-1440');
  await p.click(`#ordem-${old} summary`);
  await p.fill(`#cr-${old}`, 'Revisão antiga substituída (verificação).');
  await submit(p, `#ordem-${old} form[action$="/cancelar"] button`);
  check('r03_cancelada_pela_ficha', /cancelad/i.test(await flash(p)) && await p.locator(`#ordem-${old}`, { hasText: 'Cancelada' }).count() === 1, await flash(p));

  // R07 — 22 vigentes: todas com concluir/cancelar; a ficha do agente leva à missão dele.
  const agents = Object.keys(PREP.missoes);
  await p.goto(`${BASE}/planejar`);
  const forms = await p.locator('form[action*="/planejar/missoes/"][action$="/concluir"]').count();
  check('r07_todas_as_vigentes_com_acao', forms >= agents.length, forms);
  const firstAgent = agents[0];
  const firstMission = PREP.missoes[firstAgent];
  await p.goto(`${BASE}/agentes/${firstAgent}`);
  await Promise.all([p.waitForNavigation(), p.click(`a[href$="/planejar#missao-${firstMission}"] >> nth=0`)]);
  check('r07_ficha_do_agente_leva_a_missao', p.url().endsWith(`#missao-${firstMission}`) && await p.locator(`#missao-${firstMission}`).isVisible());
  await shot(p, '04-r07-missao-pela-ficha-1440', false);
  await p.fill(`#motivo-concluir-${firstMission}`, 'Ronda feita (verificação).');
  await submit(p, `#missao-${firstMission} form[action$="/concluir"] button`);
  check('r07_concluida', await p.locator(`#missao-${firstMission}`, { hasText: 'Ronda feita (verificação).' }).count() === 1, await flash(p));

  // R08 — resumo da central diz "operacionais" e, junto, quantos têm sinal recente.
  await p.goto(`${BASE}/`);
  await p.waitForSelector('[data-kpi="operational_confirmed"]', { state: 'attached' });
  const kpi = await text(p, '.listpanel__kpis .kpi');
  check('r08_resumo_qualificado', /operacionais/.test(kpi) && /\d+ com sinal/.test(kpi) && !/funcionando/.test(kpi), kpi);
  await shot(p, '05-r08-central-1440', false);

  // R05 — a ação chega ao servidor, mas a resposta se perde: aviso honesto e reconferência do estado.
  await p.goto(`${BASE}/?selecionar=EQUIPAMENTO:${PREP.historico}`);
  await p.waitForSelector('.detail button:has-text("Simular falha")', { timeout: 8000 });
  let processed = false;
  await p.route(/\/simular-falha$/, async (route) => { await route.fetch(); processed = true; await route.abort('connectionreset'); });
  await p.click('.detail button:has-text("Simular falha")');
  await p.waitForSelector('.toast:has-text("resultado não confirmado"), .toast:has-text("confira o resultado")', { timeout: 8000 });
  const toast1 = await p.locator('.toast').allInnerTexts();
  check('r05_nao_afirma_que_nada_mudou', processed && !/nada foi registrado|não mudou/i.test(toast1.join(' ')) && /Falha simulada/.test(toast1.join(' ')), toast1);
  await p.waitForSelector('.toast:has-text("confira o resultado")', { timeout: 10000 });
  const toast2 = await p.locator('.toast').allInnerTexts();
  check('r05_reconfere_e_orienta', /veja na ficha se ela aparece antes de tentar novamente/.test(toast2.join(' ')), toast2);
  const status = await p.evaluate(async ([url, id]) => { const snap = await (await fetch(url, { headers: { Accept: 'application/json' } })).json();
    return snap.resources.find((r) => r.kind === 'EQUIPAMENTO' && Number(r.id) === id)?.status ?? null; }, [`${BASE}/api/central`, PREP.historico]).catch((e) => String(e));
  check('r05_servidor_tinha_registrado', status === 'FALHA', status);
  await shot(p, '06-r05-resultado-nao-confirmado-1440', false);
  await p.unroute(/\/simular-falha$/);

  // R04 — conferência "depois da multa" com lembrete para hoje: ficha, lista, filtro e resumo.
  await p.goto(`${BASE}/multas/${CHECK}`);
  if (!(await p.locator('#conferir').evaluate((d) => d.open))) await p.click('#conferir summary');
  await p.selectOption('#f-scope', 'POSTERIOR');
  await p.fill('#f-effective_on', today);
  await p.selectOption('#f-document_status', 'REGULARIDADE_INFORMADA');
  await p.selectOption('#f-source_type', 'CRLV_APRESENTADO');
  await p.fill('#f-source_reference', 'CRLV-e fictício exibido depois (verificação)');
  check('r04_texto_do_lembrete', await p.locator('[data-check-review]', { hasText: 'A partir do dia escolhido' }).count() === 1);
  // "Em dia" vem com a pergunta recolhida e "Não precisa" marcado (multas.js): abrir e escolher a data.
  if (!(await p.locator('[data-check-review]').evaluate((d) => d.open))) await p.click('#lbl-review_choice');
  await p.check('input[name=review_choice][value=DATA]');
  await p.fill('#f-review_on', today);
  await submit(p, 'form[action$="/conferencias"] button[type=submit]');
  const ficha = await p.locator('main').innerText();
  check('r04_ficha_mostra_o_lembrete', /Lembrete em vigor/.test(ficha) && /conferência de depois da multa/.test(ficha) && /precisam ser conferidos de novo/.test(ficha), await flash(p));
  await shot(p, '07-r04-lembrete-posterior-1440');
  await p.goto(`${BASE}/multas`);
  const kpis = await text(p, '.fine-kpis');
  await Promise.all([p.waitForNavigation(), p.click('a:has-text("a conferir")')]);
  const code = `DEMO-MUL-${String(CHECK).padStart(6, '0')}`;
  check('r04_filtro_a_conferir', /conferir=1/.test(p.url()) && await p.locator('.fine-row', { hasText: code }).count() === 1 && await p.isChecked('input[name=conferir]'), kpis);
  await shot(p, '08-r04-lista-a-conferir-1440', false);
  // Nova conferência: "Não precisa" substitui o lembrete; o anterior fica no histórico.
  await p.goto(`${BASE}/multas/${CHECK}`);
  if (!(await p.locator('#conferir').evaluate((d) => d.open))) await p.click('#conferir summary');
  await p.selectOption('#f-scope', 'POSTERIOR');
  await p.fill('#f-effective_on', today);
  await p.selectOption('#f-document_status', 'REGULARIDADE_INFORMADA');
  await p.selectOption('#f-source_type', 'CRLV_APRESENTADO');
  await p.fill('#f-source_reference', 'CRLV-e conferido de novo (verificação)');
  await p.check('input[name=review_choice][value=NAO]');
  await submit(p, 'form[action$="/conferencias"] button[type=submit]');
  check('r04_nova_conferencia_substitui', !/Lembrete em vigor/.test(await p.locator('main').innerText()));

  // R01 — retificação que deixaria o andamento antes da ocorrência: recusada no próprio formulário, digitação mantida.
  await p.goto(`${BASE}/multas/${PAID}/editar`);
  const before = await p.inputValue('#f-occurred_at');
  await p.fill('#f-occurred_at', '2026-09-10T13:30');
  await p.fill('#f-reason', 'Data relida no auto (verificação)');
  await submit(p, 'form[data-fine-form] button[type=submit]');
  const summary = await text(p, '[data-error-summary]').catch(() => '');
  check('r01_recusa_nomeia_as_datas', /aviso de infração enviado em 04\/09\/2026/.test(summary) && /até 04\/09\/2026/.test(summary), summary);
  check('r01_digitacao_mantida', (await p.inputValue('#f-occurred_at')) === '2026-09-10T13:30' && (await p.inputValue('#f-reason')) === 'Data relida no auto (verificação)');
  check('r01_campo_associado_e_caminho', (await p.getAttribute('#f-occurred_at', 'aria-invalid')) === 'true'
    && await p.locator(`a[href$="/multas/${PAID}#andamento"][target=_blank]`).count() === 1);
  await shot(p, '09-r01-retificacao-recusada-1440');
  await p.goto(`${BASE}/multas/${PAID}`);
  check('r01_nada_gravado', !/Data relida no auto/.test(await p.locator('#historico').innerText()), before);

  // R06 — conta de teste: redefinir, sessão aberta encerrada, senha anterior recusada, nova aceita.
  const login = `rev.${Date.now().toString(36)}`;
  await p.goto(`${BASE}/contas`);
  await p.fill('#f-username', login);
  await p.fill('#f-display_name', 'Conta de teste da revisão');
  await submit(p, 'form[action$="/contas"] button[type=submit]');
  const first = await text(p, '.flash code');
  const { p: other } = await page(D, { login: false });
  const signIn = async (pg, password) => { await pg.goto(`${BASE}/entrar`); await pg.fill('#u', login); await pg.fill('#p', password);
    await Promise.all([pg.waitForNavigation(), pg.click('form[action$="/entrar"] button[type=submit]')]); return !new URL(pg.url()).pathname.endsWith('/entrar'); };
  check('r06_login_inicial', await signIn(other, first));
  const row = p.locator('tr', { hasText: login });
  await row.locator('form[action$="/senha"] button').focus();
  check('r06_foco_visivel_no_botao', await p.evaluate(() => getComputedStyle(document.activeElement).outlineStyle !== 'none'));
  await submit(p, `tr:has-text("${login}") form[action$="/senha"] button`);
  const second = await text(p, '.flash code');
  check('r06_senha_nova_mostrada_uma_vez', second !== first && /sessões abertas dessa conta foram encerradas/.test(await flash(p)));
  check('r06_autoria_e_instante', await p.locator('tr', { hasText: login }).locator('.hint', { hasText: 'Senha redefinida em' }).count() === 1);
  await p.evaluate(() => document.querySelectorAll('code').forEach((c) => { c.textContent = '•••••••• (oculta na captura)'; }));
  await shot(p, '10-r06-contas-1440', false);
  await other.goto(`${BASE}/`);
  check('r06_sessao_aberta_encerrada', new URL(other.url()).pathname.endsWith('/entrar') && /foi redefinida pelo administrador/.test(await flash(other)));
  const loginPage = await other.locator('main').innerText();
  check('r06_login_orienta_senha_perdida', /Esqueceu a senha\? Peça ao administrador para redefini-la em Contas/.test(loginPage));
  await shot(other, '11-r06-sessao-encerrada-1440', false);
  check('r06_senha_anterior_recusada', !(await signIn(other, first)));
  check('r06_senha_nova_aceita', await signIn(other, second));
  await p.goto(`${BASE}/contas`);
  await submit(p, `tr:has-text("${login}") form[action$="/situacao"] button`);

  // Celular (390 e 360 px, movimento reduzido): telas afetadas sem rolagem lateral.
  const mobile = {};
  for (const width of [390, 360]) {
    const { p: m, ctx } = await page({ width, height: 844 }, { reduced: true });
    for (const [name, path] of [['nao-instalado', `/equipamentos/${PREP.nao_instalado}`], ['historico', `/equipamentos/${PREP.historico}`], ['manutencao', '/manutencao'],
      ['planejar', '/planejar#missoes'], ['central', '/'], ['multa', `/multas/${CHECK}`], ['multas', '/multas?conferir=1'], ['contas', '/contas']]) {
      await m.goto(`${BASE}${path}`);
      mobile[`${name}-${width}`] = await noHScroll(m);
      if (width === 390 && ['nao-instalado', 'central', 'multa', 'contas'].includes(name)) await shot(m, `12-${name}-390`, name !== 'central');
    }
    await m.goto(`${BASE}/multas/${PAID}/editar`);
    await m.fill('#f-occurred_at', '2026-09-10T13:30');
    await m.fill('#f-reason', 'Data relida (celular)');
    await submit(m, 'form[data-fine-form] button[type=submit]');
    mobile[`retificacao-${width}`] = await noHScroll(m) && (await m.inputValue('#f-reason')) === 'Data relida (celular)';
    if (width === 390) await shot(m, '13-r01-retificacao-390', false);
    await ctx.close();
  }
  check('celular_sem_rolagem_lateral', Object.values(mobile).every(Boolean), mobile);
  check('console_sem_erros_nem_csp', report.console.length === 0, report.console);

  fs.writeFileSync(`${OUT}/verificacao-navegador.json`, JSON.stringify(report, null, 2));
  const failed = Object.entries(report.verificacoes).filter(([, v]) => !v.ok);
  console.log(`${Object.keys(report.verificacoes).length - failed.length} OK, ${failed.length} com falha`);
  failed.forEach(([k, v]) => console.log(`FALHA ${k}: ${JSON.stringify(v.detalhe)}`));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
