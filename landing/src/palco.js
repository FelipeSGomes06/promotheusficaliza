import { gsap } from 'gsap';
import { movimento } from './movimento.js';

/*
 * Palco da hero: a foto de Marília atrás, a marca na frente. A primeira pintura já mostra a foto e a marca oficial em
 * SVG (vale sem JavaScript e sem WebGL); quando o módulo 3D carrega e a hero está na tela, o SVG dá lugar à marca 3D,
 * que se monta uma vez. Quem não quer a sequência usa "Reduzir movimento" (a marca aparece pronta). Sem WebGL, ou se o
 * módulo falhar, fica o SVG — e a foto continua, porque é HTML comum e não depende do 3D.
 *
 * Ponteiro: este módulo é o único que ouve o mouse. Depois da montagem, a foto se desloca poucos pixels, como camada
 * distante, e a marca gira um pouco (controle.apontar, em cena3d.js). Sem mouse, com movimento reduzido ou fora da
 * hero, tudo fica em repouso e nada roda.
 */
const FOTO = { x: 12, y: 8 }; // deslocamento máximo da foto, em px (a imagem tem 6% de sobra para a borda não aparecer)

export function iniciarPalco(raiz) {
  const host = raiz.querySelector('[data-palco-cena]');
  const estatico = raiz.querySelector('[data-palco-estatico]');
  const foto = raiz.querySelector('[data-palco-foto] img');
  const montagem = raiz.dataset.montagem === '1';
  const comPonteiro = raiz.dataset.ponteiro === '1';
  const mouse = window.matchMedia('(hover: hover) and (pointer: fine)');
  let controle = null;
  let pronta = false; // marca montada (ou 3D indisponível): a partir daí a foto pode responder
  const esperando = [];

  // ---------- Foto e marca acompanhando o mouse ----------
  const fotoX = gsap.quickTo(foto, 'x', { duration: 0.6, ease: 'power3.out' });
  const fotoY = gsap.quickTo(foto, 'y', { duration: 0.6, ease: 'power3.out' });
  const area = raiz.closest('.hero') ?? raiz;
  let caixa = null;
  let ocioso = 0;
  const ativo = () => pronta && comPonteiro && mouse.matches && !movimento.reduzido();
  const repousar = (imediato = false) => {
    clearTimeout(ocioso);
    fotoX(0);
    fotoY(0);
    if (imediato) [fotoX, fotoY].forEach((f) => f.tween.progress(1));
    controle?.repousar();
  };
  const limitar = (v) => Math.max(-1, Math.min(1, v));
  // O retângulo do palco é lido uma vez por entrada e de novo só depois de rolar ou redimensionar.
  const esquecerCaixa = () => {
    caixa = null;
  };
  area.addEventListener('pointermove', (evento) => {
    if (evento.pointerType !== 'mouse' || !ativo()) return;
    caixa ??= raiz.getBoundingClientRect();
    const x = limitar(((evento.clientX - caixa.left) / caixa.width - 0.5) * 2);
    const y = limitar(((evento.clientY - caixa.top) / caixa.height - 0.5) * 2);
    fotoX(-x * FOTO.x);
    fotoY(-y * FOTO.y);
    controle?.apontar(x, y);
    clearTimeout(ocioso);
    ocioso = setTimeout(repousar, 2200);
  }, { passive: true });
  area.addEventListener('pointerleave', () => repousar());
  window.addEventListener('scroll', esquecerCaixa, { passive: true });
  window.addEventListener('resize', esquecerCaixa, { passive: true });

  const aoEstado = (estado) => {
    if (estado === 'pronta') {
      pronta = true;
      controle?.ligarPonteiro();
    }
    if (estado === 'falhou') {
      pronta = true;
      host.querySelector('canvas')?.remove();
      gsap.set(estatico, { opacity: 1 });
    }
  };

  movimento.aoMudar(() => {
    controle?.definirReduzido(movimento.reduzido() || !montagem);
    if (movimento.reduzido()) repousar(true);
  });

  const api = {
    /** Chama f(controle) quando a cena 3D estiver pronta — nunca, se não houver WebGL. */
    quandoPronto: (f) => (controle ? f(controle) : esperando.push(f)),
  };
  // Sem WebGL no navegador, nem baixa o módulo 3D. A criação do contexto (que pode falhar) fica com o Three.js.
  if (!('WebGLRenderingContext' in window)) {
    pronta = true;
    return api;
  }

  // O módulo 3D (three.js) só é baixado quando é preciso: a hero na tela ou, no desktop, um objeto das cenas de
  // recursos chegando perto (os dois usam o mesmo renderer). Entrar direto por #sistema não paga a marca 3D antes da
  // leitura. Mesmo assim, espera a página carregar e o navegador ficar ocioso.
  let pedido = false;
  const carregar = () => {
    if (pedido) return;
    pedido = true;
    vigias.forEach((v) => v.disconnect());
    quandoOcioso(() => import('./cena3d.js').then(({ criarCena }) => {
      controle = criarCena({
        host,
        nome: raiz.querySelector('[data-palco-nome]'),
        sombra: raiz.querySelector('[data-palco-sombra]'),
        celular: window.matchMedia('(max-width: 899.98px), (pointer: coarse)').matches,
        reduzido: movimento.reduzido() || !montagem,
        duracao: Number(raiz.dataset.duracao) || 4,
        ponteiro: comPonteiro,
        aoEstado,
      });
      if (!controle) {
        pronta = true;
        return;
      }
      // Começa quando a hero aparece. Não recomeça com rolagem nem redimensionamento.
      const observador = new IntersectionObserver(([entrada]) => {
        if (!entrada.isIntersecting) return;
        observador.disconnect();
        controle.iniciar(() => gsap.to(estatico, { opacity: 0, duration: movimento.reduzido() ? 0 : 0.3 }));
      });
      observador.observe(raiz);
      esperando.splice(0).forEach((f) => f(controle));
    }).catch(() => {
      // Sem o módulo 3D (rede, bloqueio): a marca em SVG continua no lugar.
      pronta = true;
    }));
  };
  // Conta como "na tela" só o que fica visível por 300 ms (a hero, com ao menos 35% à mostra): passar rolando não baixa
  // o 3D. Objetos escondidos por CSS (celular) nunca cruzam a tela.
  const vigiar = (alvos, rootMargin, minimo = 0) => {
    const visiveis = new Set();
    let espera = 0;
    const v = new IntersectionObserver((entradas) => {
      entradas.forEach((e) => (e.isIntersecting && e.intersectionRatio >= minimo ? visiveis.add(e.target) : visiveis.delete(e.target)));
      clearTimeout(espera);
      if (visiveis.size) espera = setTimeout(carregar, 300);
    }, { rootMargin, threshold: [0, minimo] });
    alvos.forEach((alvo) => v.observe(alvo));
    return v;
  };
  const vigias = [];
  const comecarVigias = () => vigias.push(vigiar([raiz], '0px', 0.35), vigiar(document.querySelectorAll('[data-objeto]'), '300px 0px'));
  // Na entrada por uma âncora abaixo da hero (#sistema, #planejamento…), a página pinta no topo e só depois rola até
  // ela: a vigia começa quando essa rolagem termina.
  const ancora = location.hash.length > 1 && !['#inicio', '#conteudo'].includes(location.hash) ? document.getElementById(location.hash.slice(1)) : null;
  if (ancora) depoisDaRolagemInicial(comecarVigias);
  else comecarVigias();

  return api;
}

function depoisDaRolagemInicial(f) {
  let feito = false;
  const uma = () => {
    if (feito) return;
    feito = true;
    f();
  };
  const esperar = () => {
    window.addEventListener('scrollend', uma, { once: true });
    setTimeout(uma, 1500); // navegador sem "scrollend", ou âncora que já estava no lugar
  };
  if (document.readyState === 'complete') esperar();
  else window.addEventListener('load', esperar, { once: true });
}

function quandoOcioso(f) {
  const agendar = () => (window.requestIdleCallback ? requestIdleCallback(f, { timeout: 1200 }) : setTimeout(f, 200));
  if (document.readyState === 'complete') agendar();
  else window.addEventListener('load', agendar, { once: true });
}
