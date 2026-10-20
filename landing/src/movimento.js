/*
 * Preferência de movimento, com a mesma regra do sistema (resources/js/lib/motion.js): vale a escolha salva neste
 * navegador (localStorage "pf-motion": "reduced" ou "full", aplicada antes da pintura por boot.js) e, sem escolha,
 * a preferência do sistema operacional. O atributo html[data-motion] comanda o CSS; reduzido() comanda GSAP e 3D.
 */
const CHAVE = 'pf-motion';
const html = document.documentElement;
const sistema = window.matchMedia('(prefers-reduced-motion: reduce)');
const ouvintes = new Set();
const avisar = () => ouvintes.forEach((ouvinte) => ouvinte());

export const movimento = {
  reduzido: () => (html.dataset.motion ? html.dataset.motion === 'reduced' : sistema.matches),
  porEscolha: () => html.dataset.motion === 'reduced' || html.dataset.motion === 'full',
  alternar() {
    const valor = this.reduzido() ? 'full' : 'reduced';
    html.dataset.motion = valor;
    try {
      localStorage.setItem(CHAVE, valor);
    } catch {
      // Armazenamento bloqueado: a escolha vale só até recarregar a página.
    }
    avisar();
  },
  aoMudar: (ouvinte) => ouvintes.add(ouvinte),
};

sistema.addEventListener('change', () => {
  if (!movimento.porEscolha()) avisar();
});
