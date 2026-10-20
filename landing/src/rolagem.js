import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { movimento } from './movimento.js';

/*
 * Animações ligadas à rolagem (GSAP + ScrollTrigger), refeitas por gsap.matchMedia() quando muda a largura, a
 * preferência do sistema ou a escolha do botão "Reduzir movimento". Com movimento reduzido nada se desloca: só o nó
 * ativo da via muda de cor (CSS) e os objetos 3D ficam assentados. A rolagem continua nativa (sem pin nem smooth).
 */
export function animarRolagem(palco) {
  let mm = null;
  let objetos = [];

  const montar = () => {
    mm?.revert();
    mm = gsap.matchMedia();
    mm.add({ largo: '(min-width: 900px)', sistemaReduz: '(prefers-reduced-motion: reduce)' }, (contexto) => {
      const { largo } = contexto.conditions;
      const cenas = gsap.utils.toArray('[data-cena]');
      cenas.forEach((cena) => ScrollTrigger.create({ trigger: cena, start: 'top 65%', end: 'bottom 35%', toggleClass: 'is-ativa' }));

      if (movimento.reduzido()) {
        objetos.forEach((o) => o.pose(1));
        return undefined;
      }

      // Captura principal: entra inclinada em perspectiva e assenta para a leitura.
      gsap.fromTo('[data-vitrine-quadro]',
        { rotationX: largo ? 14 : 7, y: largo ? 70 : 30, scale: 0.95, opacity: 0.4 },
        { rotationX: 0, y: 0, scale: 1, opacity: 1, ease: 'none', scrollTrigger: { trigger: '[data-vitrine]', start: 'top 95%', end: 'top 35%', scrub: 0.6 } });
      gsap.from('.vitrine__ponto', { scale: 0, duration: 0.45, ease: 'back.out(2.2)', stagger: 0.09, scrollTrigger: { trigger: '[data-vitrine]', start: 'top 40%', once: true } });
      gsap.from('[data-vitrine-legenda] li', { y: 16, opacity: 0, duration: 0.6, ease: 'power3.out', stagger: 0.07, scrollTrigger: { trigger: '[data-vitrine-legenda]', start: 'top 90%', once: true } });

      // A faixa central da via se desenha conforme a rolagem; cada cena entra quando chega a ela.
      gsap.fromTo('[data-via-faixa]', { clipPath: 'inset(0% 0% 100% 0%)' },
        { clipPath: 'inset(0% 0% 0% 0%)', ease: 'none', scrollTrigger: { trigger: '[data-via]', start: 'top 75%', end: 'bottom 70%', scrub: true } });
      cenas.forEach((cena) => {
        const inversa = cena.classList.contains('cena--inversa');
        gsap.from(cena.querySelectorAll('.cena__texto > *'), { y: 26, opacity: 0, duration: 0.7, ease: 'power3.out', stagger: 0.06, scrollTrigger: { trigger: cena, start: 'top 80%', once: true } });
        gsap.from(cena.querySelector('.cena__tela'), { y: 50, rotation: inversa ? -1.2 : 1.2, opacity: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: cena, start: 'top 80%', once: true } });
      });

      // Objetos 3D: no desktop, giram até a pose de leitura conforme a cena chega; no celular, ficam assentados.
      objetos.forEach((o) => {
        if (!largo) return o.pose(1);
        const estado = { t: 0 };
        return gsap.fromTo(estado, { t: 0 }, { t: 1, ease: 'none', onUpdate: () => o.pose(estado.t), scrollTrigger: { trigger: o.cena, start: 'top 95%', end: 'center 55%', scrub: 0.8 } });
      });

      // Placa final: sobe do poste e endireita.
      gsap.fromTo('.placa-final__face', { rotationX: 9, y: 46, transformPerspective: 1400 },
        { rotationX: 0, y: 0, ease: 'none', scrollTrigger: { trigger: '[data-placa]', start: 'top 95%', end: 'top 45%', scrub: 0.6 } });

      return () => objetos.forEach((o) => o.pose(1));
    });
  };

  palco.quandoPronto((controle) => {
    objetos = gsap.utils.toArray('[data-objeto]').map((el) => ({
      cena: el.closest('[data-cena]'),
      ...controle.objeto(el, el.dataset.objeto, el.closest('.cena--inversa') ? -1 : 1),
    }));
    montar();
  });
  montar();
  movimento.aoMudar(montar);
  document.fonts?.ready.then(() => ScrollTrigger.refresh());
}
