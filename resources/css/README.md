# Estilos (`resources/css/`)

`app.css` (cerca de 1.250 linhas): Tailwind 4 com `@import "tailwindcss" source(none)` e `@source` em `templates/` e
`resources/js/` (só as classes usadas entram); `@theme` com **paleta fechada** (grafite azulado, azul de marca e ação,
turquesa, âmbar e os tokens das situações, que nunca usam a cor da marca) e a fonte Overpass (OFL) lida de
`node_modules/@fontsource-variable/overpass`; componentes em `@layer components`; `html[data-motion]` comanda as
transições. O CSS do Leaflet fica sem camada, por isso não é afetado pelo reset.

Gerado em `public/assets/build/styles-<hash>.css` por `npm run build`. Decisões:
[ADR-0012](../../docs/adr/0012-identidade-motion-e-build-de-assets.md); fonte e licença:
[licenças e fontes](../../docs/licencas-e-fontes.md).
