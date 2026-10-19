// Partes comuns dos scripts: o sistema não tem leitura pública (ADR-0014), então páginas e API pedem uma conta.
const BASE = process.env.PF_BASE || 'http://127.0.0.1:8091/promotheusfiscaliza';
const USUARIO = process.env.PF_USUARIO || 'avaliador';
const SENHA = process.env.PF_SENHA || '';

async function entrar(page) {
  await page.goto(`${BASE}/entrar`);
  await page.fill('#u', USUARIO);
  await page.fill('#p', SENHA);
  await Promise.all([page.waitForNavigation(), page.click('form[action$="/entrar"] button[type=submit]')]);
  if (new URL(page.url()).pathname.endsWith('/entrar')) throw new Error('Login recusado: confira PF_USUARIO e PF_SENHA.');
}

// Sessão pronta para um contexto novo (por exemplo, um contexto que grava vídeo e não deve mostrar o login).
async function sessao(browser) {
  const c = await browser.newContext();
  await entrar(await c.newPage());
  const state = await c.storageState();
  await c.close();
  return state;
}

// Instantâneo da central lido com a sessão da página (sem conta, a API responde 401).
const instantaneo = (page) => page.evaluate(async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).json(), `${BASE}/api/central`);

module.exports = { BASE, entrar, sessao, instantaneo };
