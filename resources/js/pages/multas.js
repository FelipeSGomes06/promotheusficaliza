/*
 * Multas: melhoria progressiva dos formulários. Sem JavaScript todos os blocos aparecem (com a instrução de preencher
 * só o da origem escolhida) e o servidor ignora ou recusa o bloco que não se aplica. Com JavaScript, o bloco inativo
 * fica oculto e desabilitado (não é enviado), mas o que foi digitado nele continua no lugar se a pessoa voltar atrás;
 * os campos que só valem numa situação (data-show-if) ficam fora da vista até ela, sem deixar de ser enviados.
 * Movimento: entrada curta (opacidade + 8 px) só no bloco que aparece; a troca rápida termina sempre na última escolha.
 */
import { reveal } from '../lib/reveal.js';

/* Já tem algo digitado ou um erro: o bloco não pode ficar escondido. Lista sem opção vazia (ex.: Fonte) não conta. */
const filled = (el) => el.querySelector('[aria-invalid]') !== null
  || Array.from(el.querySelectorAll('input:not([type=hidden]):not([type=radio]):not([type=checkbox]), textarea, select'))
    .some((c) => c.value.trim() !== '' && (c.tagName !== 'SELECT' || c.options[0]?.value === ''));

/*
 * Divulgação progressiva: [data-show-if="campo=A B;outro=C"] aparece quando um dos campos vale um dos valores
 * ("campo=" quer dizer em branco) ou quando já tem algo digitado ou um erro. Escondido, o bloco continua sendo enviado
 * (não é desabilitado): só sai da vista, e nada gravado se perde. As condições espelham o que o servidor exige.
 */
function showIf(form) {
  const groups = Array.from(form.querySelectorAll('[data-show-if]'), (el) => [el, el.dataset.showIf.split(';').filter(Boolean).map((rule) => {
    const [name, values = ''] = rule.split('=');
    return [name, values.split(' ')];
  })]);
  if (!groups.length) return;
  const value = (name) => (form.elements.namedItem(name)?.value ?? '').trim();
  const update = (animate = true) => groups.forEach(([el, rules]) => reveal(el, rules.some(([name, values]) => values.includes(value(name))) || filled(el), animate));
  form.addEventListener('change', () => update());
  form.addEventListener('input', () => update());
  update(false);
}

function sourceForm(form) {
  const radios = form.querySelectorAll('[data-fine-source]');
  const kind = form.querySelector('[data-fine-kind]');
  const blocks = form.querySelectorAll('[data-source-block]');
  const speed = form.querySelector('[data-speed-block]');
  const note = form.querySelector('[data-source-note]');
  const agency = form.querySelector('#f-agency_id');
  form.querySelectorAll('[data-fine-nojs]').forEach((n) => { n.hidden = true; });
  // Mesmas comparações de FineService::divergence: órgão autuador diferente do órgão do radar ou do agente escolhido,
  // ou cadastro do radar que não combina com o fato (sem a função da infração, nunca instalado, fixo com operador).
  const agencyOf = (select) => select?.selectedOptions[0]?.dataset.agency ?? '';
  const radarLacks = () => {
    const option = form.querySelector('#f-equipment_id')?.selectedOptions[0];
    if (!option?.value) return false;
    const operator = form.querySelector('#f-operator_agent_id')?.value ?? '';
    return (Boolean(kind?.value) && kind.value !== 'OUTRA' && !(option.dataset.functions ?? '').split(' ').includes(kind.value))
      || option.dataset.neverInstalled === '1' || (option.dataset.modality === 'FIXO' && operator !== '');
  };
  const update = (animate = true) => {
    const source = Array.from(radios).find((r) => r.checked)?.value ?? '';
    blocks.forEach((b) => reveal(b, b.dataset.sourceBlock === source, animate));
    if (speed) {
      const on = kind?.value === 'VELOCIDADE';
      reveal(speed, on, animate);
      speed.querySelectorAll('input').forEach((i) => { i.disabled = !on; });
    }
    if (note) {
      const resource = { RADAR: agencyOf(form.querySelector('#f-equipment_id')), AGENTE: agencyOf(form.querySelector('#f-agent_id')) }[source] ?? '';
      reveal(note, (resource !== '' && agency?.value !== '' && resource !== agency?.value) || (source === 'RADAR' && radarLacks()) || filled(note), animate);
    }
  };
  form.addEventListener('change', () => update());
  update(false); // estado inicial sem animação: o servidor já entregou a página pronta
  suggestDescription(form, kind);

  // Prévia da velocidade considerada com a mesma regra do servidor (FineRules::consideredSpeed), que é quem grava.
  const measured = form.querySelector('#f-speed_measured_kmh');
  const out = form.querySelector('[data-speed-considered]');
  if (measured && out) {
    measured.addEventListener('input', () => {
      const m = Number.parseInt(measured.value, 10);
      out.textContent = Number.isFinite(m) && m > 0 ? `${m <= 107 ? Math.max(0, m - 7) : Math.floor((m * 93 + 50) / 100)} km/h` : 'calculada ao salvar';
    });
  }
}

