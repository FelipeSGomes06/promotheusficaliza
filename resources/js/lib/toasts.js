/*
 * Avisos (toasts). Regras:
 * - sucesso só depois da confirmação do servidor (quem chama decide; aqui não há "otimismo");
 * - erro fica até a pessoa fechar ou até a causa sumir (dismiss pela chave) — nunca some por um temporizador;
 * - mesma chave = mesmo aviso: o texto é atualizado em vez de empilhar outro (ex.: polling sem conexão);
 * - leitores de tela recebem o texto por regiões vivas fixas (polite para tudo, assertive só para erro).
 * Movimento: GSAP na entrada/saída e Flip para a pilha se acomodar; modo reduzido mostra o estado final.
 */
import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';
import { motion } from './motion.js';
import { icon } from './dom.js';

const ICONS = { success: 'i-check-circle', error: 'i-error-circle', warning: 'i-alert', alert: 'i-alert', info: 'i-info' };
const TIMEOUT = { success: 5000, info: 6000, warning: 9000, alert: 9000 };
const MAX = 4;
const live = new Map(); // chave → { el, timer, remaining, started, type }
let seq = 0;

const region = () => document.querySelector('[data-toasts]');

export function announce(text, assertive = false) {
  const node = document.querySelector(assertive ? '[data-live-assertive]' : '[data-live-polite]');
  if (!node) return;
  node.textContent = '';
  window.setTimeout(() => { node.textContent = text; }, 60);
}

function build(type, title, body, actions, onClose) {
  const el = document.createElement('div');
  el.className = `toast toast--${type}`;
  el.dataset.flipId = `toast-${++seq}`;
  const badge = document.createElement('span');
  badge.className = 'toast__icon';
  badge.append(icon(ICONS[type] || 'i-info'));
  const t = document.createElement('p');
  t.className = 'toast__title';
  const b = document.createElement('p');
  b.className = 'toast__body';
  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'toast__close';
  close.setAttribute('aria-label', 'Fechar aviso');
  close.append(icon('i-close'));
  close.addEventListener('click', onClose);
  el.append(badge, t, close, b);
  const bar = document.createElement('div');
  bar.className = 'toast__actions';
  el.append(bar);
  fill(el, title, body, actions);
  return el;
}

function fill(el, title, body, actions = []) {
  el.querySelector('.toast__title').textContent = title;
  const b = el.querySelector('.toast__body');
  b.textContent = body || '';
  b.hidden = !body;
  const bar = el.querySelector('.toast__actions');
  bar.replaceChildren(...actions.map((a) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `btn btn--small ${el.classList.contains('toast--error') ? '' : 'btn--ghost'}`;
    if (!el.classList.contains('toast--error')) btn.classList.add('toast__action');
    const label = document.createElement('span');
    label.textContent = a.label;
    btn.append(label);
    btn.addEventListener('click', () => a.onClick(btn));
    return btn;
  }));
  bar.hidden = actions.length === 0;
}

function arm(entry) {
  window.clearTimeout(entry.timer);
  if (!entry.timeout) return;
  entry.started = Date.now();
  entry.timer = window.setTimeout(() => dismiss(entry.key), entry.remaining);
}
function hold(entry) {
  if (!entry.timeout || !entry.started) return;
  window.clearTimeout(entry.timer);
  entry.remaining = Math.max(1200, entry.remaining - (Date.now() - entry.started));
  entry.started = 0;
}

function enter(el, type) {
  if (motion.reduced()) { gsap.set(el, { autoAlpha: 1 }); return; }
  // Desktop: sobe a partir da borda de baixo; celular: desce do topo (onde a pilha fica).
  const fromTop = window.matchMedia('(max-width: 1099px)').matches;
  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
  tl.fromTo(el, { autoAlpha: 0, y: fromTop ? -16 : 18, scale: 0.96 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.28 })
    .fromTo(el.querySelector('.toast__icon'), { scale: 0.55, rotation: -12 }, { scale: 1, rotation: 0, duration: 0.32, ease: 'back.out(2.2)' }, '<0.06');
  if (type === 'error') tl.to(el, { keyframes: { x: [0, -7, 6, -4, 2, 0] }, duration: 0.34, ease: 'power1.inOut' }, '<0.12');
}

