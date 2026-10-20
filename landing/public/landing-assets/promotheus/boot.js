/*
 * Roda antes da primeira pintura (script clássico e externo: a CSP não aceita script inline). Marca que há
 * JavaScript e aplica a escolha de movimento salva neste navegador — a mesma chave "pf-motion" do sistema, que
 * fica na mesma origem. Sem escolha salva, vale a preferência do sistema operacional (prefers-reduced-motion).
 */
(function () {
  var html = document.documentElement;
  html.classList.add('js');
  try {
    var escolha = localStorage.getItem('pf-motion');
    if (escolha === 'reduced' || escolha === 'full') html.setAttribute('data-motion', escolha);
  } catch (e) { /* armazenamento bloqueado: segue a preferência do sistema */ }
})();
