/*
 * Formulários enviados ao servidor (POST): confirmação de ações irreversíveis, botão em "enviando…" e bloqueio de
 * envio duplicado. O resultado só aparece depois da resposta (mensagem do servidor após o redirecionamento).
 * Voltar pelo histórico (bfcache) devolve os botões ao normal.
 */
import { setBusy } from './dom.js';

const LABELS = [
  [/^Entrar/, 'Entrando…'],
  [/^Sair/, 'Saindo…'],
  [/^(Cadastrar|Salvar|Criar)/, 'Salvando…'],
  [/^Iniciar/, 'Iniciando…'],
  [/^Concluir/, 'Concluindo…'],
  [/^Cancelar/, 'Cancelando…'],
  [/^Arquivar/, 'Arquivando…'],
  [/^Registrar/, 'Registrando…'],
  [/^Recalcular/, 'Calculando…'],
  [/^Simular/, 'Simulando…'],
  [/^Abrir/, 'Abrindo…'],
  [/^Confirmar/, 'Confirmando…'],
];
const busyLabel = (btn) => {
  const text = (btn?.textContent || '').trim();
  return (LABELS.find(([re]) => re.test(text)) || [null, 'Enviando…'])[1];
};

export function initForms() {
  document.addEventListener('submit', (e) => {
    const form = e.target;
    // Atributo, não form.method: um campo chamado "method" (como no Planejar) encobre a propriedade.
    if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') || '').toLowerCase() !== 'post' || form.hasAttribute('data-no-guard')) return;
    if (form.dataset.sending === '1') {
      e.preventDefault();
      return;
    }
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      e.preventDefault();
      return;
    }
    const btn = e.submitter instanceof HTMLButtonElement ? e.submitter : form.querySelector('button[type="submit"], button:not([type])');
    form.dataset.sending = '1';
    // Depois do evento: se outro ouvinte cancelou o envio, nada fica preso em "enviando".
    window.setTimeout(() => {
      if (e.defaultPrevented) { delete form.dataset.sending; return; }
      setBusy(btn, true, busyLabel(btn));
    }, 0);
  });

  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('form[data-sending]').forEach((f) => {
      delete f.dataset.sending;
      f.querySelectorAll('[aria-busy="true"]').forEach((b) => setBusy(b, false));
    });
  });

  // Resumo de erros: recebe o foco para que leitores de tela anunciem o que corrigir.
  document.querySelector('[data-error-summary]')?.focus();

  // Link para seção recolhida (ex.: "Mudar situação" → "Mais ações") abre a seção de destino.
  const openTarget = (id) => {
    const target = id && document.getElementById(id);
    if (target && target.tagName === 'DETAILS') target.open = true;
  };
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href^="#"]');
    if (link) openTarget(link.getAttribute('href').slice(1));
  });
  openTarget(window.location.hash.slice(1));

  // Formulário de equipamento: mostra só as características do tipo escolhido (as demais não são enviadas) e só os
  // tipos de local coerentes com a situação (a mesma regra do servidor, EquipmentService::validate).
  const typeSelect = document.querySelector('[data-type-select]');
  if (typeSelect) {
    const statusSelect = typeSelect.form.querySelector('select[name="status"]');
    const installed = ['OPERACIONAL', 'FALHA', 'MANUTENCAO', 'FURTADO'];
    const update = () => {
      document.querySelectorAll('[data-spec-type]').forEach((fs) => {
        const on = fs.dataset.specType === typeSelect.value;
        fs.hidden = !on;
        fs.disabled = !on;
      });
      const mobile = typeSelect.selectedOptions[0]?.dataset.mobile === '1';
      const status = statusSelect ? statusSelect.value : typeSelect.form.dataset.equipmentStatus;
      const allowed = mobile ? ['BASE'] : ({ NAO_INSTALADO: ['PLANEJADO'], DESATIVADO: ['INSTALADO', 'PLANEJADO'] }[status] || (installed.includes(status) ? ['INSTALADO'] : ['INSTALADO', 'PLANEJADO']));
      document.querySelectorAll('[data-location-kind] option').forEach((o) => { o.disabled = !allowed.includes(o.value); });
      const kind = document.querySelector('[data-location-kind]');
      if (kind && !allowed.includes(kind.value)) kind.value = allowed[0];
      document.querySelectorAll('[data-mobile-note]').forEach((n) => { n.hidden = !mobile; });
    };
    typeSelect.addEventListener('change', update);
    statusSelect?.addEventListener('change', update);
    update();
  }
}
