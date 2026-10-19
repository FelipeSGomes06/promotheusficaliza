/*
 * Página "Sobre": cartão de autoria. É um link comum para o portal do autor; sem JavaScript ou com movimento
 * reduzido, o clique só navega. No palco: a foto em círculo, o mascote do Claude Code e o símbolo do Codex em CSS 3D
 * (templates/_autoria.twig). Com movimento ativo (padrões das skills oficiais greensock/gsap-skills):
 * - entrada: na primeira vez que o cartão aparece, a foto abre em íris, a nuvem gira até a pose, o mascote cai
 *   quicando e o nome sobe letra a letra (SplitText). Se o cartão já estava visível ao carregar, nada é escondido;
 * - mouse (em qualquer ponto da página, enquanto o cartão está na tela): a foto inclina, o mascote vira o corpo e os
 *   olhos, a nuvem gira na direção do cursor (quickTo: uma tween reaproveitada por propriedade). Com o mouse sobre o
 *   cartão, o CSS faz o mascote sorrir ("> <") e acenar e o cursor do ">_" andar;
 * - clique: a foto treme (CustomWiggle), o mascote pula, a nuvem dá uma volta e o cartão se expande até cobrir a tela
 *   (clip-path), com o palco inteiro e o nome voando para o centro (Flip) e faixas de cinema; a navegação começa junto
 *   com a barra de progresso. Clicar na tela pula a espera; Esc cancela e devolve o cartão. Ctrl/Cmd/Shift + clique
 *   só faz a reação (o navegador abre a nova aba).
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
    stage: card.querySelector('[data-author-stage]'),
    photo: card.querySelector('[data-author-photo]'),
    img: card.querySelector('[data-author-img]'),
    halo: card.querySelector('[data-author-halo]'),
    rings: card.querySelectorAll('[data-author-ring]'),
    mascot: card.querySelector('[data-author-mascot]'),
    mascotRig: card.querySelector('[data-mascot-rig]'),
    mascotHop: card.querySelector('[data-mascot-hop]'),
    mascotShadow: card.querySelector('[data-mascot-shadow]'),
    eyes: card.querySelectorAll('[data-mascot-eye]'),
    codex: card.querySelector('[data-author-codex]'),
    codexRig: card.querySelector('[data-codex-rig]'),
    codexSpin: card.querySelector('[data-codex-spin]'),
    kicker: card.querySelector('[data-author-kicker]'),
    name: card.querySelector('[data-author-name]'),
    url: card.querySelector('[data-author-url]'),
  };
  entrance(parts);
  follow(parts);
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

/** Foto abrindo em íris, nuvem girando até a pose, mascote caindo, nome letra a letra. Criada pausada. */
function revealTimeline({ img, halo, mascot, mascotHop, mascotShadow, codex, codexSpin, kicker, name, url }) {
  const split = SplitText.create(name, { type: 'chars', mask: 'chars', aria: 'none' });
  // Opacidade só nas raízes dos personagens: dentro do 3D ela achataria as faces.
  return gsap.timeline({ paused: true, defaults: { ease: 'expo.out' }, onComplete: () => split.revert() })
    .fromTo(img, { clipPath: 'circle(0% at 50% 45%)' }, { clipPath: 'circle(75% at 50% 45%)', duration: 1.1, ease: 'expo.inOut', clearProps: 'clipPath' }, 0)
    .fromTo(img, { scale: 1.3 }, { scale: 1, duration: 1.8, clearProps: 'scale' }, 0)
    .from(halo, { autoAlpha: 0, scale: 0.5, duration: 1.4 }, 0.35)
    .from(codex, { autoAlpha: 0, duration: 0.3, ease: 'none' }, 0.5)
    .from(codexSpin, { rotationY: -200, scale: 0.2, duration: 1.2, ease: 'back.out(1.6)' }, 0.5)
    .from(mascot, { autoAlpha: 0, duration: 0.2, ease: 'none' }, 0.75)
    .from(mascotHop, { yPercent: -160, duration: 0.9, ease: 'bounce.out' }, 0.75)
    .from(mascotShadow, { scale: 0.2, duration: 0.9, ease: 'bounce.out' }, 0.75)
    .from(kicker, { autoAlpha: 0, y: 10, duration: 0.7 }, 0.6)
    .from(split.chars, { yPercent: 115, duration: 0.9, stagger: 0.035, ease: 'power4.out' }, 0.65)
    .from(url, { autoAlpha: 0, y: 10, duration: 0.7 }, 1);
}

