# Fontes do frontend (`resources/`)

CSS e JavaScript **de origem**. O Vite (`npm run build`, [`vite.config.js`](../vite.config.js)) gera os arquivos
versionados em `public/assets/build/` (nomes com hash + `manifest.json`); a produção não precisa de Node e a CI confere
que o build bate com estas fontes. Nunca edite `public/assets/build/` à mão.

| Pasta | Conteúdo |
|---|---|
| [`css/`](css/README.md) | `app.css`: Tailwind 4 + componentes |
| [`js/`](js/README.md) | `app.js` (entrada única), `lib/` e `pages/` |

Referência: [frontend](../docs/referencia/codigo/frontend/entrada-e-bibliotecas.md); decisões:
[ADR-0012](../docs/adr/0012-identidade-motion-e-build-de-assets.md).
