import { gsap } from 'gsap';
import { movimento } from './movimento.js';

/*
 * Enfeites 3D junto das capturas (o desenho fica em enfeites3d.js). Este módulo decide quando o 3D é baixado, quais
 * enfeites estão na tela e o estado de cada um:
 *
 * - entrada: anima de 0 a 1 uma vez, quando o enfeite aparece pela primeira vez (metade dele visível);
 * - giro: onde ele está na janela (−1 em cima, 1 embaixo), para girar de leve com a rolagem;
 * - x, y: o ponteiro em relação ao enfeite (só mouse); a câmera de monitoramento olha para ele quando chega perto.
 *
 * Só desenha os enfeites visíveis, a até 60 quadros por segundo (30 se os quadros atrasam). Com movimento reduzido,
 * cada enfeite aparece pronto e parado, e só é redesenhado quando muda de tamanho. Sem WebGL, sem o módulo ou com o
 * contexto perdido, os enfeites simplesmente não aparecem (são decoração, `aria-hidden`; o CSS os esconde até o
 * primeiro quadro e abaixo de 900 px).
 */
export function iniciarEnfeites() {
  const lista = gsap.utils.toArray('[data-enfeite]').map((el) => ({
    el,
    tipo: el.dataset.enfeite,
    canvas: null,
    contexto: null,
    visivel: false,
    pronto: false,
    estado: { entrada: 0, t: 0, giro: 0, x: 0, y: 0, parado: false, ponteiro: false },
    alvo: { x: 0, y: 0, ponteiro: false },
    sujo: true,
  }));
  if (!lista.length || !('WebGLRenderingContext' in window)) return;

  const mouse = window.matchMedia('(hover: hover) and (pointer: fine)');
  let mundo = null;
  let lado = 384;
  let ultimo = 0;
  let intervalo = 15;
  let lentos = 0;
  let perdido = false;
  let ponteiro = null;

  const visibilidade = new IntersectionObserver((entradas) => {
    for (const e of entradas) {
      const item = lista.find((i) => i.el === e.target);
      item.visivel = e.isIntersecting;
      item.sujo = true;
      // A entrada começa com metade do enfeite à vista e roda uma vez.
      if (mundo && e.intersectionRatio >= 0.5 && !item.entrou) entrar(item);
    }
  }, { rootMargin: '80px 0px', threshold: [0, 0.5] });
  lista.forEach((i) => visibilidade.observe(i.el));

  function entrar(item) {
    item.entrou = true;
    if (movimento.reduzido()) {
      item.estado.entrada = 1;
      return;
    }
    gsap.to(item.estado, { entrada: 1, duration: 1.8, ease: 'none' });
  }

  window.addEventListener('pointermove', (evento) => {
    if (evento.pointerType !== 'mouse') return;
    ponteiro = [evento.clientX, evento.clientY];
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => {
    ponteiro = null;
  });

  function tique(_, dt) {
    if (!mundo || perdido || document.hidden) return;
    const parado = movimento.reduzido();
    const agora = performance.now();
    const passo = Math.min(dt, 100) / 1000;
    if (!parado && agora - ultimo < intervalo) return;
    if (!parado && ultimo) {
      // Quadros lentos por um tempo: cai para 30 por segundo.
      lentos = agora - ultimo > 28 ? lentos + 1 : Math.max(0, lentos - 1);
      if (lentos > 60) intervalo = 31;
    }
    ultimo = agora;
    const altura = window.innerHeight;
    for (const item of lista) {
      if (!item.visivel || !item.pronto) continue;
      if (parado && !item.sujo) continue;
      const b = item.el.getBoundingClientRect();
      const e = item.estado;
      e.parado = parado;
      if (parado) {
        e.entrada = 1;
        Object.assign(e, { giro: 0, x: 0, y: 0, ponteiro: false });
      } else {
        e.t += passo;
        e.giro = Math.max(-1, Math.min(1, ((b.top + b.height / 2) / altura) * 2 - 1));
        // Ponteiro relativo ao enfeite: perto (até ~2,5 tamanhos) ele conta; longe, volta ao repouso.
        const perto = ponteiro && mouse.matches
          && Math.hypot(ponteiro[0] - (b.left + b.width / 2), ponteiro[1] - (b.top + b.height / 2)) < b.width * 2.5;
        item.alvo.ponteiro = Boolean(perto);
        item.alvo.x = perto ? Math.max(-1, Math.min(1, (ponteiro[0] - (b.left + b.width / 2)) / (b.width * 1.5))) : 0;
        item.alvo.y = perto ? Math.max(-1, Math.min(1, (ponteiro[1] - (b.top + b.height / 2)) / (b.width * 1.5))) : 0;
        const k = 1 - Math.exp(-passo * 5);
        e.x += (item.alvo.x - e.x) * k;
        e.y += (item.alvo.y - e.y) * k;
        e.ponteiro = item.alvo.ponteiro;
      }
      mundo.desenhar(item.tipo, e, item.contexto);
      item.sujo = false;
      if (!item.el.classList.contains('is-3d')) item.el.classList.add('is-3d');
    }
  }

  new ResizeObserver(() => lista.forEach((i) => {
    i.sujo = true;
  })).observe(document.documentElement);
  movimento.aoMudar(() => lista.forEach((i) => {
    i.sujo = true;
    if (movimento.reduzido()) i.estado.entrada = 1;
  }));

  // O módulo 3D só é pedido com um enfeite a pouco mais de uma tela (e exibido: abaixo de 900 px eles nem aparecem).
  let pedido = false;
  const vigia = new IntersectionObserver((entradas) => {
    if (pedido || !entradas.some((e) => e.isIntersecting)) return;
    pedido = true;
    vigia.disconnect();
    quandoOcioso(carregar);
  }, { rootMargin: '120% 0px' });
  lista.forEach((i) => vigia.observe(i.el));

  async function carregar() {
    let modulo;
    try {
      modulo = await import('./enfeites3d.js');
    } catch {
      return;
    }
    // Um quadro quadrado para todos, na resolução do maior enfeite (até 512 px).
    const maior = Math.max(...lista.map((i) => i.el.getBoundingClientRect().width));
    lado = Math.max(256, Math.min(512, Math.ceil((maior || 192) * Math.min(window.devicePixelRatio || 1, 2))));
    const criado = modulo.criarEnfeites({ lado });
    if (!criado) return;
    criado.aoPerder(() => {
      perdido = true;
      gsap.ticker.remove(tique);
      lista.forEach((i) => i.el.classList.remove('is-3d'));
    });
    for (const item of lista) {
      item.canvas = document.createElement('canvas');
      item.canvas.width = lado;
      item.canvas.height = lado;
      item.contexto = item.canvas.getContext(criado.bitmap ? 'bitmaprenderer' : '2d');
      item.el.append(item.canvas);
      item.pronto = Boolean(item.contexto);
    }
    await criado.preparar();
    mundo = criado;
    for (const item of lista) {
      const b = item.el.getBoundingClientRect();
      if (b.bottom > 0 && b.top < window.innerHeight && b.height && b.top + b.height / 2 < window.innerHeight) entrar(item);
    }
    gsap.ticker.add(tique);
  }
}

function quandoOcioso(f) {
  const agendar = () => (window.requestIdleCallback ? requestIdleCallback(f, { timeout: 1500 }) : setTimeout(f, 200));
  if (document.readyState === 'complete') agendar();
  else window.addEventListener('load', agendar, { once: true });
}
