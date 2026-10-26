# JavaScript (`resources/js/`)

Melhoria progressiva sobre telas que já funcionam sem JavaScript: Alpine (build CSP, sem `eval`), GSAP e Leaflet local.

| Arquivo ou pasta | Papel | Referência |
|---|---|---|
| `app.js` | entrada única: inicia Alpine e as bibliotecas comuns e carrega o módulo da tela de `body[data-page]` | [entrada e bibliotecas](../../docs/referencia/codigo/frontend/entrada-e-bibliotecas.md) |
| [`lib/`](lib/README.md) | bibliotecas comuns | idem |
| [`pages/`](pages/README.md) | comportamento de cada tela | [central](../../docs/referencia/codigo/frontend/central.md), [páginas](../../docs/referencia/codigo/frontend/paginas.md) |
