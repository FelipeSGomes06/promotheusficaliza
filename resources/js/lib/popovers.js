/*
 * Painéis que abrem por cima (<details data-popover>): aviso de demonstração, legenda, menus de filtro.
 * Sem script continuam sendo <details> nativos. Com script: entrada a partir do gatilho (transform-origin no CSS),
 * saída curta, fecham com Esc (o foco volta ao gatilho) ou clique fora. Um só aberto por vez.
 *
 * Ajuda contextual ([data-help], macro ui.help): também abre ao passar o mouse e ao focar pelo teclado. Essa
 * "espiada" fecha ao sair; o clique (ou o toque) fixa a caixinha até Esc, clique fora ou o foco sair dela.
 * A caixinha é a irmã seguinte do <details> (ver ui.help); o conjunto dos dois é a área da ajuda.
 */
import { gsap } from 'gsap';
import { motion } from './motion.js';

const isHelp = (d) => d.hasAttribute('data-help');
const panelOf = (d) => (isHelp(d) ? d.nextElementSibling : d.querySelector(':scope > :not(summary)'));
const areaOf = (d) => (isHelp(d) ? d.parentElement : d);
const GAP = 8;
let returning = false; // foco devolvido pelo Esc: não reabre a ajuda que acabou de fechar

function animateOpen(d) {
  const panel = panelOf(d);
  if (!panel) return;
  gsap.killTweensOf(panel);
  if (motion.reduced()) { gsap.set(panel, { clearProps: 'all' }); return; }
  gsap.fromTo(panel, { autoAlpha: 0, y: -6, scale: 0.97 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.18, ease: 'power3.out', clearProps: 'transform,opacity,visibility' });
}

export function closePopover(d, returnFocus = false) {
  if (!d.open) return;
  const panel = panelOf(d);
  const done = () => {
    d.open = false;
    delete d.dataset.peek;
    if (panel) gsap.set(panel, { clearProps: 'all' });
    if (returnFocus) {
      returning = true;
      d.querySelector('summary')?.focus();
      returning = false;
    }
  };
  if (!panel || motion.reduced()) { done(); return; }
  gsap.killTweensOf(panel);
  gsap.to(panel, { autoAlpha: 0, y: -4, scale: 0.98, duration: 0.12, ease: 'power2.out', onComplete: done });
}

// Caixinha da ajuda presa à janela: abaixo do "?" (acima, se não couber), sem passar das bordas, nem no celular.
function place(d) {
  const box = panelOf(d);
  const btn = d.querySelector('summary');
  if (!box || !btn) return;
  Object.assign(box.style, { position: 'fixed', left: '0px', top: '0px', right: 'auto' });
  const r = btn.getBoundingClientRect();
  const vw = document.documentElement.clientWidth;
  const vh = window.innerHeight;
  const w = box.offsetWidth;
  const h = box.offsetHeight;
  const left = Math.max(GAP, Math.min(r.left - GAP, vw - w - GAP));
  const above = r.bottom + GAP + h > vh - GAP && r.top - GAP - h >= GAP;
  const top = above ? r.top - GAP - h : Math.max(GAP, Math.min(r.bottom + GAP, vh - h - GAP));
  Object.assign(box.style, { left: `${left}px`, top: `${top}px`, transformOrigin: `${r.left + r.width / 2 - left}px ${above ? '100%' : '0'}` });
}

function openHelp(d, peek) {
  if (peek) d.dataset.peek = '1'; else delete d.dataset.peek;
  if (!d.open) d.open = true;
  place(d);
}

function initHelp(d) {
  const summary = d.querySelector('summary');
  const area = areaOf(d);
  let timer = 0;
  const later = (fn, ms) => { clearTimeout(timer); timer = setTimeout(fn, ms); };
  area.addEventListener('pointerenter', (e) => {
    if (e.pointerType === 'mouse') later(() => { if (!d.open) openHelp(d, true); }, 150);
  });
  area.addEventListener('pointerleave', (e) => {
    if (e.pointerType === 'mouse') later(() => { if (d.dataset.peek) closePopover(d); }, 200);
  });
  // Só foco de teclado: o clique do mouse também foca o "?", e esse caso é tratado no clique.
  summary.addEventListener('focus', () => { if (!returning && !d.open && summary.matches(':focus-visible')) openHelp(d, true); });
  area.addEventListener('focusout', (e) => { if (!area.contains(e.relatedTarget)) closePopover(d); });
  summary.addEventListener('click', (e) => {
    e.preventDefault();
    clearTimeout(timer);
    if (d.open && !d.dataset.peek) closePopover(d);
    else openHelp(d, false);
  });
}

export function initPopovers(root = document) {
  const all = () => Array.from(root.querySelectorAll('details[data-popover]'));
  for (const d of all()) {
    const summary = d.querySelector('summary');
    if (isHelp(d)) {
      initHelp(d);
    } else {
      summary?.addEventListener('click', (e) => {
        if (d.open) {
          e.preventDefault();
          closePopover(d);
        }
      });
    }
    d.addEventListener('toggle', () => {
      if (!d.open) return;
      // A ajuda pode estar dentro de outro painel (menu de filtros): o painel de fora continua aberto.
      for (const other of all()) if (other !== d && other.open && !other.contains(d)) closePopover(other);
      animateOpen(d);
      if (isHelp(d)) place(d);
    });
  }
  // Ao rolar, a caixinha acompanha o "?"; se ele sair da tela, ela fecha em vez de ficar presa na borda.
  const reposition = () => {
    for (const d of all()) {
      if (!d.open || !isHelp(d)) continue;
      const r = d.getBoundingClientRect();
      if (r.bottom < 0 || r.top > window.innerHeight) closePopover(d); else place(d);
    }
  };
  window.addEventListener('resize', reposition);
  window.addEventListener('scroll', reposition, { capture: true, passive: true });
  document.addEventListener('click', (e) => {
    for (const d of all()) if (d.open && !areaOf(d).contains(e.target)) closePopover(d);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    for (const d of all()) {
      if (d.open) {
        e.stopPropagation();
        // O foco só volta ao gatilho se estava no painel: Esc numa ajuda aberta pelo mouse não puxa o foco do campo.
        closePopover(d, areaOf(d).contains(document.activeElement));
      }
    }
  });
}
