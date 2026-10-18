/*
 * Menu principal. Desktop: trilho fixo, sem animação. Celular: gaveta com GSAP (entrada 0,32 s, saída mais
 * rápida), interrompível — abrir/fechar seguidos terminam no último estado pedido. O conteúdo atrás fica inert
 * enquanto a gaveta está aberta; Esc fecha e devolve o foco ao botão.
 */
import { gsap } from 'gsap';
import { motion } from './motion.js';

const DRAWER = '(max-width: 1099px)';

export function nav() {
  let tl = null;
  let mm = null;
  return {
    open: false,
    init() {
      const rail = this.$el;
      const scrim = document.querySelector('[data-rail-scrim]');
      const btn = document.querySelector('[data-menu-btn]');
      const main = document.querySelector('.main');
      this.parts = { rail, scrim, btn, main };
      btn?.addEventListener('click', () => this.toggle());
      scrim?.addEventListener('click', () => this.close(true));
      mm = gsap.matchMedia();
      mm.add(DRAWER, () => {
        tl = gsap.timeline({ paused: true, defaults: { ease: 'power3.out' } })
          .fromTo(scrim, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.22 }, 0)
          .fromTo(rail, { xPercent: -104, autoAlpha: 0 }, { xPercent: 0, autoAlpha: 1, duration: 0.32, ease: 'expo.out' }, 0)
          .fromTo(rail.querySelectorAll('.rail__link'), { x: -14, opacity: 0 }, { x: 0, opacity: 1, duration: 0.24, stagger: 0.03 }, 0.08);
        tl.eventCallback('onReverseComplete', () => { scrim.hidden = true; rail.classList.remove('is-open'); });
        return () => { this.setOpen(false, false, true); tl = null; };
      });
    },
    destroy() {
      mm?.revert();
    },
    toggle() {
      this.setOpen(!this.open, false);
    },
    close(returnFocus = false) {
      this.setOpen(false, returnFocus);
    },
    closeFromKey() {
      if (this.open) this.setOpen(false, true);
    },
    setOpen(open, returnFocus, silent = false) {
      const { rail, btn, main, scrim } = this.parts;
      this.open = open;
      btn?.setAttribute('aria-expanded', String(open));
      btn?.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      if (main) main.inert = open;
      if (!tl) { if (scrim) scrim.hidden = true; return; }
      if (open) {
        rail.classList.add('is-open');
        scrim.hidden = false;
        if (motion.reduced()) tl.progress(1).pause();
        else tl.timeScale(1).play();
        // Foco depois do primeiro quadro: antes disso a gaveta ainda está invisível e não aceita foco.
        gsap.delayedCall(motion.reduced() ? 0 : 0.05, () => { if (this.open) rail.querySelector('.rail__link')?.focus({ preventScroll: true }); });
      } else {
        if (motion.reduced() || silent) { tl.progress(0).pause(); scrim.hidden = true; rail.classList.remove('is-open'); }
        else tl.timeScale(1.6).reverse();
        if (returnFocus) btn?.focus();
      }
    },
  };
}
