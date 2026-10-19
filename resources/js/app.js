/*
 * Entrada única dos scripts (módulo ES gerado pelo Vite, servido de 'self': a CSP não muda).
 * Alpine no build CSP (sem eval) cuida do estado; GSAP cuida do movimento coordenado; cada página carrega só o
 * que usa. Sem JavaScript, todas as telas continuam funcionando por links e formulários comuns.
 */
import Alpine from '@alpinejs/csp';
import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';
import { motionPref } from './lib/motion.js';
import { adoptServerFlashes } from './lib/toasts.js';
import { initForms } from './lib/forms.js';
import { initPopovers } from './lib/popovers.js';
import { initPicker } from './lib/picker.js';
import { nav } from './lib/shell.js';

gsap.registerPlugin(Flip);
gsap.defaults({ ease: 'power3.out', duration: 0.25 });

Alpine.data('motionPref', motionPref);
Alpine.data('nav', nav);

const page = document.body.dataset.page;
if (page === 'central') {
  const mod = await import('./pages/central.js');
  mod.register(Alpine);
} else if (page === 'sobre') {
  // Não registra componente no Alpine: carrega em paralelo, sem atrasar o resto da página.
  import('./pages/sobre.js').then((mod) => mod.init());
}

initForms();
initPopovers();
initPicker();
Alpine.start();
adoptServerFlashes();
