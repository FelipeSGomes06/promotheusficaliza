/* Pequenos utilitários de DOM. Texto vindo do servidor sempre entra por textContent (nunca innerHTML). */

export const SVG_NS = 'http://www.w3.org/2000/svg';

export function iconsUrl() {
  return document.body.dataset.icons || '';
}

/** Ícone do sprite local; o id é validado para não montar URL arbitrária. */
export function icon(id, cls = 'icon') {
  const svg = document.createElementNS(SVG_NS, 'svg');
  svg.setAttribute('class', cls);
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('focusable', 'false');
  const use = document.createElementNS(SVG_NS, 'use');
  use.setAttribute('href', `${iconsUrl()}#${/^i-[A-Za-z_-]+$/.test(id) ? id : 'i-pin'}`);
  svg.append(use);
  return svg;
}

/** Cria elemento; filhos string viram nós de texto. */
export function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (v === null || v === undefined || v === false) continue;
    if (k === 'class') node.className = v;
    else if (k === 'text') node.textContent = v;
    else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
    else node.setAttribute(k, v === true ? '' : String(v));
  }
  for (const c of children.flat()) {
    if (c === null || c === undefined || c === false) continue;
    node.append(c instanceof Node ? c : document.createTextNode(String(c)));
  }
  return node;
}

/**
 * Estado "carregando" de um botão: mantém a largura, troca o rótulo por indicador + texto e bloqueia novo clique.
 * Texto solto dentro do botão é embrulhado em <span> na primeira vez (o CSS esconde só elementos).
 */
export function setBusy(btn, busy, label = 'Enviando…') {
  if (!btn) return;
  if (busy) {
    for (const n of [...btn.childNodes]) {
      if (n.nodeType === Node.TEXT_NODE && n.textContent.trim()) {
        const s = document.createElement('span');
        s.textContent = n.textContent;
        n.replaceWith(s);
      }
    }
    let spin = btn.querySelector('.btn__spinner');
    if (!spin) {
      spin = el('span', { class: 'btn__spinner', 'aria-hidden': 'true' }, el('span', { class: 'spinner' }), el('span', { class: 'btn__spinner-text' }));
      btn.append(spin);
    }
    spin.querySelector('.btn__spinner-text').textContent = label;
    btn.setAttribute('aria-busy', 'true');
    btn.setAttribute('aria-disabled', 'true');
    btn.dataset.busyLabel = label;
  } else {
    btn.removeAttribute('aria-busy');
    btn.removeAttribute('aria-disabled');
  }
}
