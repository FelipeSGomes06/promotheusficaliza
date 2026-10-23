import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { movimento } from './movimento.js';

/*
 * Planos 3D entre as cenas de recursos (o desenho fica em planos3d.js). Este módulo decide quando o 3D é baixado, qual
 * plano está em cena e quanto do caminho de câmera ele já percorreu.
 *
 * - Cada plano tem lugar próprio no fluxo da página (não cobre texto nem captura e não muda o layout ao carregar) e um
 *   cartaz em CSS que vale sem JavaScript, sem WebGL ou enquanto o 3D não chega.
 * - Há um único WebGLRenderer, separado do da hero. O canvas dele é emprestado ao plano mais visível; o que sai de cena
 *   fica com o último quadro, copiado uma vez para um canvas 2D. Nada é copiado a cada quadro.
 * - Só se desenha com um plano na tela e a aba visível, a até 60 quadros por segundo; se os quadros atrasam, a
 *   densidade de pixels cai um degrau.
 * - A rolagem conduz a câmera (com um pouco de inércia); o tempo move o trânsito. Com movimento reduzido, cada plano
 *   mostra um quadro parado na pose de repouso e nada se mexe.
 */
const FASES = { falha: 'Falha registrada', execucao: 'Intervenção em execução', concluida: 'Concluída · em operação' };

