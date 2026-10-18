// Verificação visual sem dependências: abre o Chrome/Edge headless pelo DevTools Protocol, percorre cenários
// (viewport, movimento reduzido, login de teste, ações), salva capturas PNG e imprime medições e erros de console.
//
// Uso: node scripts/capturar-telas.mjs <cenarios.json> <pasta-de-saida>
//   BROWSER=caminho-do-chrome (opcional) · BASE=http://127.0.0.1:8080/promotheusfiscaliza (opcional)
// Cada cenário: { "nome", "caminho", "largura", "altura", "movel"?, "reduzir"?, "login"?: [usuario, senha],
//   "antes"?: "código JS executado após carregar", "esperar"?: ms, "esperarDepois"?: ms, "inteira"?: true,
//   "medir"?: "expressão JS que devolve um objeto" }
// Use somente contra um ambiente local de demonstração, com conta de teste descartável.
import { spawn } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const [scenariosFile, outDir] = process.argv.slice(2);
if (!scenariosFile || !outDir) {
  console.error('Uso: node scripts/capturar-telas.mjs <cenarios.json> <pasta-de-saida>');
  process.exit(1);
}
const BASE = process.env.BASE || 'http://127.0.0.1:8080/promotheusfiscaliza';
const BROWSER = process.env.BROWSER || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PORT = 9300 + Math.floor(Math.random() * 500);
const scenarios = JSON.parse(readFileSync(scenariosFile, 'utf8'));
mkdirSync(outDir, { recursive: true });

const profile = mkdtempSync(join(tmpdir(), 'capturas-'));
const browser = spawn(BROWSER, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, '--no-first-run', '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function target() {
  for (let i = 0; i < 50; i++) {
    try {
      const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
      const page = list.find((t) => t.type === 'page');
      if (page) return page.webSocketDebuggerUrl;
    } catch { /* navegador ainda iniciando */ }
    await sleep(200);
  }
  throw new Error('Navegador não respondeu.');
}

const ws = new WebSocket(await target());
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let seq = 0;
const pending = new Map();
const listeners = [];
ws.addEventListener('message', (ev) => {
  const msg = JSON.parse(ev.data);
  if (msg.id && pending.has(msg.id)) {
    const { resolve, reject } = pending.get(msg.id);
    pending.delete(msg.id);
    msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
  } else if (msg.method) {
    listeners.forEach((fn) => fn(msg));
  }
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++seq;
  pending.set(id, { resolve, reject });
  ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async (expression) => {
  const r = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
  if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text);
  return r.result.value;
};
let loaded = null;
listeners.push((m) => { if (m.method === 'Page.loadEventFired' && loaded) { loaded(); loaded = null; } });
const navigate = async (url) => {
  const done = new Promise((r) => { loaded = r; });
  await send('Page.navigate', { url });
  await Promise.race([done, sleep(15000)]);
};

const consoleErrors = [];
listeners.push((m) => {
  if (m.method === 'Runtime.exceptionThrown') consoleErrors.push(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text);
  if (m.method === 'Runtime.consoleAPICalled' && ['error', 'warning'].includes(m.params.type)) consoleErrors.push(m.params.args.map((a) => a.value ?? a.description).join(' '));
  if (m.method === 'Log.entryAdded' && ['error', 'warning'].includes(m.params.entry.level)) consoleErrors.push(`${m.params.entry.text} ${m.params.entry.url || ''}`.trim());
});
await send('Page.enable');
await send('Network.enable');
await send('Runtime.enable');
await send('Log.enable');

const report = [];
let loggedAs = null;
for (const s of scenarios) {
  consoleErrors.length = 0;
  await send('Emulation.setDeviceMetricsOverride', { width: s.largura, height: s.altura, deviceScaleFactor: 1, mobile: Boolean(s.movel) });
  await send('Emulation.setTouchEmulationEnabled', { enabled: Boolean(s.movel) });
  await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: s.reduzir ? 'reduce' : 'no-preference' }] });
  const login = s.login ? s.login.join('\u0000') : null;
  if (login !== loggedAs) {
    await send('Network.clearBrowserCookies');
    if (s.login) {
      await navigate(`${BASE}/entrar`);
      await evaluate(`(() => { const f = document.querySelector('form[action$="/entrar"]');
        f.querySelector('[name=username]').value = ${JSON.stringify(s.login[0])};
        f.querySelector('[name=password]').value = ${JSON.stringify(s.login[1])}; f.submit(); })()`);
      await sleep(1500);
    }
    loggedAs = login;
  }
  await navigate(BASE + s.caminho);
  await sleep(s.esperar ?? 1200);
  if (s.antes) { await evaluate(`(async () => { ${s.antes} })()`); await sleep(s.esperarDepois ?? 700); }
  const metrics = s.medir ? await evaluate(`(() => (${s.medir}))()`) : null;
  const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: Boolean(s.inteira) });
  const file = join(outDir, `${s.nome}.png`);
  writeFileSync(file, Buffer.from(shot.data, 'base64'));
  report.push({ cenario: s.nome, arquivo: file, medidas: metrics, console: [...consoleErrors] });
}
console.log(JSON.stringify(report, null, 2));
ws.close();
browser.kill();