/* Descrição curta sugerida a partir do tipo e do logradouro, como na frase da ficha; o que a pessoa escreveu não é trocado. */
function suggestDescription(form, kind) {
  const desc = form.querySelector('#f-description');
  const street = form.querySelector('#f-street');
  if (!desc || !kind) return;
  const suggestion = () => (kind.value ? `${kind.selectedOptions[0].text}${street?.value.trim() ? ` na ${street.value.trim()}` : ''}`.slice(0, 240) : '');
  let last = suggestion();
  const fill = () => {
    const next = suggestion();
    if (desc.value.trim() === '' || desc.value === last) desc.value = next;
    last = next;
  };
  kind.addEventListener('change', fill);
  street?.addEventListener('input', fill);
}

function checkForm(form) {
  const scope = form.querySelector('[data-check-scope]');
  const effective = form.querySelector('[data-check-effective]');
  if (!scope || !effective) return;
  const update = () => {
    const on = scope.value === 'POSTERIOR';
    reveal(effective, on);
    effective.querySelectorAll('input').forEach((i) => { i.disabled = !on; });
  };
  scope.addEventListener('change', update);
  effective.hidden = scope.value !== 'POSTERIOR';
  effective.querySelectorAll('input').forEach((i) => { i.disabled = effective.hidden; });
  reviewQuestion(form);
}

/* Lembrete: a pergunta só faz sentido depois do resultado. A data só vai junto com "Escolher data" (o servidor recusa as duas). */
function reviewQuestion(form) {
  const status = form.querySelector('[name="document_status"]');
  const box = form.querySelector('[data-check-review]');
  if (!status || !box) return;
  const radios = Array.from(box.querySelectorAll('[data-review-choice]'));
  const dateBlock = box.querySelector('[data-review-date]');
  const answer = box.querySelector('[data-review-answer]');
  box.querySelectorAll('[data-review-nojs]').forEach((n) => { n.hidden = true; });
  let auto = null; // "Não precisa" marcado pelo sistema, não pela pessoa
  const sync = (animate = true) => {
    const picked = radios.find((r) => r.checked);
    const custom = picked?.value === 'DATA';
    reveal(dateBlock, custom, animate);
    dateBlock.querySelectorAll('input').forEach((i) => { i.disabled = !custom; });
    answer.textContent = picked ? ` · ${picked.parentElement.textContent.trim()}` : '';
  };
  const onStatus = (animate = true) => {
    reveal(box, status.value !== '', animate);
    // Em dia: quase nunca precisa de lembrete, então vem recolhida com "Não precisa"; pendência ou não conferida: em destaque.
    const regular = status.value === 'REGULARIDADE_INFORMADA';
    if (regular && !radios.some((r) => r.checked)) {
      auto = radios.find((r) => r.value === 'NAO');
      if (auto) auto.checked = true;
    } else if (!regular && auto?.checked) {
      auto.checked = false;
      auto = null;
    }
    if (!box.querySelector('[aria-invalid]')) box.open = !regular;
    sync(animate);
  };
  radios.forEach((r) => r.addEventListener('change', () => { auto = null; sync(); }));
  status.addEventListener('change', () => onStatus());
  onStatus(false);
}

export function init() {
  document.querySelectorAll('[data-fine-form]').forEach(sourceForm);
  document.querySelectorAll('[data-fine-check]').forEach(checkForm);
  document.querySelectorAll('form').forEach(showIf);
}