export function iniciarPlanos() {
  const planos = gsap.utils.toArray('[data-plano]').map((el) => ({
    el,
    nome: el.dataset.plano,
    tela: el.querySelector('[data-plano-tela]'),
    estado: el.querySelector('[data-plano-estado]'),
    quadro: null, // canvas 2D com o último quadro, quando o plano sai de cena
    alvo: 0.5,
    p: 0.5,
    visivel: 0,
    fase: null,
  }));
  if (!planos.length || !('WebGLRenderingContext' in window)) return;

  const celular = window.matchMedia('(max-width: 899.98px), (pointer: coarse)').matches;
  let mundo = null;
  let REPOUSO = null;
  let ativo = null;
  let tempo = 7.5; // começa com o trânsito já espalhado pela avenida
  let densidade = Math.min(window.devicePixelRatio || 1, celular ? 1.5 : 2);
  let ultimo = 0;
  let mediaQuadro = 16.7;
  let quadros = 0;
  let sujo = true;
  let perdido = false;

  // Progresso de cada plano: 0 quando o topo dele entra por baixo da janela, 1 quando a base sai por cima.
  for (const plano of planos) {
    ScrollTrigger.create({
      trigger: plano.el, start: 'top bottom', end: 'bottom top',
      onUpdate: (st) => {
        plano.alvo = st.progress;
      },
      onRefresh: (st) => {
        plano.alvo = st.progress;
      },
    });
  }

  const visibilidade = new IntersectionObserver((entradas) => {
    for (const e of entradas) {
      const plano = planos.find((p) => p.el === e.target);
      plano.visivel = e.isIntersecting ? e.intersectionRatio : 0;
    }
    escolher();
  }, { threshold: Array.from({ length: 11 }, (_, i) => i / 10) });
  planos.forEach((p) => visibilidade.observe(p.el));

  function escolher() {
    if (!mundo || perdido) return;
    const melhor = planos.reduce((a, b) => (b.visivel > a.visivel ? b : a));
    ativar(melhor.visivel > 0 ? melhor : null);
  }

  function dimensionar() {
    const { width, height } = ativo.tela.getBoundingClientRect();
    if (width && height) mundo.tamanho(width, height, densidade);
  }

  function desenhar(plano) {
    const parado = movimento.reduzido();
    const info = mundo.quadro(plano.nome, { p: plano.p, t: tempo, parado });
    if (plano.estado && info?.fase && info.fase !== plano.fase) {
      plano.fase = info.fase;
      plano.estado.textContent = FASES[info.fase];
      plano.estado.dataset.fase = info.fase;
    }
  }

  /** Guarda o quadro atual do plano num canvas 2D (uma cópia, logo depois de desenhar, na mesma tarefa). */
  function congelar(plano) {
    desenhar(plano);
    if (!plano.quadro) {
      plano.quadro = document.createElement('canvas');
      plano.quadro.className = 'plano__quadro';
      plano.quadro.setAttribute('aria-hidden', 'true');
      plano.tela.prepend(plano.quadro);
    }
    const origem = mundo.canvas;
    plano.quadro.width = origem.width;
    plano.quadro.height = origem.height;
    plano.quadro.getContext('2d').drawImage(origem, 0, 0);
    plano.el.classList.add('is-3d');
  }

  function ativar(plano) {
    if (plano === ativo) return;
    if (ativo) congelar(ativo);
    ativo = plano;
    if (!plano) {
      mundo.canvas.remove();
      return;
    }
    plano.p = movimento.reduzido() ? REPOUSO[plano.nome] : plano.alvo;
    plano.tela.append(mundo.canvas);
    plano.el.classList.add('is-3d');
    dimensionar();
    sujo = true;
  }

  function tique(_, dt) {
    if (!ativo || perdido || document.hidden) return;
    const parado = movimento.reduzido();
    const alvo = parado ? REPOUSO[ativo.nome] : ativo.alvo;
    const passo = Math.min(dt, 100) / 1000;
    const antes = ativo.p;
    // Inércia da câmera: chega ao ponto da rolagem em ~0,5 s (sem tween a cada evento de rolagem).
    ativo.p = parado ? alvo : ativo.p + (alvo - ativo.p) * (1 - Math.exp(-passo * 6));
    if (parado && !sujo && antes === ativo.p) return;
    const agora = performance.now();
    if (!parado && agora - ultimo < 15) return; // até 60 quadros por segundo, mesmo em telas de 120 Hz
    if (!parado) {
      tempo += Math.min(agora - (ultimo || agora), 100) / 1000;
      if (ultimo) mediaQuadro += (agora - ultimo - mediaQuadro) * 0.05;
      // Quadros lentos por um tempo (≈ abaixo de 40 por segundo): baixa a densidade de pixels um degrau.
      if (++quadros > 90 && mediaQuadro > 25 && densidade > 1) {
        densidade = Math.max(1, densidade - 0.25);
        quadros = 0;
        mediaQuadro = 16.7;
        dimensionar();
      }
    }
    ultimo = agora;
    sujo = false;
    desenhar(ativo);
  }

  new ResizeObserver(() => {
    if (!ativo) return;
    dimensionar();
    sujo = true;
  }).observe(planos[0].el.parentElement);
  movimento.aoMudar(() => {
    ultimo = 0;
    sujo = true;
  });
  document.addEventListener('visibilitychange', () => {
    ultimo = 0;
  });

  // O módulo 3D só é pedido quando um plano chega a pouco mais de uma tela de distância, com o navegador ocioso.
  let pedido = false;
  const vigia = new IntersectionObserver((entradas) => {
    if (pedido || !entradas.some((e) => e.isIntersecting)) return;
    pedido = true;
    vigia.disconnect();
    quandoOcioso(carregar);
  }, { rootMargin: '120% 0px' });
  planos.forEach((p) => vigia.observe(p.el));

  async function carregar() {
    let modulo;
    try {
      modulo = await import('./planos3d.js');
    } catch {
      return; // sem o módulo (rede, bloqueio): os cartazes ficam
    }
    const criado = modulo.criarMundo({ celular });
    if (!criado) return;
    REPOUSO = modulo.REPOUSO;
    criado.canvas.classList.add('plano__canvas');
    criado.canvas.addEventListener('webglcontextlost', (evento) => {
      evento.preventDefault();
      perdido = true;
      gsap.ticker.remove(tique);
      criado.canvas.remove();
    });
    await criado.preparar();
    mundo = criado;
    // Um quadro de cada plano já de saída: quem rolar depressa encontra a cena, não o cartaz.
    ativo = null;
    for (const plano of planos) {
      plano.p = movimento.reduzido() ? REPOUSO[plano.nome] : plano.alvo;
      const { width, height } = plano.tela.getBoundingClientRect();
      if (!width || !height) continue;
      mundo.tamanho(width, height, densidade);
      congelar(plano);
    }
    gsap.ticker.add(tique);
    escolher();
  }
}

function quandoOcioso(f) {
  const agendar = () => (window.requestIdleCallback ? requestIdleCallback(f, { timeout: 1500 }) : setTimeout(f, 200));
  if (document.readyState === 'complete') agendar();
  else window.addEventListener('load', agendar, { once: true });
}
