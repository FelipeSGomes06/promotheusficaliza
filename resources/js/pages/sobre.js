/*
 * Página "Sobre": cartão de autoria. É um link comum para o portal do autor; sem JavaScript ou com movimento
 * reduzido, o clique só navega. Com movimento ativo (padrões das skills oficiais greensock/gsap-skills):
 * - entrada: na primeira vez que o cartão aparece, a foto abre como uma tela de cinema e o nome sobe letra a letra
 *   (SplitText com máscara). Se o cartão já estava visível ao carregar, nada que foi mostrado é escondido;
 * - mouse: a foto inclina em 3D e um brilho segue o cursor (quickTo: uma tween reaproveitada por propriedade);
 * - clique: a foto treme (CustomWiggle) e o cartão se expande até cobrir a tela (clip-path), com a foto e o nome
 *   voando para o centro (Flip) e faixas de cinema; a navegação começa junto com a barra de progresso. Clicar na tela
 *   pula a espera; Esc cancela e devolve o cartão. Ctrl/Cmd/Shift + clique só treme (o navegador abre a nova aba).
 * Voltar pelo histórico (página restaurada do bfcache) devolve a página sem a tela sobreposta.
 */
import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';
import { CustomEase } from 'gsap/CustomEase';
import { CustomWiggle } from 'gsap/CustomWiggle';
import { SplitText } from 'gsap/SplitText';
import { motion } from '../lib/motion.js';
import { announce } from '../lib/toasts.js';
import { el } from '../lib/dom.js';

gsap.registerPlugin(Flip, CustomEase, CustomWiggle, SplitText);
// Tremor que perde força até parar; deslocamento e giro em ritmos diferentes para não parecer mecânico.
CustomWiggle.create('pf-tremor', { wiggles: 9, type: 'easeOut' });
CustomWiggle.create('pf-tremor-giro', { wiggles: 6, type: 'easeOut' });

const FINE_POINTER = window.matchMedia('(hover: hover) and (pointer: fine)');
let run = null; // abertura em andamento: { url, shake, expand, portal, navigating }

export function init() {
  const card = document.querySelector('[data-author-card]');
  if (!card) return;
  const parts = {
    card,
    photo: card.querySelector('[data-author-photo]'),
    img: card.querySelector('[data-author-img]'),
    halo: card.querySelector('[data-author-halo]'),
    rings: card.querySelectorAll('[data-author-ring]'),
    kicker: card.querySelector('[data-author-kicker]'),
    name: card.querySelector('[data-author-name]'),
    url: card.querySelector('[data-author-url]'),
  };
  entrance(parts);
  tilt(parts);
  card.addEventListener('click', (event) => open(event, parts));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && run) cancel(parts);
  });
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) reset(parts);
  });
}

/** Entrada na primeira vez que o cartão aparece. Só prepara (esconde) o que ainda está fora da tela. */
async function entrance(parts) {
  if (motion.reduced()) return;
  await document.fonts.ready; // SplitText mede as letras já com a Overpass
  let reveal = null;
  const io = new IntersectionObserver(([entry]) => {
    if (!reveal) {
      if (entry.isIntersecting) io.disconnect();
      else reveal = revealTimeline(parts);
      return;
    }
    if (entry.intersectionRatio < 0.3) return;
    io.disconnect();
    if (motion.reduced()) reveal.progress(1);
    else reveal.play();
  }, { threshold: 0.3 });
  io.observe(parts.card);
}

