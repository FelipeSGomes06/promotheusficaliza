import { movimento } from './movimento.js';

/*
 * Menu do celular (botão que abre/fecha o painel de links) e botão "Reduzir movimento". Sem JavaScript, o CSS mostra
 * os links numa faixa e o botão de movimento fica escondido (a preferência do sistema continua valendo).
 */
export function iniciarNavegacao() {
  const topo = document.querySelector('[data-topo]');
  const botao = topo.querySelector('[data-menu-btn]');
  const painel = document.getElementById(botao.getAttribute('aria-controls'));

  const abrir = (aberto, devolverFoco = false) => {
    topo.classList.toggle('is-aberto', aberto);
    botao.setAttribute('aria-expanded', String(aberto));
    if (devolverFoco) botao.focus();
  };

  botao.hidden = false;
  botao.addEventListener('click', () => abrir(botao.getAttribute('aria-expanded') !== 'true'));
  painel.addEventListener('click', (evento) => {
    if (evento.target.closest('a')) abrir(false);
  });
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && topo.classList.contains('is-aberto')) abrir(false, true);
  });
  document.addEventListener('click', (evento) => {
    if (topo.classList.contains('is-aberto') && !topo.contains(evento.target)) abrir(false);
  });

  const preferencia = document.querySelector('[data-movimento]');
  const sincronizar = () => {
    preferencia.setAttribute('aria-pressed', String(movimento.reduzido()));
    preferencia.title = movimento.porEscolha()
      ? 'Escolha salva neste navegador. Clique para alternar.'
      : 'Seguindo a preferência de movimento do sistema. Clique para escolher.';
  };
  preferencia.hidden = false;
  preferencia.addEventListener('click', () => movimento.alternar());
  movimento.aoMudar(sincronizar);
  sincronizar();
}
