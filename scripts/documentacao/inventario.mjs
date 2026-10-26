// Inventário da documentação: lista os símbolos nomeados do código próprio (PHP pelo tokenizer, Python pelo ast,
// JavaScript pelo analisador do Rolldown já instalado com o Vite), gera o catálogo e confere a cobertura.
// Só lê arquivos e escreve docs/referencia/catalogo-simbolos.md (com --catalogo). Não executa o código analisado.
//
//   node scripts/documentacao/inventario.mjs --catalogo    # regrava o catálogo de símbolos
//   node scripts/documentacao/inventario.mjs --verificar   # símbolos, pastas e links; sai com 1 se faltar algo
//
// Requer node, php e python no PATH (os mesmos usados no desenvolvimento). Ver scripts/documentacao/README.md.
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, posix, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseAst } from 'rolldown/parseAst';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const REF = 'docs/referencia/codigo';
const CATALOG = 'docs/referencia/catalogo-simbolos.md';
const args = new Set(process.argv.slice(2));

const git = (...a) => execFileSync('git', a, { cwd: ROOT, encoding: 'utf8', maxBuffer: 64 << 20 });
const files = git('ls-files', '--cached', '--others', '--exclude-standard').split('\n').filter(Boolean);
// Fora do inventário: pasta privada do Claude e registros locais de ferramenta (não são código do projeto).
const own = files.filter((f) => !/^(\.claude|\.playwright-mcp)\//.test(f));
const isCode = (f) => /\.(php|js|mjs|cjs|py)$/.test(f) || f === 'bin/fiscaliza';
// Saídas de build e bibliotecas distribuídas são documentadas pelo processo que as gera, não símbolo a símbolo.
const generated = (f) => /(^|\/)build\//.test(f) || /^public\/assets\/vendor\//.test(f);
const code = own.filter((f) => isCode(f) && !generated(f));

/** @type {Array<{file: string, kind: string, name: string, short: string, signature: string, visibility: string, line: number}>} */
const symbols = [];
const php = code.filter((f) => f.endsWith('.php') || f === 'bin/fiscaliza');
const py = code.filter((f) => f.endsWith('.py'));
const js = code.filter((f) => /\.(js|mjs|cjs)$/.test(f));
const lines = (out) => out.split('\n').filter(Boolean).map((l) => JSON.parse(l));
symbols.push(...lines(execFileSync('php', ['scripts/documentacao/simbolos-php.php', ...php], { cwd: ROOT, encoding: 'utf8', maxBuffer: 64 << 20 })));
symbols.push(...lines(execFileSync('python', ['-I', 'scripts/documentacao/simbolos-py.py', ...py], { cwd: ROOT, encoding: 'utf8', maxBuffer: 64 << 20 })));
for (const f of js) symbols.push(...jsSymbols(f));
// Macros Twig são as "funções" das telas: entram com o caminho do template (ex.: _ui.input, planning/index.place).
const twig = own.filter((f) => f.startsWith('templates/') && f.endsWith('.twig'));
for (const f of twig) {
  const source = readFileSync(join(ROOT, f), 'utf8');
  for (const m of source.matchAll(/\{%-?\s*macro\s+(\w+)\(([^)]*)\)/g)) {
    const name = `${f.slice('templates/'.length, -'.twig'.length)}.${m[1]}`;
    symbols.push({ file: f, kind: 'macro', name, short: m[1], signature: `${m[1]}(${m[2]})`, visibility: 'macro', line: source.slice(0, m.index).split('\n').length });
  }
}

function jsSymbols(file) {
  const source = readFileSync(join(ROOT, file), 'utf8');
  const lineOf = (offset) => source.slice(0, offset).split('\n').length;
  const ast = parseAst(source, { lang: 'js', sourceType: 'unambiguous' }, file);
  const found = [];
  const add = (node, name, kind, exported, params) =>
    found.push({
      file, kind, name, short: name.split('.').pop(), line: lineOf(node.start), visibility: exported ? 'export' : 'local',
      signature: `${name.split('.').pop()}(${(params ?? []).map((p) => source.slice(p.start, p.end)).join(', ')})`,
    });
  const isFn = (n) => n && ['FunctionExpression', 'ArrowFunctionExpression'].includes(n.type);
  const keyName = (k) => (k.type === 'Identifier' ? k.name : k.type === 'Literal' ? String(k.value) : null);
  // Percorre a árvore guardando o "contêiner" nomeado (função, constante ou componente Alpine) para qualificar nomes.
  const visit = (node, scope, exported = false) => {
    if (!node || typeof node.type !== 'string') return;
    switch (node.type) {
      case 'ExportNamedDeclaration':
      case 'ExportDefaultDeclaration':
        if (node.declaration) visit(node.declaration, scope, true);
        return;
      case 'FunctionDeclaration':
        if (node.id) {
          const name = scope ? `${scope}.${node.id.name}` : node.id.name;
          add(node, name, 'function', exported, node.params);
          visit(node.body, name);
          return;
        }
        break;
      case 'VariableDeclarator':
        if (node.id.type === 'Identifier' && isFn(node.init)) {
          const name = scope ? `${scope}.${node.id.name}` : node.id.name;
          add(node, name, 'function', exported, node.init.params);
          visit(node.init.body, name);
          return;
        }
        if (node.id.type === 'Identifier' && node.init?.type === 'ObjectExpression' && node.init.properties.some((p) => isFn(p.value))) {
          const name = scope ? `${scope}.${node.id.name}` : node.id.name;
          found.push({ file, kind: 'object', name, short: node.id.name, line: lineOf(node.start), visibility: exported ? 'export' : 'local', signature: `${node.id.name} = { … }` });
          visit(node.init, name);
          return;
        }
        break;
      case 'VariableDeclaration':
        for (const d of node.declarations) visit(d, scope, exported);
        return;
      case 'Property':
      case 'MethodDefinition': {
        const key = keyName(node.key);
        if (key && isFn(node.value)) {
          const name = scope ? `${scope}.${key}` : key;
          add(node, name, node.type === 'MethodDefinition' ? 'method' : 'method', exported, node.value.params);
          visit(node.value.body, name);
          return;
        }
        break;
      }
      case 'ClassDeclaration':
        if (node.id) {
          add(node, node.id.name, 'class', exported, []);
          visit(node.body, node.id.name);
          return;
        }
        break;
      case 'CallExpression': {
        // Alpine.data('nome', () => ({ … })): os métodos do componente ficam sob o nome registrado.
        const callee = node.callee;
        if (callee.type === 'MemberExpression' && callee.property?.name === 'data' && node.arguments[0]?.type === 'Literal' && isFn(node.arguments[1])) {
          const name = String(node.arguments[0].value);
          found.push({ file, kind: 'component', name, short: name, line: lineOf(node.start), visibility: 'Alpine.data', signature: `Alpine.data('${name}')` });
          visit(node.arguments[1].body, name);
          return;
        }
        break;
      }
    }
    for (const value of Object.values(node)) {
      if (Array.isArray(value)) value.forEach((v) => visit(v, scope));
      else if (value && typeof value === 'object' && typeof value.type === 'string') visit(value, scope);
    }
  };
  ast.body.forEach((n) => visit(n, ''));
  return found;
}

// Página de referência de cada arquivo: declarada no topo da página com <!-- fontes: caminho caminho … -->.
const pages = new Map();
const walkDocs = (dir) =>
  readdirSync(join(ROOT, dir)).flatMap((n) => {
    const p = posix.join(dir, n);
    return statSync(join(ROOT, p)).isDirectory() ? walkDocs(p) : p.endsWith('.md') ? [p] : [];
  });
const refPages = existsSync(join(ROOT, REF)) ? walkDocs(REF) : [];
for (const page of refPages) {
  const m = readFileSync(join(ROOT, page), 'utf8').match(/<!--\s*fontes:([\s\S]*?)-->/);
  for (const src of (m?.[1] ?? '').split(/\s+/).filter(Boolean)) pages.set(src, page);
}

// Um símbolo está documentado quando o nome qualificado aparece na página do arquivo (em título, tabela ou texto).
// Mesma regra de âncora do GitHub: minúsculas, sem pontuação (acentos ficam), espaço vira hífen.
const anchor = (text) => text.trim().toLowerCase().replace(/`/g, '').replace(/[^\p{L}\p{N}\- _]/gu, '').replace(/ /g, '-');
const pageText = new Map();
for (const s of symbols) {
  const page = pages.get(s.file);
  s.page = page ?? null;
  if (!page) { s.documented = false; continue; }
  if (!pageText.has(page)) pageText.set(page, readFileSync(join(ROOT, page), 'utf8'));
  const text = pageText.get(page);
  s.documented = text.includes('`' + s.name + '`') || text.includes(s.name + '(') || new RegExp(`^#+ .*\\b${s.name.replace(/[.$]/g, '\\$&')}\\b`, 'm').test(text);
  // Âncora só para o título do próprio símbolo, entre crases: um nome curto (ex.: `e`) casaria com qualquer palavra.
  const heading = text.split('\n').find((l) => /^#{2,4} /.test(l) && l.includes('`' + s.name + '`'));
  s.anchor = heading ? anchor(heading.replace(/^#+ /, '')) : null;
}

if (args.has('--catalogo')) writeCatalog();
if (args.has('--verificar')) process.exitCode = verify() ? 0 : 1;
if (args.has('--lista')) for (const s of symbols) console.log([s.file, s.kind, s.name, s.visibility, s.signature, s.line, s.documented ? 'ok' : '-'].join('\t'));
if (!args.has('--catalogo') && !args.has('--verificar') && !args.has('--lista')) {
  console.log(JSON.stringify({ arquivos: code.length, simbolos: symbols.length, documentados: symbols.filter((s) => s.documented).length }));
}

function writeCatalog() {
  const byFile = new Map();
  for (const s of symbols) byFile.set(s.file, [...(byFile.get(s.file) ?? []), s]);
  const rel = (to) => posix.relative(posix.dirname(CATALOG), to);
  const esc = (t) => String(t).replace(/\|/g, '\\|').replace(/\n/g, ' ');
  const total = symbols.length;
  const done = symbols.filter((s) => s.documented).length;
  const out = [
    '# Catálogo de símbolos',
    '',
    '<!-- Gerado por scripts/documentacao/inventario.mjs --catalogo. Não edite à mão: rode o comando de novo. -->',
    '',
    'Índice de **todas as classes, funções e métodos nomeados** do código próprio (PHP, JavaScript, Python e scripts),',
    'com a assinatura real e o link para a página de referência que explica o contrato. Funções anônimas não têm',
    'linha própria: são explicadas no fluxo que as contém. Bibliotecas de terceiros e saídas de build ficam fora',
    '(ver [cobertura](../cobertura-documentacao.md#pastas-geradas-de-terceiros-e-excluídas)).',
    '',
    `**${total} símbolos em ${byFile.size} arquivos; ${done} encontrados na página de referência do arquivo` +
      (done === total ? ' (todos).**' : `, ${total - done} pendentes.**`),
    '',
    'Coluna **Ref.**: ✓ = o nome qualificado aparece na página indicada (título, tabela ou texto); ✗ = pendente.',
    'Linha = posição no arquivo quando o catálogo foi gerado.',
    '',
  ];
  const sections = [...byFile.keys()].sort();
  let currentDir = '';
  for (const file of sections) {
    const dir = posix.dirname(file);
    if (dir !== currentDir) {
      out.push(`## ${dir}`, '');
      currentDir = dir;
    }
    const list = byFile.get(file);
    const page = list[0].page;
    out.push(`### ${posix.basename(file)}`, '');
    out.push(`Fonte: [\`${file}\`](${rel(file)}) · Referência: ${page ? `[${posix.basename(page, '.md')}](${rel(page)})` : '**sem página**'}`, '');
    out.push('| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |', '|---|---|---|---|---:|:---:|');
    for (const s of list) {
      const link = s.page ? `[\`${esc(s.name)}\`](${rel(s.page)}${s.anchor ? '#' + s.anchor : ''})` : `\`${esc(s.name)}\``;
      out.push(`| ${link} | ${s.kind} | ${s.visibility}${s.static ? ' static' : ''} | \`${esc(s.signature ?? '')}\` | ${s.line} | ${s.documented ? '✓' : '✗'} |`);
    }
    out.push('');
  }
  writeFileSync(join(ROOT, CATALOG), out.join('\n'));
  console.log(`${CATALOG}: ${total} símbolos, ${done} documentados.`);
}

function verify() {
  let ok = true;
  const missingPage = [...new Set(symbols.filter((s) => !s.page).map((s) => s.file))];
  const missing = symbols.filter((s) => s.page && !s.documented);
  if (missingPage.length) { ok = false; console.log(`Arquivos de código sem página de referência (${missingPage.length}):\n  ` + missingPage.join('\n  ')); }
  if (missing.length) { ok = false; console.log(`Símbolos ausentes da página do arquivo (${missing.length}):\n  ` + missing.map((s) => `${s.file}: ${s.name} → ${s.page}`).join('\n  ')); }

  // Toda pasta com arquivo próprio precisa de README ou de linha na matriz de cobertura.
  const coverage = existsSync(join(ROOT, 'docs/cobertura-documentacao.md')) ? readFileSync(join(ROOT, 'docs/cobertura-documentacao.md'), 'utf8') : '';
  const dirs = [...new Set(own.flatMap((f) => f.split('/').slice(0, -1).map((_, i, a) => a.slice(0, i + 1).join('/'))))].sort();
  const uncovered = dirs.filter((d) => !existsSync(join(ROOT, d, 'README.md')) && !coverage.includes('`' + d + '/`'));
  if (uncovered.length) { ok = false; console.log(`Pastas sem README nem linha na matriz (${uncovered.length}):\n  ` + uncovered.join('\n  ')); }

  // Links relativos e âncoras dos Markdown do projeto (exceto histórico copiado de fora, que não é reescrito).
  const md = own.filter((f) => f.endsWith('.md') && !f.startsWith('docs/planejamento-codex-') && !f.startsWith('ml/marilia-setores/0'));
  const anchorsOf = new Map();
  const anchorsIn = (f) => {
    if (!anchorsOf.has(f)) {
      const seen = new Map();
      const list = readFileSync(join(ROOT, f), 'utf8').replace(/```[\s\S]*?```/g, '').split('\n')
        .filter((l) => /^#{1,6} /.test(l))
        .map((l) => {
          const base = l.replace(/^#+ /, '').trim().toLowerCase().replace(/`/g, '').replace(/[^\p{L}\p{N}\- _]/gu, '').replace(/ /g, '-');
          const n = seen.get(base) ?? 0;
          seen.set(base, n + 1);
          return n ? `${base}-${n}` : base;
        });
      anchorsOf.set(f, new Set(list));
    }
    return anchorsOf.get(f);
  };
  const broken = [];
  for (const f of md) {
    const text = readFileSync(join(ROOT, f), 'utf8').replace(/```[\s\S]*?```/g, '').replace(/`[^`\n]*`/g, '');
    for (const [, target] of text.matchAll(/\]\(([^)\s]+)(?:\s+"[^"]*")?\)/g)) {
      if (/^(https?:|mailto:|#$)/.test(target)) continue;
      const [path, hash] = target.split('#');
      const dest = path === '' ? f : posix.normalize(posix.join(posix.dirname(f), decodeURIComponent(path)));
      if (!existsSync(join(ROOT, dest))) { broken.push(`${f}: ${target} (arquivo inexistente)`); continue; }
      if (hash && dest.endsWith('.md') && !anchorsIn(dest).has(decodeURIComponent(hash).toLowerCase())) broken.push(`${f}: ${target} (âncora inexistente)`);
    }
  }
  if (broken.length) { ok = false; console.log(`Links quebrados (${broken.length}):\n  ` + broken.join('\n  ')); }
  console.log(ok ? `OK: ${symbols.length} símbolos documentados, ${dirs.length} pastas cobertas, links de ${md.length} arquivos conferidos.` : 'Há pendências (acima).');
  return ok;
}
