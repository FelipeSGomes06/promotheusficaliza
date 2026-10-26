# Bibliotecas comuns (`resources/js/lib/`)

Contratos de cada função: [entrada e bibliotecas](../../../docs/referencia/codigo/frontend/entrada-e-bibliotecas.md).

| Arquivo | Papel |
|---|---|
| `dom.js` | ícones do sprite `icons.svg` (id validado, nunca URL arbitrária) e utilitários de DOM |
| `motion.js` | preferência de movimento (`pf-motion` + `prefers-reduced-motion`) e o botão Animações |
| `shell.js` | menu principal (trilho no desktop, gaveta no celular) |
| `reveal.js` | mostrar/ocultar blocos de formulário (oculto = desabilitado, não é enviado) |
| `toasts.js` | avisos: sucesso só após o servidor, erro fica até fechar, regiões vivas |
| `forms.js` | bloqueio de envio duplicado, confirmação de ações irreversíveis, rótulos "Salvando…" |
| `popovers.js` | ajuda contextual "?" (mouse, teclado, toque, `Esc`, bordas) |
| `picker.js` | mapa para marcar o ponto no cadastro (teclado incluso) |
| `cep.js` | consulta de CEP e ponto pelo endereço no cadastro de agente |