/**
 * Todos olham para o mouse, em qualquer ponto da página: a foto inclina (e o brilho segue), o mascote vira o corpo e
 * os olhos, a nuvem gira. quickTo reaproveita uma tween por propriedade em vez de criar uma a cada movimento.
 */
function follow({ card, stage, photo, mascot, mascotRig, eyes, codex, codexRig }) {
  gsap.set(photo, { transformPerspective: 800 });
  const to = (target, prop, duration) => gsap.quickTo(target, prop, { duration, ease: 'power3' });
  const photoX = to(photo, 'rotationX', 0.6);
  const photoY = to(photo, 'rotationY', 0.6);
  const glareX = to(photo, '--gx', 0.6);
  const glareY = to(photo, '--gy', 0.6);
  const bodyX = to(mascotRig, 'rotationX', 0.8);
  const bodyY = to(mascotRig, 'rotationY', 0.8);
  const eyeX = to(eyes, 'x', 0.3);
  const eyeY = to(eyes, 'y', 0.3);
  const cloudX = to(codexRig, 'rotationX', 0.9);
  const cloudY = to(codexRig, 'rotationY', 0.9);
  const reach = (distance, range) => gsap.utils.clamp(-1, 1, distance / range);
  const fromCenter = (target, event) => {
    const r = target.getBoundingClientRect();
    return [event.clientX - (r.left + r.width / 2), event.clientY - (r.top + r.height / 2)];
  };
  let em = parseFloat(getComputedStyle(stage).fontSize);
  window.addEventListener('resize', () => { em = parseFloat(getComputedStyle(stage).fontSize); });
  let visible = false;
  new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(card);

  window.addEventListener('pointermove', (event) => {
    if (!visible || run || event.pointerType !== 'mouse' || !FINE_POINTER.matches || motion.reduced()) return;
    const r = card.getBoundingClientRect();
    const px = gsap.utils.clamp(0, 1, (event.clientX - r.left) / r.width);
    const py = gsap.utils.clamp(0, 1, (event.clientY - r.top) / r.height);
    photoY((px - 0.5) * 16);
    photoX((0.5 - py) * 12);
    glareX(px * 100);
    glareY(py * 100);
    const [mx, my] = fromCenter(mascot, event);
    bodyY(reach(mx, 500) * 34);
    bodyX(-reach(my, 500) * 16);
    eyeX(reach(mx, 350) * em * 0.55);
    eyeY(reach(my, 350) * em * 0.35);
    const [cx, cy] = fromCenter(codex, event);
    cloudY(reach(cx, 500) * 30);
    cloudX(-reach(cy, 500) * 22);
  }, { passive: true });
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

/** Reação ao clique: a foto treme e perde força, o mascote pula, a nuvem dá uma volta e os anéis de radar se abrem. */
function tremor({ card, photo, rings, mascotHop, mascotShadow, codexSpin }) {
  return gsap.timeline()
    .fromTo(photo, { x: 0 }, { x: 10, duration: 0.6, ease: 'pf-tremor' }, 0)
    .fromTo(photo, { rotation: 0 }, { rotation: 3.5, duration: 0.6, ease: 'pf-tremor-giro' }, 0)
    .fromTo(card, { scale: 1 }, { scale: 0.975, duration: 0.12, ease: 'power2.out', yoyo: true, repeat: 1 }, 0)
    .fromTo(rings, { scale: 0.9, autoAlpha: 0.9 }, { scale: 1.3, autoAlpha: 0, duration: 0.8, stagger: 0.15, ease: 'power2.out' }, 0)
    .fromTo(mascotHop, { yPercent: 0 }, { yPercent: -30, duration: 0.2, ease: 'power2.out', yoyo: true, repeat: 1 }, 0.04)
    .fromTo(mascotShadow, { scale: 1 }, { scale: 0.65, duration: 0.2, ease: 'power2.out', yoyo: true, repeat: 1 }, 0.04)
    .fromTo(mascotHop, { scaleX: 1, scaleY: 1 }, { scaleX: 1.08, scaleY: 0.86, duration: 0.08, ease: 'power1.out', yoyo: true, repeat: 1 }, 0.44)
    .fromTo(codexSpin, { rotationY: 0 }, { rotationY: 360, duration: 0.8, ease: 'power3.inOut' }, 0);
}

/** Expansão até cobrir a tela: o fundo cresce a partir do cartão (clip-path); palco e nome voam para o centro (Flip). */
async function expand(parts) {
  const { card, stage, name } = parts;
  const mine = run;
  const p = portal(stage, name.textContent.trim(), new URL(mine.url).host);
  await p.stage.querySelector('img').decode().catch(() => {}); // a imagem já está em cache: só evita um quadro vazio
  if (run !== mine) return; // cancelado (ou recomeçado) enquanto isso

  const state = Flip.getState([stage, name]);
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
    .add(Flip.from(state, { targets: [p.stage, p.name], scale: true, duration: 1, ease: 'expo.inOut' }), 0)
    .fromTo(p.bands, { scaleY: 0 }, { scaleY: 1, duration: 0.9 }, 0.1)
    .fromTo(p.go, { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.6, ease: 'power3.out' }, 0.75)
    .addLabel('navegar', 0.9)
    .call(go, null, 'navegar')
    .fromTo(p.progress, { scaleX: 0 }, { scaleX: 1, duration: 1.1, ease: 'power2.inOut' }, 'navegar')
    // Chegando ao centro, os dois personagens comemoram: o mascote pula e a nuvem gira de novo.
    .to(p.hop, { yPercent: -24, duration: 0.18, ease: 'power2.out', yoyo: true, repeat: 3 }, 'navegar')
    .to(p.spin, { rotationY: '+=360', duration: 0.9, ease: 'power3.inOut' }, 'navegar');
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
function reset({ card, photo, rings, mascotHop, mascotShadow, codexSpin, kicker, url }) {
  if (!run) return;
  run.shake.kill();
  run.expand?.kill();
  run.portal?.remove();
  gsap.set(card, { scale: 1 });
  gsap.set(photo, { x: 0, rotation: 0, rotationX: 0, rotationY: 0 });
  gsap.set(rings, { autoAlpha: 0 });
  gsap.set(mascotHop, { yPercent: 0, scaleX: 1, scaleY: 1 });
  gsap.set(mascotShadow, { scale: 1 });
  gsap.set(codexSpin, { rotationY: 0 });
  gsap.to([kicker, url], { autoAlpha: 1, y: 0, duration: motion.d(0.3), ease: 'power2.out' });
  run = null;
}

/**
 * Cópia do palco para a tela cheia, na pose em que está: atributos sem "style" e os estilos inline (escritos pelo
 * GSAP) copiados pelo CSSOM, que a CSP permite em qualquer navegador.
 */
function copy(node) {
  if (node.nodeType !== Node.ELEMENT_NODE) return node.cloneNode(false);
  const out = document.createElementNS(node.namespaceURI, node.localName);
  for (const a of node.attributes) {
    if (a.name !== 'style') out.setAttributeNS(a.namespaceURI, a.name, a.value);
  }
  if (node.getAttribute('style')) out.style.cssText = node.style.cssText;
  node.childNodes.forEach((child) => out.append(copy(child)));
  return out;
}

/** Tela sobreposta (decorativa: o anúncio vai para a região viva). */
function portal(stage, name, host) {
  const stageCopy = copy(stage);
  const nameEl = el('p', { class: 'author-portal__name', 'data-flip-id': 'autor-nome', text: name });
  const progress = el('span');
  const goEl = el('p', { class: 'author-portal__go' }, el('span', { text: `Abrindo ${host}` }), el('span', { class: 'author-portal__bar' }, progress));
  const bands = [el('span', { class: 'author-portal__band author-portal__band--top' }), el('span', { class: 'author-portal__band author-portal__band--bottom' })];
  const root = el('div', { class: 'author-portal', 'aria-hidden': 'true' }, ...bands, el('div', { class: 'author-portal__content' }, stageCopy, nameEl, goEl));
  return { root, stage: stageCopy, hop: stageCopy.querySelector('[data-mascot-hop]'), spin: stageCopy.querySelector('[data-codex-spin]'), name: nameEl, go: goEl, progress, bands };
}