/** Foto abrindo do centro como tela de cinema, nome letra a letra e o restante logo atrás. Criada pausada. */
function revealTimeline({ img, halo, kicker, name, url }) {
  const split = SplitText.create(name, { type: 'chars', mask: 'chars', aria: 'none' });
  return gsap.timeline({ paused: true, defaults: { ease: 'expo.out' }, onComplete: () => split.revert() })
    .fromTo(img, { clipPath: 'inset(50% 0% 50% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.1, ease: 'expo.inOut', clearProps: 'clipPath' }, 0)
    .fromTo(img, { scale: 1.3 }, { scale: 1, duration: 1.8, clearProps: 'scale' }, 0)
    .from(halo, { autoAlpha: 0, scale: 0.5, duration: 1.4 }, 0.35)
    .from(kicker, { autoAlpha: 0, y: 10, duration: 0.7 }, 0.6)
    .from(split.chars, { yPercent: 115, duration: 0.9, stagger: 0.035, ease: 'power4.out' }, 0.65)
    .from(url, { autoAlpha: 0, y: 10, duration: 0.7 }, 1);
}

/** Inclinação 3D com o mouse: quickTo reaproveita uma tween por propriedade em vez de criar uma a cada movimento. */
function tilt({ card, photo }) {
  gsap.set(photo, { transformPerspective: 800 });
  const to = (prop) => gsap.quickTo(photo, prop, { duration: 0.6, ease: 'power3' });
  const rx = to('rotationX');
  const ry = to('rotationY');
  const gx = to('--gx');
  const gy = to('--gy');
  card.addEventListener('pointermove', (event) => {
    if (run || event.pointerType !== 'mouse' || !FINE_POINTER.matches || motion.reduced()) return;
    const r = card.getBoundingClientRect();
    const px = (event.clientX - r.left) / r.width;
    const py = (event.clientY - r.top) / r.height;
    ry((px - 0.5) * 16);
    rx((0.5 - py) * 12);
    gx(px * 100);
    gy(py * 100);
  });
  card.addEventListener('pointerleave', () => {
    rx(0);
    ry(0);
  });
}

function open(event, parts) {
  if (motion.reduced()) return; // link comum
  if (run) {
    event.preventDefault(); // já abrindo: um segundo clique não recomeça
    return;
  }
  const shake = tremor(parts);
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return; // nova aba/janela
  event.preventDefault();
  run = { url: parts.card.href, shake, expand: null, portal: null, navigating: false };
  announce(`Abrindo o site de ${parts.name.textContent.trim()}…`);
  // O que não vai junto para a tela cheia sai antes de ser coberto, em vez de sumir de uma vez.
  shake.to([parts.kicker, parts.url], { autoAlpha: 0, y: 6, duration: 0.3, ease: 'power2.in' }, 0.25)
    .call(() => expand(parts), null, 0.55);
}

/** Tremor do clique: a foto chacoalha e perde força, o cartão afunda um instante e dois anéis de radar se abrem. */
function tremor({ card, photo, rings }) {
  return gsap.timeline()
    .fromTo(photo, { x: 0 }, { x: 10, duration: 0.6, ease: 'pf-tremor' }, 0)
    .fromTo(photo, { rotation: 0 }, { rotation: 3.5, duration: 0.6, ease: 'pf-tremor-giro' }, 0)
    .fromTo(card, { scale: 1 }, { scale: 0.975, duration: 0.12, ease: 'power2.out', yoyo: true, repeat: 1 }, 0)
    .fromTo(rings, { scale: 0.9, autoAlpha: 0.9 }, { scale: 1.45, autoAlpha: 0, duration: 0.8, stagger: 0.15, ease: 'power2.out' }, 0);
}

/** Expansão até cobrir a tela: o fundo cresce a partir do cartão (clip-path); foto e nome voam para o centro (Flip). */
async function expand(parts) {
  const { card, photo, img, name } = parts;
  const mine = run;
  const p = portal(img.currentSrc || img.src, name.textContent.trim(), new URL(mine.url).host);
  await p.img.decode().catch(() => {}); // a imagem já está em cache: só evita um quadro vazio
  if (run !== mine) return; // cancelado (ou recomeçado) enquanto isso

  gsap.set(photo, { rotationX: 0, rotationY: 0 });
  const state = Flip.getState([photo, name]);
  const r = card.getBoundingClientRect();
  const vw = document.documentElement.clientWidth;
  const vh = document.documentElement.clientHeight;
  const radius = parseFloat(getComputedStyle(card).borderTopLeftRadius) || 0;
  const from = `inset(${Math.max(0, r.top)}px ${Math.max(0, vw - r.right)}px ${Math.max(0, vh - r.bottom)}px ${Math.max(0, r.left)}px round ${radius}px)`;
  gsap.set(p.root, { clipPath: from }); // antes de entrar na página: nenhum quadro em tela cheia
  document.body.append(p.root);
  p.root.addEventListener('click', go);

  const tl = gsap.timeline({ defaults: { ease: 'expo.inOut' } })
    .fromTo(p.root, { clipPath: from }, { clipPath: 'inset(0px 0px 0px 0px round 0px)', duration: 0.9 }, 0)
    .add(Flip.from(state, { targets: [p.photo, p.name], scale: true, duration: 1, ease: 'expo.inOut' }), 0)
    .fromTo(p.bands, { scaleY: 0 }, { scaleY: 1, duration: 0.9 }, 0.1)
    .fromTo(p.go, { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.6, ease: 'power3.out' }, 0.75)
    .addLabel('navegar', 0.9)
    .call(go, null, 'navegar')
    .fromTo(p.progress, { scaleX: 0 }, { scaleX: 1, duration: 1.1, ease: 'power2.inOut' }, 'navegar');
  Object.assign(run, { expand: tl, portal: p.root });
}

/** Começa a navegação (uma vez). A tela sobreposta fica até o navegador trocar de página. */
function go() {
  if (!run || run.navigating || run.expand?.reversed()) return;
  run.navigating = true;
  window.location.assign(run.url);
}

/** Esc: interrompe a navegação que ainda não terminou e desfaz a expansão, devolvendo o cartão. */
function cancel(parts) {
  if (run.navigating) window.stop();
  run.navigating = false;
  if (run.expand) {
    run.expand.eventCallback('onReverseComplete', () => reset(parts));
    run.expand.timeScale(2.2).reverse();
  } else {
    reset(parts);
  }
  announce('Abertura do site cancelada.');
}

/** Repouso: sem tela sobreposta e com o cartão como antes (também ao voltar pelo histórico). */
function reset({ card, photo, rings, kicker, url }) {
  if (!run) return;
  run.shake.kill();
  run.expand?.kill();
  run.portal?.remove();
  gsap.set(card, { scale: 1 });
  gsap.set(photo, { x: 0, rotation: 0, rotationX: 0, rotationY: 0 });
  gsap.set(rings, { autoAlpha: 0 });
  gsap.to([kicker, url], { autoAlpha: 1, y: 0, duration: motion.d(0.3), ease: 'power2.out' });
  run = null;
}

/** Tela sobreposta (decorativa: o anúncio vai para a região viva). */
function portal(src, name, host) {
  const img = el('img', { src, alt: '', width: 440, height: 550 });
  const photo = el('span', { class: 'author-portal__photo', 'data-flip-id': 'autor-foto' }, img);
  const nameEl = el('p', { class: 'author-portal__name', 'data-flip-id': 'autor-nome', text: name });
  const progress = el('span');
  const goEl = el('p', { class: 'author-portal__go' }, el('span', { text: `Abrindo ${host}` }), el('span', { class: 'author-portal__bar' }, progress));
  const bands = [el('span', { class: 'author-portal__band author-portal__band--top' }), el('span', { class: 'author-portal__band author-portal__band--bottom' })];
  const root = el('div', { class: 'author-portal', 'aria-hidden': 'true' }, ...bands, el('div', { class: 'author-portal__content' }, photo, nameEl, goEl));
  return { root, img, photo, name: nameEl, go: goEl, progress, bands };
}
