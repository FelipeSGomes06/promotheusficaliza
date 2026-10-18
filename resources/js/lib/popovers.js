/*
 * Painéis que abrem por cima (<details data-popover>): aviso de demonstração, legenda, menus de filtro.
 * Sem script continuam sendo <details> nativos. Com script: entrada a partir do gatilho (transform-origin no CSS),
 * saída curta, fecham com Esc (o foco volta ao gatilho) ou clique fora. Um só aberto por vez.
 */
import { gsap } from 'gsap';
import { motion } from './motion.js';

const panelOf = (d) => d.querySelector(':scope > :not(summary)');

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
    if (panel) gsap.set(panel, { clearProps: 'all' });
    if (returnFocus) d.querySelector('summary')?.focus();
  };
  if (!panel || motion.reduced()) { done(); return; }
  gsap.killTweensOf(panel);
  gsap.to(panel, { autoAlpha: 0, y: -4, scale: 0.98, duration: 0.12, ease: 'power2.out', onComplete: done });
}

export function initPopovers(root = document) {
  const all = () => Array.from(root.querySelectorAll('details[data-popover]'));
  for (const d of all()) {
    const summary = d.querySelector('summary');
    summary?.addEventListener('click', (e) => {
      if (d.open) {
        e.preventDefault();
        closePopover(d);
      }
    });
    d.addEventListener('toggle', () => {
      if (!d.open) return;
      for (const other of all()) if (other !== d && other.open) closePopover(other);
      animateOpen(d);
    });
  }
  document.addEventListener('click', (e) => {
    for (const d of all()) if (d.open && !d.contains(e.target)) closePopover(d);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    for (const d of all()) {
      if (d.open) {
        e.stopPropagation();
        closePopover(d, true);
      }
    }
  });
}
