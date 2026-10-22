/*
 * Mostra ou oculta um bloco de formulário (melhoria progressiva: sem JavaScript, o bloco fica sempre visível).
 * Oculto, um fieldset fica também desabilitado (não é enviado). Movimento: entrada curta (opacidade + 8 px) só no
 * bloco que aparece; a troca rápida termina sempre na última escolha.
 */
import { gsap } from 'gsap';
import { motion } from './motion.js';

export function reveal(el, on, animate = true) {
  const was = !el.hidden;
  if (was === on) return; // nada muda: uma entrada em andamento continua até o fim
  gsap.killTweensOf(el);
  el.hidden = !on;
  if ('disabled' in el) el.disabled = !on;
  if (!on) {
    gsap.set(el, { clearProps: 'opacity,transform' });
    return;
  }
  if (animate && !was && !motion.reduced()) {
    gsap.fromTo(el, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: motion.d(0.2), ease: 'power3.out', clearProps: 'opacity,transform' });
  }
}
