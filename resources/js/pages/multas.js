/*
 * Multas: melhoria progressiva dos formulários. Sem JavaScript todos os blocos aparecem (com a instrução de preencher
 * só o da origem escolhida) e o servidor ignora ou recusa o bloco que não se aplica. Com JavaScript, o bloco inativo
 * fica oculto e desabilitado (não é enviado), mas o que foi digitado nele continua no lugar se a pessoa voltar atrás.
 * Movimento: entrada curta (opacidade + 8 px) só no bloco que aparece; a troca rápida termina sempre na última escolha.
 */
import { gsap } from 'gsap';
import { motion } from '../lib/motion.js';

function reveal(el, on, animate = true) {
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

function sourceForm(form) {
  const radios = form.querySelectorAll('[data-fine-source]');
  const kind = form.querySelector('[data-fine-kind]');
  const blocks = form.querySelectorAll('[data-source-block]');
  const speed = form.querySelector('[data-speed-block]');
  form.querySelectorAll('[data-fine-nojs]').forEach((n) => { n.hidden = true; });
  const update = (animate = true) => {
    const source = Array.from(radios).find((r) => r.checked)?.value ?? '';
    blocks.forEach((b) => reveal(b, b.dataset.sourceBlock === source, animate));
    if (speed) {
      const on = kind?.value === 'VELOCIDADE';
      reveal(speed, on, animate);
      speed.querySelectorAll('input').forEach((i) => { i.disabled = !on; });
    }
  };
  radios.forEach((r) => r.addEventListener('change', () => update()));
  kind?.addEventListener('change', () => update());
  update(false); // estado inicial sem animação: o servidor já entregou a página pronta

  // Prévia da velocidade considerada com a mesma regra do servidor (FineRules::consideredSpeed), que é quem grava.
  const measured = form.querySelector('#f-speed_measured_kmh');
  const out = form.querySelector('[data-speed-considered]');
  if (measured && out) {
    measured.addEventListener('input', () => {
      const m = Number.parseInt(measured.value, 10);
      out.textContent = Number.isFinite(m) && m > 0 ? `${m <= 107 ? Math.max(0, m - 7) : Math.floor((m * 93 + 50) / 100)} km/h` : 'calculada ao salvar';
    });
  }
}

function checkForm(form) {
  const scope = form.querySelector('[data-check-scope]');
  const effective = form.querySelector('[data-check-effective]');
  if (!scope || !effective) return;
  const update = () => {
    const on = scope.value === 'POSTERIOR';
    reveal(effective, on);
    effective.querySelectorAll('input').forEach((i) => { i.disabled = !on; });
  };
  scope.addEventListener('change', update);
  effective.hidden = scope.value !== 'POSTERIOR';
  effective.querySelectorAll('input').forEach((i) => { i.disabled = effective.hidden; });
}

export function init() {
  document.querySelectorAll('[data-fine-form]').forEach(sourceForm);
  document.querySelectorAll('[data-fine-check]').forEach(checkForm);
}
