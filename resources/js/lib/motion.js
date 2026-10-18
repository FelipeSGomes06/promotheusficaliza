/*
 * Preferência de movimento. Vale, nesta ordem: a escolha salva neste navegador (localStorage "pf-motion":
 * "reduced" ou "full") e a preferência do sistema (prefers-reduced-motion). O atributo html[data-motion] comanda o
 * CSS; motion.reduced() comanda o GSAP e o Leaflet. A escolha nunca é apagada sem a pessoa pedir.
 */
const KEY = 'pf-motion';
const html = document.documentElement;
const media = window.matchMedia('(prefers-reduced-motion: reduce)');
const listeners = new Set();

function read() {
  try { return window.localStorage.getItem(KEY); } catch { return null; }
}
function write(value) {
  try { window.localStorage.setItem(KEY, value); } catch { /* armazenamento indisponível: vale só nesta página */ }
}

const saved = read();
if (saved === 'reduced' || saved === 'full') html.dataset.motion = saved;

const notify = () => listeners.forEach((fn) => fn());
media.addEventListener('change', notify);

export const motion = {
  reduced() {
    const pref = html.dataset.motion;
    return pref === 'reduced' || (pref !== 'full' && media.matches);
  },
  /** De onde vem o estado atual: "escolha" (salva aqui), "sistema" ou "padrão". */
  source() {
    if (html.dataset.motion === 'reduced' || html.dataset.motion === 'full') return 'escolha';
    return media.matches ? 'sistema' : 'padrão';
  },
  systemReduced() {
    return media.matches;
  },
  set(value) {
    html.dataset.motion = value;
    write(value);
    notify();
  },
  onChange(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
  /** Duração em segundos para o GSAP: zero no modo reduzido (o estado final aparece de imediato). */
  d(seconds) {
    return this.reduced() ? 0 : seconds;
  },
};

/** Componente Alpine do botão "Reduzir animações": mostra o estado e a origem, sem esconder a escolha salva. */
export function motionPref() {
  return {
    reduced: motion.reduced(),
    source: motion.source(),
    system: motion.systemReduced(),
    init() {
      this.off = motion.onChange(() => this.sync());
    },
    destroy() {
      this.off?.();
    },
    sync() {
      this.reduced = motion.reduced();
      this.source = motion.source();
      this.system = motion.systemReduced();
    },
    toggle() {
      motion.set(this.reduced ? 'full' : 'reduced');
    },
    get pressed() {
      return this.reduced ? 'true' : 'false';
    },
    get stateLabel() {
      return this.reduced ? 'Animações reduzidas' : 'Animações ativas';
    },
    get whyLabel() {
      if (this.source === 'escolha') {
        return this.reduced ? 'Por sua escolha neste navegador. Toque para ativar.' : `Ativadas por sua escolha${this.system ? ' (o sistema pede menos movimento)' : ''}. Toque para reduzir.`;
      }
      return this.reduced ? 'Pela preferência do sistema. Toque para ativar aqui.' : 'Toque para reduzir.';
    },
  };
}
