/*
 * Entrada da landing. O conteúdo já está todo no HTML; este módulo só acrescenta comportamento: menu do celular,
 * preferência de movimento, ampliação das capturas, a marca 3D da hero (carregada à parte, sob demanda) e as
 * animações de rolagem.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { iniciarAmpliacao } from './ampliar.js';
import { iniciarNavegacao } from './navegacao.js';
import { iniciarPalco } from './palco.js';
import { animarRolagem } from './rolagem.js';

gsap.registerPlugin(ScrollTrigger);
iniciarNavegacao();
iniciarAmpliacao();
animarRolagem(iniciarPalco(document.querySelector('[data-palco]')));
