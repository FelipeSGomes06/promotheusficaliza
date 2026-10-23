/*
 * Endereço nos formulários ([data-cep-lookup]). Com 8 dígitos no CEP, pergunta ao próprio servidor (GET /api/cep/…; a
 * CSP só libera 'self') e preenche a rua e a cidade, que continuam editáveis: só é trocado o que está vazio ou o que o
 * CEP anterior preencheu. Sem rua na resposta (CEP não encontrado, geral da cidade, de outra cidade, consulta fora do
 * ar ou sem rede), uma linha diz o motivo. Com a rua, "Levar a base até este endereço" (GET /api/localizar) põe o
 * ponto no mapa; se ainda não há ponto, isso acontece sozinho. Nada aqui impede o envio; sem JavaScript, o servidor
 * localiza pelo endereço ao salvar.
 */
import { el, setBusy } from './dom.js';
import { reveal } from './reveal.js';

const WAIT_MS = 250; // pausa depois do 8º dígito: colar ou corrigir não dispara duas consultas
const GIVE_UP_MS = 8000; // o servidor desiste do provedor em 3 s; isto cobre o próprio servidor lento
const FALLBACK = 'Não foi possível consultar o CEP agora. Digite a rua; o cadastro não depende da consulta.';
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
    const byId = (key) => (box.dataset[key] ? document.getElementById(box.dataset[key]) : null);
    const cep = byId('cep');
    const street = byId('street');
    const status = box.querySelector('[data-cep-status]');
    if (!cep || !street || !status || !box.dataset.url) return;
    const city = byId('city');
    const number = byId('number');
    const lat = byId('lat');
    const lng = byId('lng');
    const locateBox = box.querySelector('[data-locate-box]');
    const locateButton = box.querySelector('[data-locate]');
    const locateStatus = box.querySelector('[data-locate-status]');
    const canLocate = Boolean(lat && lng && locateBox && locateButton && locateStatus && box.dataset.locateUrl);

    const digitsOf = () => cep.value.replace(/\D/g, '');
    let asked = digitsOf().length === 8 ? digitsOf() : ''; // CEP que já veio no formulário não é consultado de novo
    let filledStreet = asked ? street.value : ''; // rua posta pelo CEP (a digitada nunca é trocada); a gravada com CEP conta como dele
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

    const locate = async () => {
      locating?.abort();
      const mine = new AbortController();
      locating = mine;
      const giveUp = window.setTimeout(() => mine.abort(), GIVE_UP_MS);
      const query = new URLSearchParams({ rua: street.value.trim() });
      if (digitsOf().length === 8) query.set('cep', digitsOf());
      if (number?.value.trim()) query.set('numero', number.value.trim());
      if (city?.value.trim()) query.set('cidade', city.value.trim());
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
      say(status, `Procurando a rua do CEP ${digits.slice(0, 5)}-${digits.slice(5)}…`, 'loading');
      const data = await getJson(`${box.dataset.url}${digits}`, mine.signal);
      window.clearTimeout(giveUp);
      if (pending !== mine) return; // outro CEP foi digitado nesse meio-tempo
      pending = null;
      const typed = street.value.trim() !== '' && street.value !== filledStreet;
      if (data?.situacao === 'ENCONTRADO' && typeof data.rua === 'string' && data.rua) {
        if (!typed) { street.value = data.rua; filledStreet = data.rua; }
        if (city && typeof data.cidade === 'string' && data.cidade && (!city.value.trim() || city.value.trim() === 'Marília')) city.value = data.cidade;
        say(status, typed ? `O CEP indica a rua ${data.rua}; a que você digitou foi mantida.` : 'Rua preenchida pelo CEP. Se precisar, corrija.');
        syncLocate();
        if (canLocate && !lat.value && !lng.value) locate(); // ainda sem ponto: a base vai para o endereço
        return;
      }
      if (!typed) { street.value = ''; filledStreet = ''; } // não deixa a rua de outro CEP
      say(status, typeof data?.mensagem === 'string' && data.mensagem ? data.mensagem : FALLBACK, 'note');
      syncLocate();
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
      if (filledStreet && street.value === filledStreet) { street.value = ''; filledStreet = ''; } // era do CEP apagado
      say(status, '');
      syncLocate();
    });
    street.addEventListener('input', syncLocate);
    locateButton?.addEventListener('click', locate);
    syncLocate();
  });
}
