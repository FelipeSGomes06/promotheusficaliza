// Aba Multas: capturas (JPEG) e verificações de comportamento num banco de demonstração com o cenário de multas
// (demo:seed ou demo:seed-multas). Grava rascunhos e edições de teste: use só em banco de demonstração.
// Uso: node scripts/verificacao-interface/multas.cjs <pasta-de-saida>  → capturas/*.jpg + verificacao-navegador.json
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs');
const { BASE, entrar } = require('./comum.cjs');
const OUT = process.argv[2];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  fs.mkdirSync(`${OUT}/capturas`, { recursive: true });
  const browser = await chromium.launch(process.env.PF_CHROMIUM ? { executablePath: process.env.PF_CHROMIUM } : {});
  const report = { base: BASE, quando: new Date().toISOString(), capturas: [], verificacoes: {}, console: [], recursos: [] };
  const check = (nome, ok, detalhe = null) => { report.verificacoes[nome] = { ok: !!ok, detalhe }; };

  async function page(size, { reduced = false, js = true, motionPref = null } = {}) {
    const ctx = await browser.newContext({ viewport: size, deviceScaleFactor: 1, javaScriptEnabled: js, reducedMotion: reduced ? 'reduce' : 'no-preference' });
    if (motionPref) await ctx.addInitScript((v) => { try { localStorage.setItem('pf-motion', v); } catch { /* sem armazenamento */ } }, motionPref);
    const p = await ctx.newPage();
    await p.route(/tile\.openstreetmap\.org/, (r) => r.abort()); // sem tiles: endereço e coordenadas continuam bastando
    // Recursos que falham de propósito (tiles bloqueados, 422/409 provocados, envio abortado) ficam à parte.
    p.on('console', (m) => { if (['error', 'warning'].includes(m.type())) (/^Failed to load resource/.test(m.text()) ? report.recursos : report.console).push(`${m.type()}: ${m.text()}`); });
    p.on('pageerror', (e) => report.console.push(`pageerror: ${e.message}`));
    await p.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP: ${e.violatedDirective} ${e.blockedURI}`)));
    await entrar(p);
    return { ctx, p };
  }
  const shot = async (p, name, full = false) => { await sleep(400); await p.screenshot({ path: `${OUT}/capturas/${name}.jpg`, type: 'jpeg', quality: 82, fullPage: full }); report.capturas.push(name); };
  const noHScroll = (p) => p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
  const fineId = async (p, text) => {
    await p.goto(`${BASE}/multas?cadastro=todos&q=${encodeURIComponent(text)}`);
    const href = await p.locator('a.fine-row').first().getAttribute('href');
    return href.match(/\/multas\/(\d+)/)[1];
  };
  const D = { width: 1440, height: 900 };
  const M = { width: 390, height: 844 };
  const S = { width: 360, height: 780 };

  // Desktop: lista com filtro, cadastros, erros, fichas.
  let ids = {};
  { const { ctx, p } = await page(D);
    ids = { completo: await fineId(p, 'DEMO-AIT-26-000101'), pagamento: await fineId(p, 'DEMO-AIT-26-000108'), retificado: await fineId(p, 'DEMO-AIT-26-000112'), posterior: await fineId(p, 'DEMO-AIT-26-000107'), semAndamento: await fineId(p, 'DEMO-AIT-26-000103') };
    await p.goto(`${BASE}/multas?origem=RADAR`, { waitUntil: 'networkidle' });
    const kpis = await p.locator('.fine-kpis .kpi__n').allTextContents();
    const listed = await p.locator('a.fine-row').count();
    check('resumos_do_mesmo_filtro', Number(kpis[0]) === listed, { resumos: kpis, linhas: listed });
    await shot(p, '01-lista-filtro-radar');
    await p.goto(`${BASE}/multas/novo`, { waitUntil: 'networkidle' });
    // Tipo escolhido logo depois da origem, durante a entrada do bloco: a entrada termina (opacidade 1), não para no meio.
    await p.check('input[name="source_kind"][value="RADAR"]'); await p.selectOption('#f-infraction_kind', 'VELOCIDADE'); await sleep(500);
    const opacity = await p.locator('[data-source-block="RADAR"]').evaluate((f) => getComputedStyle(f).opacity);
    check('radar_mostra_so_bloco_radar', opacity === '1' && await p.isVisible('[data-source-block="RADAR"]') && !(await p.isVisible('[data-source-block="AGENTE"]')) && await p.isVisible('[data-speed-block]'), { opacity });
    await p.locator('[data-source-block="RADAR"]').scrollIntoViewIfNeeded(); await shot(p, '02-cadastro-radar');
    await p.check('input[name="source_kind"][value="AGENTE"]'); await sleep(300);
    check('agente_desabilita_bloco_radar', await p.isVisible('[data-source-block="AGENTE"]') && await p.locator('[data-source-block="RADAR"]').evaluate((f) => f.disabled && f.hidden));
    await p.locator('[data-source-block="AGENTE"]').scrollIntoViewIfNeeded(); await shot(p, '03-cadastro-agente');
    // Troca rápida: termina na última escolha, sem bloco preso a meio caminho.
    for (const v of ['RADAR', 'AGENTE', 'RADAR', 'AGENTE', 'RADAR']) await p.check(`input[name="source_kind"][value="${v}"]`);
    await sleep(500);
    const radarBlock = await p.locator('[data-source-block="RADAR"]').evaluate((f) => ({ hidden: f.hidden, opacity: getComputedStyle(f).opacity, transform: getComputedStyle(f).transform }));
    check('troca_rapida_termina_na_ultima_escolha', !radarBlock.hidden && radarBlock.opacity === '1' && radarBlock.transform === 'none', radarBlock);
    // Digitar no bloco de radar, ir para agente e voltar: a digitação continua.
    await p.fill('#f-speed_limit_kmh', '50'); await p.check('input[name="source_kind"][value="AGENTE"]'); await p.check('input[name="source_kind"][value="RADAR"]');
    check('valores_preservados_ao_alternar', (await p.inputValue('#f-speed_limit_kmh')) === '50');
    // Campos que só valem numa situação: fora da vista até ela; a descrição vem sugerida e não troca o que foi escrito.
    { const n = await ctx.newPage(); await n.route(/tile\.openstreetmap\.org/, (r) => r.abort()); await n.goto(`${BASE}/multas/novo`, { waitUntil: 'networkidle' });
      const hidden = async (sel) => !(await n.isVisible(sel));
      check('cadastro_novo_recolhe_opcionais', await hidden('#f-ait_missing_reason') && await hidden('#f-renavam_missing_reason') && await hidden('#f-vehicle_color')
        && await hidden('#f-infraction_code') && await hidden('#f-source_note') && await n.isVisible('summary:has-text("Não tem o número do AIT?")'));
      await n.fill('#f-external_ait', 'DEMO-AIT-TESTE');
      check('ait_digitado_esconde_alternativa', await hidden('details:has(#f-ait_missing_reason)'));
      await n.selectOption('#f-infraction_kind', 'AVANCO_SEMAFORO'); await n.fill('#f-street', 'Avenida Sampaio Vidal');
      check('descricao_sugerida_tipo_e_local', (await n.inputValue('#f-description')) === 'Avanço de sinal vermelho na Avenida Sampaio Vidal', await n.inputValue('#f-description'));
      await n.fill('#f-description', 'Descrição escrita à mão'); await n.selectOption('#f-infraction_kind', 'OUTRA');
      check('descricao_escrita_nao_e_trocada', (await n.inputValue('#f-description')) === 'Descrição escrita à mão');
      await n.check('input[name="source_kind"][value="RADAR"]'); await n.selectOption('#f-equipment_id', { index: 1 });
      const radarAgency = await n.locator('#f-equipment_id option:checked').getAttribute('data-agency');
      const otherAgency = await n.locator(`#f-agency_id option:not([value=""]):not([value="${radarAgency}"])`).first().getAttribute('value');
      await n.selectOption('#f-agency_id', radarAgency); await sleep(300); const same = await hidden('#f-source_note');
      await n.selectOption('#f-agency_id', otherAgency); await sleep(300);
      check('observacao_da_origem_so_com_orgaos_diferentes', same && await n.isVisible('#f-source_note'), { radarAgency, otherAgency });
      await n.locator('[data-source-note]').scrollIntoViewIfNeeded(); await shot(n, '03b-cadastro-observacao-da-origem');
      await n.close(); }
    // 422: placa malformada, digitação mantida, foco no resumo, link leva ao campo.
    await p.fill('#f-description', 'Teste de erro de placa'); await p.fill('#f-vehicle_plate', 'XX-9'); await p.fill('#f-vehicle_renavam', '123');
    await Promise.all([p.waitForNavigation(), p.click('.form-actions button[type=submit]')]);
    const focused = await p.evaluate(() => document.activeElement?.hasAttribute('data-error-summary'));
    check('erro_422_foco_no_resumo_e_digitacao', focused && (await p.inputValue('#f-vehicle_plate')) === 'XX-9');
    await shot(p, '04-cadastro-erro-422');
    await p.click('.error-summary a[href="#f-vehicle_plate"]');
    check('resumo_leva_ao_campo', await p.evaluate(() => document.activeElement?.id === 'f-vehicle_plate' || location.hash === '#f-vehicle_plate'));
    // 409: duas abas editando a mesma versão.
    const edit = `${BASE}/multas/${ids.completo}/editar`;
    const other = await ctx.newPage(); await other.goto(edit); await p.goto(edit);
    await other.fill('#f-reason', 'Edição da outra aba (teste)'); await other.fill('#f-notes', `Relato atualizado em outra aba (teste ${Date.now()}).`);
    await Promise.all([other.waitForNavigation(), other.click('.form-actions button[type=submit]')]);
    await p.fill('#f-reason', 'Minha retificação (teste)'); await p.fill('#f-notes', 'Minha versão do relato (teste).');
    await Promise.all([p.waitForNavigation(), p.click('.form-actions button[type=submit]')]);
    check('conflito_409_mantem_digitacao', (await p.isVisible('#conflito')) && (await p.inputValue('#f-notes')) === 'Minha versão do relato (teste).');
    await shot(p, '05-conflito-409');
    await p.goto(`${BASE}/multas/${ids.completo}`, { waitUntil: 'networkidle' }); await shot(p, '06-ficha-completa', true);
    await p.goto(`${BASE}/multas/${ids.pagamento}#andamento-sec`, { waitUntil: 'networkidle' }); await p.locator('#andamento-sec').scrollIntoViewIfNeeded(); await shot(p, '07-pagamento-e-recurso');
    await p.click('#andamento > summary');
    check('situacao_gravada_continua_a_vista', (await p.isVisible('#f-paid_on')) && (await p.inputValue('#f-paid_on')) !== '');
    // Situação sem nada informado: só as três perguntas; "Paga" traz os campos que o servidor passa a exigir.
    await p.goto(`${BASE}/multas/${ids.semAndamento}`, { waitUntil: 'networkidle' }); await p.click('#andamento > summary');
    const shown = async (...sels) => Promise.all(sels.map((s) => p.isVisible(s)));
    check('situacao_so_tres_perguntas', (await shown('#f-process_stage', '#f-payment_status', '#f-contest_status')).every(Boolean)
      && !(await shown('#f-nominal_amount', '#f-autuation_issued_on', '#f-paid_on', '#f-contest_kind', '#f-closed_on', '#f-progress_reason')).some(Boolean));
    await p.selectOption('#f-payment_status', 'PAGAMENTO_INFORMADO'); await p.selectOption('#f-contest_status', 'DEFERIDA');
    check('situacao_paga_e_aceito_mostram_o_exigido', (await shown('#f-paid_on', '#f-paid_amount', '#f-payment_reference', '#f-contest_kind', '#f-contest_decided_on', '#f-contest_result_note', '#f-payment_due_on')).every(Boolean)
      && !(await shown('#f-closed_on', '#f-penalty_issued_on')).some(Boolean));
    await p.locator('#andamento').scrollIntoViewIfNeeded(); await shot(p, '07b-situacao-so-o-que-se-aplica');
    await p.goto(`${BASE}/multas/${ids.retificado}`, { waitUntil: 'networkidle' });
    await p.click('#documentacao-sec summary:has-text("Ver todas as conferências")'); await p.locator('#documentacao-sec').scrollIntoViewIfNeeded();
    check('retificacao_conferencia_antiga_so_no_historico', (await p.locator('.fine-checks li.is-stale').count()) === 1 && (await p.locator('.fine-head .st--DOC_NAO_VERIFICADA').count()) === 1);
    await shot(p, '08-documentacao-apos-retificacao');
    await p.locator('#historico').scrollIntoViewIfNeeded(); await p.click('#historico details summary >> nth=0'); await shot(p, '09-historico-antes-depois');
    await p.goto(`${BASE}/multas/${ids.posterior}#documentacao-sec`, { waitUntil: 'networkidle' }); await p.locator('#documentacao-sec').scrollIntoViewIfNeeded(); await shot(p, '10-ocorrencia-x-posterior');
    // Conferência: a pergunta do lembrete só aparece depois do resultado; "Em dia" vem recolhida com "Não precisa".
    await p.goto(`${BASE}/multas/${ids.pagamento}`, { waitUntil: 'networkidle' });
    if (!(await p.locator('#conferir').evaluate((d) => d.open))) await p.click('#conferir > summary');
    const rev = p.locator('[data-check-review]');
    check('lembrete_oculto_antes_do_resultado', !(await rev.isVisible()));
    check('conferencia_fonte_so_depois_do_resultado', !(await p.isVisible('#f-source_type')) && !(await p.isVisible('#f-pending_items')) && !(await p.isVisible('#f-checked_at')));
    await p.selectOption('#f-document_status', 'REGULARIDADE_INFORMADA');
    check('lembrete_em_dia_recolhido_nao_precisa', (await rev.isVisible()) && !(await rev.evaluate((d) => d.open)) && (await p.isChecked('input[name="review_choice"][value="NAO"]')));
    check('conferencia_em_dia_mostra_fonte_sem_pendencia', (await p.isVisible('#f-source_type')) && (await p.isVisible('#f-source_reference')) && !(await p.isVisible('#f-pending_items')));
    await rev.scrollIntoViewIfNeeded(); await shot(p, '10b-lembrete-em-dia');
    await p.selectOption('#f-document_status', 'PENDENCIA_INFORMADA');
    check('conferencia_pendencia_mostra_o_que_estava_errado', await p.isVisible('#f-pending_items'));
    check('lembrete_pendencia_em_destaque', (await rev.evaluate((d) => d.open)) && !(await p.locator('input[name="review_choice"]:checked').count()) && !(await p.isVisible('#f-review_on')));
    await p.check('input[name="review_choice"][value="DATA"]');
    check('lembrete_escolher_data_mostra_campo', await p.isVisible('#f-review_on'));
    await shot(p, '10c-lembrete-pendencia-escolher-data');
    // Lembrete vencido na lista e na ficha, com a frase nova.
    await p.goto(`${BASE}/multas?q=DEMO-AIT-26-000106`, { waitUntil: 'networkidle' });
    check('lembrete_vencido_frase_nova', (await p.locator('text=Documentos: precisam ser conferidos de novo').count()) > 0);
    // Zoom de 200% em 1440 px equivale a uma janela de 720 px CSS.
    await p.setViewportSize({ width: 720, height: 450 });
    for (const path of ['/multas', `/multas/${ids.pagamento}`, '/multas/novo']) { await p.goto(`${BASE}${path}`); check(`zoom_200_sem_rolagem_horizontal ${path}`, await noHScroll(p)); }
    // Falha de conexão no envio: sem falso sucesso.
    await p.setViewportSize(D); await p.goto(`${BASE}/multas/novo`);
    await p.route(/\/multas$/, (r) => (r.request().method() === 'POST' ? r.abort() : r.continue()));
    await p.fill('#f-description', 'Envio que vai falhar'); await p.click('.form-actions button[type=submit]').catch(() => {}); await sleep(800);
    check('falha_de_conexao_sem_falso_sucesso', !(await p.locator('text=Rascunho salvo').count()));
    await p.unroute(/\/multas$/);
    // Voltar pelo histórico depois de enviar: botão não fica preso em "Salvando…".
    await p.goto(`${BASE}/multas/novo`); await p.fill('#f-description', 'Rascunho de teste do voltar');
    await Promise.all([p.waitForNavigation(), p.click('.form-actions button[type=submit]')]);
    await p.goBack(); await sleep(400);
    check('voltar_sem_botao_preso', !(await p.locator('.form-actions button[aria-busy="true"]').count()));
    // Teclado: o primeiro Tab leva ao "Pular para o conteúdo"; o menu ativo é Multas.
    await p.goto(`${BASE}/multas`); await p.keyboard.press('Tab');
    check('teclado_skip_link', await p.evaluate(() => document.activeElement?.classList.contains('skip-link')));
    check('menu_ativo_multas', (await p.getAttribute('.rail__link[aria-current="page"]', 'href')).endsWith('/multas'));
    await ctx.close(); }

  // Celular e 360 px.
  for (const [size, tag] of [[M, '390'], [S, '360']]) {
    const { ctx, p } = await page(size);
    await p.goto(`${BASE}/multas`, { waitUntil: 'networkidle' }); check(`sem_rolagem_horizontal_lista_${tag}`, await noHScroll(p)); await shot(p, `11-lista-celular-${tag}`);
    await p.goto(`${BASE}/multas/${ids.pagamento}`, { waitUntil: 'networkidle' }); check(`sem_rolagem_horizontal_ficha_${tag}`, await noHScroll(p)); await shot(p, `12-ficha-celular-${tag}`);
    await p.goto(`${BASE}/multas/novo`, { waitUntil: 'networkidle' }); check(`sem_rolagem_horizontal_cadastro_${tag}`, await noHScroll(p)); await shot(p, `13-cadastro-celular-${tag}`);
    await ctx.close();
  }

  // Movimento reduzido pelo sistema e pela preferência pf-motion: o bloco aparece pronto, sem transição.
  for (const [opts, tag] of [[{ reduced: true }, 'sistema'], [{ motionPref: 'reduced' }, 'pf-motion']]) {
    const { ctx, p } = await page(D, opts);
    await p.goto(`${BASE}/multas/novo`);
    await p.check('input[name="source_kind"][value="AGENTE"]');
    const style = await p.locator('[data-source-block="AGENTE"]').evaluate((f) => ({ opacity: getComputedStyle(f).opacity, inline: f.getAttribute('style') }));
    check(`movimento_reduzido_${tag}`, style.opacity === '1' && !style.inline, style);
    await p.locator('[data-source-block="AGENTE"]').scrollIntoViewIfNeeded(); await shot(p, `14-movimento-reduzido-${tag}`);
    await ctx.close();
  }

  // Sem JavaScript: todos os blocos visíveis, instrução presente e o rascunho é salvo pelo formulário comum.
  { const { ctx, p } = await page(D, { js: false });
    await p.goto(`${BASE}/multas/novo`);
    check('sem_js_blocos_visiveis', await p.isVisible('[data-source-block="RADAR"]') && await p.isVisible('[data-source-block="AGENTE"]') && await p.isVisible('[data-fine-nojs]'));
    await p.fill('#f-description', 'Rascunho salvo sem JavaScript (teste)'); await p.check('input[name="source_kind"][value="AGENTE"]');
    await p.selectOption('#f-agent_id', { index: 1 });
    await Promise.all([p.waitForNavigation(), p.click('.form-actions button[type=submit]')]);
    check('sem_js_rascunho_salvo', /\/multas\/\d+$/.test(p.url()) && (await p.locator('text=Rascunho salvo').count()) > 0, p.url());
    await shot(p, '15-sem-javascript-rascunho');
    await p.goto(`${BASE}/multas/${ids.pagamento}`);
    if (!(await p.locator('#conferir').evaluate((d) => d.open))) await p.click('#conferir > summary');
    check('sem_js_lembrete_completo', await p.isVisible('input[name="review_choice"][value="30"]') && await p.isVisible('#f-review_on') && await p.isVisible('[data-review-nojs]'));
    await p.locator('[data-check-review]').scrollIntoViewIfNeeded(); await shot(p, '16-sem-javascript-lembrete');
    await p.click('#andamento > summary');
    check('sem_js_campos_condicionais_alcancaveis', await p.isVisible('#f-source_type') && await p.isVisible('#f-pending_items') && await p.isVisible('#f-check_reason')
      && await p.isVisible('#f-closed_on') && await p.isVisible('#f-contest_decided_on') && await p.isVisible('#f-progress_reason'));
    await p.goto(`${BASE}/multas/novo`); await p.click('summary:has-text("Não tem o número do AIT?")');
    check('sem_js_alternativa_do_ait_abre', await p.isVisible('#f-ait_missing_reason') && await p.isVisible('#f-source_note'));
    await ctx.close(); }

  await browser.close();
  report.recursos = [...new Set(report.recursos)];
  report.ok = Object.values(report.verificacoes).every((v) => v.ok) && report.console.length === 0;
  fs.writeFileSync(`${OUT}/verificacao-navegador.json`, JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ ok: report.ok, falhas: Object.entries(report.verificacoes).filter(([, v]) => !v.ok), console: report.console }, null, 2));
})();
