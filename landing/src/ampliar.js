/*
 * Ampliar capturas. Cada captura é um link para a imagem em tamanho real: sem JavaScript, o link abre o arquivo.
 * Com JavaScript, abre um <dialog> modal com a mesma imagem; Esc, o botão Fechar ou um clique fora fecham, e o foco
 * volta ao link de onde saiu. No celular a imagem fica larga e rola dentro do diálogo (também dá para pinçar).
 */
export function iniciarAmpliacao() {
  const dialogo = document.querySelector('[data-ampliacao]');
  if (!dialogo || typeof dialogo.showModal !== 'function') return;
  const imagem = dialogo.querySelector('[data-ampliacao-imagem]');
  const legenda = dialogo.querySelector('[data-ampliacao-legenda]');
  let origem = null;

  document.addEventListener('click', (evento) => {
    const link = evento.target.closest('a[data-ampliar]');
    if (!link || evento.button !== 0 || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey) return;
    evento.preventDefault();
    const miniatura = link.querySelector('img');
    origem = link;
    imagem.width = Number(link.dataset.largura);
    imagem.height = Number(link.dataset.altura);
    imagem.alt = miniatura.alt;
    imagem.src = link.href;
    legenda.textContent = miniatura.alt;
    dialogo.showModal();
  });
  dialogo.querySelector('[data-ampliacao-fechar]').addEventListener('click', () => dialogo.close());
  // Clique no fundo escurecido (fora do conteúdo): o alvo é o próprio <dialog>.
  dialogo.addEventListener('click', (evento) => {
    if (evento.target === dialogo) dialogo.close();
  });
  dialogo.addEventListener('close', () => {
    imagem.removeAttribute('src');
    origem?.focus();
    origem = null;
  });
}
