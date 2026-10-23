/*
 * Endereço nos formulários ([data-cep-lookup]). Com 8 dígitos no CEP, pergunta ao próprio servidor (GET /api/cep/…; a
 * CSP só libera 'self') e mostra "Rua: …"; a rua vai no campo do formulário, que fica oculto. Sem rua na resposta (CEP
 * não encontrado, geral da cidade, de outra cidade, consulta fora do ar ou sem rede), mostra o campo "Rua" para
 * digitar, com o motivo. Com a rua, "Levar a base até este endereço" (GET /api/localizar) põe o ponto no mapa; se ainda
 * não há ponto, isso acontece sozinho. Nada aqui impede o envio. Sem JavaScript, o campo "Rua" fica visível e o
 * servidor localiza pelo endereço ao salvar.
 */
import { el, setBusy } from './dom.js';
import { reveal } from './reveal.js';

const WAIT_MS = 250; // pausa depois do 8º dígito: colar ou corrigir não dispara duas consultas
const GIVE_UP_MS = 8000; // o servidor desiste do provedor em 3 s; isto cobre o próprio servidor lento
const FALLBACK = 'Não foi possível consultar o CEP agora. Digite a rua abaixo; o cadastro não depende da consulta.';
const LOCATE_FALLBACK = 'Não foi possível localizar agora. Tente de novo em instantes ou marque a base no mapa.';

/** GET JSON com desistência; null em falha de rede, tempo esgotado ou resposta que não é JSON. */
async function getJson(url, signal) {
  try {
    const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal });
    return await res.json();
  } catch {
    return null;
  }
}

export function initCep() {
  document.querySelectorAll('[data-cep-lookup]').forEach((box) => {
    const cep = document.getElementById(box.dataset.cep);
    const street = document.getElementById(box.dataset.street);
    const manual = box.querySelector('[data-cep-manual]');
    const status = box.querySelector('[data-cep-status]');
    if (!cep || !street || !manual || !status || !box.dataset.url) return;
    const lat = document.getElementById(box.dataset.lat);
    const lng = document.getElementById(box.dataset.lng);
    const locateBox = box.querySelector('[data-locate-box]');
    const locateButton = box.querySelector('[data-locate]');
    const locateStatus = box.querySelector('[data-locate-status]');
    const canLocate = Boolean(lat && lng && locateBox && locateButton && locateStatus && box.dataset.locateUrl);

    const digitsOf = () => cep.value.replace(/\D/g, '');
    let asked = digitsOf().length === 8 ? digitsOf() : ''; // CEP que já veio no formulário não é consultado de novo
    let filled = false; // a rua atual veio do CEP (e não da digitação)
    let mode = 'empty';
    let pending = null;
    let locating = null;
    let timer = 0;

    const say = (target, message, kind = '') => {
      if (kind === 'loading') target.replaceChildren(el('span', { class: 'spinner', 'aria-hidden': 'true' }), message);
      else target.textContent = message;
      target.classList.toggle('is-note', kind === 'note' && message !== '');
    };
    const syncLocate = () => {
      if (!canLocate) return;
      const on = street.value.trim() !== '';
      if (!on) { locating?.abort(); locating = null; say(locateStatus, ''); }
      reveal(locateBox, on);
    };
    const show = (next, message = '') => {
      mode = next;
      if (next === 'found') status.replaceChildren('Rua: ', el('strong', {}, street.value));
      else say(status, message, next === 'loading' ? 'loading' : (next === 'manual' ? 'note' : ''));
      if (next !== 'loading') reveal(manual, next === 'manual');
      syncLocate();
    };

    const locate = async () => {
      locating?.abort();
      const mine = new AbortController();
      locating = mine;
      const giveUp = window.setTimeout(() => mine.abort(), GIVE_UP_MS);
      const query = new URLSearchParams({ rua: street.value.trim() });
      if (digitsOf().length === 8) query.set('cep', digitsOf());
      setBusy(locateButton, true, 'Localizando…');
      say(locateStatus, 'Procurando o endereço no mapa…', 'loading');
      const data = await getJson(`${box.dataset.locateUrl}?${query}`, mine.signal);
      window.clearTimeout(giveUp);
      if (locating !== mine) return; // outra localização (ou a rua apagada) passou na frente
      locating = null;
      setBusy(locateButton, false);
      if (data?.situacao === 'ENCONTRADO' && typeof data.lat === 'string' && typeof data.lng === 'string') {
        lat.value = data.lat;
        lng.value = data.lng;
        lng.dispatchEvent(new Event('change')); // o mapa (picker.js) move o ponto e o texto de estado
        say(locateStatus, data.mensagem || '');
        return;
      }
      say(locateStatus, typeof data?.mensagem === 'string' && data.mensagem ? data.mensagem : LOCATE_FALLBACK, 'note');
    };

    const lookup = async (digits) => {
      const mine = new AbortController();
      pending = mine;
      asked = digits;
      const giveUp = window.setTimeout(() => mine.abort(), GIVE_UP_MS);
      show('loading', `Procurando a rua do CEP ${digits.slice(0, 5)}-${digits.slice(5)}…`);
      const data = await getJson(`${box.dataset.url}${digits}`, mine.signal);
      window.clearTimeout(giveUp);
      if (pending !== mine) return; // outro CEP foi digitado nesse meio-tempo
      pending = null;
      if (data?.situacao === 'ENCONTRADO' && typeof data.rua === 'string' && data.rua) {
        street.value = data.rua;
        filled = true;
        show('found');
        if (canLocate && !lat.value && !lng.value) locate(); // ainda sem ponto: a base vai para o endereço
        return;
      }
      if (filled) { street.value = ''; filled = false; } // não deixa a rua de outro CEP
      show('manual', typeof data?.mensagem === 'string' && data.mensagem ? data.mensagem : FALLBACK);
    };

    cep.addEventListener('input', () => {
      const digits = digitsOf();
      if (digits.length === 8 && digits === asked) return;
      window.clearTimeout(timer);
      pending?.abort();
      pending = null;
      if (digits.length === 8) {
        timer = window.setTimeout(() => lookup(digits), WAIT_MS);
        return;
      }
      asked = '';
      if (filled) { street.value = ''; filled = false; } // a rua era do CEP que foi apagado; a digitada fica
      show(street.value || mode === 'manual' ? 'manual' : 'empty');
    });
    street.addEventListener('input', syncLocate);
    locateButton?.addEventListener('click', locate);

    // Estado inicial: rua gravada com CEP aparece como "Rua: …"; erro do servidor ou rua sem CEP deixam o campo à vista.
    if (street.getAttribute('aria-invalid') === 'true') show('manual');
    else if (street.value && asked) { filled = true; show('found'); }
    else if (street.value) show('manual', 'Rua digitada sem CEP. Com o CEP, a rua é preenchida sozinha.');
    else if (asked) show('manual', 'Este CEP está gravado sem rua. Se souber, digite a rua.');
    else show('empty');
  });
}