/**
 * Mostra (ou atualiza) um aviso.
 * @param {{type?: string, title: string, body?: string, key?: string, actions?: Array<{label: string, onClick: Function}>, timeout?: number|false}} opts
 */
export function show({ type = 'info', title, body = '', key = null, actions = [], timeout } = {}) {
  const k = key || `t${Date.now()}-${Math.random()}`;
  const existing = live.get(k);
  announce(body ? `${title}. ${body}` : title, type === 'error');
  const ms = timeout === false || type === 'error' ? 0 : (timeout ?? TIMEOUT[type] ?? 6000);
  if (existing) {
    // Mesma chave com outro tipo (erro e depois sucesso): troca classe e ícone antes do texto (fill lê a classe).
    if (existing.type !== type) {
      existing.el.classList.replace(`toast--${existing.type}`, `toast--${type}`);
      existing.el.querySelector('.toast__icon').replaceChildren(icon(ICONS[type] || 'i-info'));
      existing.type = type;
    }
    fill(existing.el, title, body, actions);
    existing.timeout = ms;
    existing.remaining = ms;
    arm(existing);
    if (!motion.reduced()) gsap.fromTo(existing.el, { scale: 0.985 }, { scale: 1, duration: 0.2, ease: 'power2.out' });
    return k;
  }
  const host = region();
  if (!host) return k;
  // Limite visual: sai o aviso mais antigo que não seja erro.
  if (live.size >= MAX) {
    const oldest = [...live.values()].find((e) => e.type !== 'error') || [...live.values()][0];
    dismiss(oldest.key, true);
  }
  const el = build(type, title, body, actions, () => dismiss(k));
  el.setAttribute('aria-live', 'off');
  const entry = { key: k, el, type, timeout: ms, remaining: ms, timer: 0, started: 0 };
  live.set(k, entry);
  el.addEventListener('mouseenter', () => hold(entry));
  el.addEventListener('mouseleave', () => arm(entry));
  el.addEventListener('focusin', () => hold(entry));
  el.addEventListener('focusout', (e) => { if (!el.contains(e.relatedTarget)) arm(entry); });
  const state = Flip.getState([...host.children]);
  host.append(el);
  if (!motion.reduced()) Flip.from(state, { duration: 0.24, ease: 'power2.out' });
  enter(el, type);
  arm(entry);
  return k;
}

export function dismiss(key, instant = false) {
  const entry = live.get(key);
  if (!entry) return;
  live.delete(key);
  window.clearTimeout(entry.timer);
  const host = region();
  const remove = () => {
    const state = Flip.getState([...host.children].filter((c) => c !== entry.el));
    entry.el.remove();
    if (!motion.reduced()) Flip.from(state, { duration: 0.22, ease: 'power2.out' });
  };
  if (instant || motion.reduced()) { gsap.killTweensOf(entry.el); remove(); return; }
  gsap.killTweensOf(entry.el);
  gsap.to(entry.el, { autoAlpha: 0, x: 28, duration: 0.18, ease: 'power2.out', onComplete: remove });
}

/** Mensagens do servidor (depois de um POST com redirecionamento) viram avisos animados; sem script continuam na página. */
export function adoptServerFlashes() {
  const stack = document.querySelector('[data-flash-stack]');
  if (!stack) return;
  const titles = { success: 'Feito', error: 'Não foi possível concluir', warning: 'Atenção' };
  stack.querySelectorAll('[data-flash]').forEach((f, i) => {
    const type = f.dataset.type === 'error' ? 'error' : (f.dataset.type === 'warning' ? 'warning' : 'success');
    show({ type, title: titles[type], body: f.textContent.trim(), key: `flash-${i}` });
  });
  stack.remove();
}
