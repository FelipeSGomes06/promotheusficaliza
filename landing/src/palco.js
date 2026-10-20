import { gsap } from 'gsap';
import { movimento } from './movimento.js';

/*
 * Palco da hero. A primeira pintura já mostra a marca oficial em SVG (vale sem JavaScript e sem WebGL); quando o
 * módulo 3D carrega e a hero está na tela, o SVG dá lugar à marca 3D, que se monta uma vez. O botão discreto diz
 * "Pular animação" durante a montagem e "Rever montagem" depois. Sem WebGL, ou se o módulo falhar, fica o SVG.
 */
export function iniciarPalco(raiz) {
  const host = raiz.querySelector('[data-palco-cena]');
  const estatico = raiz.querySelector('[data-palco-estatico]');
  const botao = raiz.querySelector('[data-palco-ctl]');
  const rotulo = botao.querySelector('[data-palco-ctl-rotulo]');
  const icone = botao.querySelector('use');
  const montagem = raiz.dataset.montagem === '1';
  let controle = null;
  let estado = 'estatico';
  const esperando = [];

  const mostrarBotao = () => {
    const montando = estado === 'montando';
    botao.hidden = !montagem || movimento.reduzido() || !(montando || estado === 'pronta');
    rotulo.textContent = montando ? 'Pular animação' : 'Rever montagem';
    icone.setAttribute('href', montando ? '#i-pular' : '#i-rever');
  };

  const aoEstado = (novo) => {
    estado = novo;
    if (novo === 'pronta') controle?.ligarPonteiro();
    if (novo === 'falhou') {
      host.querySelector('canvas')?.remove();
      gsap.set(estatico, { opacity: 1 });
    }
    mostrarBotao();
  };

  botao.addEventListener('click', () => (estado === 'montando' ? controle.pular() : controle.rever()));
  movimento.aoMudar(() => {
    controle?.definirReduzido(movimento.reduzido() || !montagem);
    mostrarBotao();
  });

  const api = {
    /** Chama f(controle) quando a cena 3D estiver pronta — nunca, se não houver WebGL. */
    quandoPronto: (f) => (controle ? f(controle) : esperando.push(f)),
  };
  // Sem WebGL no navegador, nem baixa o módulo 3D. A criação do contexto (que pode falhar) fica com o Three.js.
  if (!('WebGLRenderingContext' in window)) return api;

  // A camada 3D entra depois que a página carregou e o navegador ficou ocioso: título, chamadas e rolagem respondem
  // primeiro; até lá (e para sempre, se algo falhar) aparece a marca em SVG.
  quandoOcioso(() => import('./cena3d.js').then(({ criarCena }) => {
    controle = criarCena({
      host,
      nome: raiz.querySelector('[data-palco-nome]'),
      sombra: raiz.querySelector('[data-palco-sombra]'),
      celular: window.matchMedia('(max-width: 899.98px), (pointer: coarse)').matches,
      reduzido: movimento.reduzido() || !montagem,
      duracao: Number(raiz.dataset.duracao) || 4,
      ponteiro: raiz.dataset.ponteiro === '1',
      aoEstado,
    });
    if (!controle) return;
    // Começa quando a hero aparece (quase sempre já na carga). Não recomeça com rolagem nem redimensionamento.
    const observador = new IntersectionObserver(([entrada]) => {
      if (!entrada.isIntersecting) return;
      observador.disconnect();
      controle.iniciar(() => gsap.to(estatico, { opacity: 0, duration: movimento.reduzido() ? 0 : 0.3 }));
    });
    observador.observe(raiz);
    esperando.splice(0).forEach((f) => f(controle));
  }).catch(() => {
    // Sem o módulo 3D (rede, bloqueio): a marca em SVG continua no lugar.
  }));

  return api;
}

function quandoOcioso(f) {
  const agendar = () => (window.requestIdleCallback ? requestIdleCallback(f, { timeout: 1200 }) : setTimeout(f, 200));
  if (document.readyState === 'complete') agendar();
  else window.addEventListener('load', agendar, { once: true });
}
