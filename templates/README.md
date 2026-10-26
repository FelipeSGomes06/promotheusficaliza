# Telas Twig (`templates/`)

Twig 3 com autoescape: todo texto vindo do banco é escapado. Nenhum `<script>` ou estilo inline (a CSP só aceita
arquivos locais); dados para o JavaScript vão em `<script type="application/json">` (`#snapshot-data`, `#plan-data`).
Todas as telas funcionam sem JavaScript. Macros e mapa das telas:
[macros e telas](../docs/referencia/codigo/templates/macros-e-telas.md).

| Arquivo ou pasta | Papel |
|---|---|
| `layout.twig` | moldura: menu (trilho/gaveta), barra, aviso "Demonstração", avisos do servidor, botão Animações |
| `_ui.twig` | macros de formulário e interface (campos, ícones, selos, ajuda "?") — [referência](../docs/referencia/codigo/templates/macros-e-telas.md#_uitwig) |
| `_ajuda.twig` | textos da ajuda contextual (situações e características vêm do domínio) — [referência](../docs/referencia/codigo/templates/macros-e-telas.md#_ajudatwig) |
| `_autoria.twig` | cartão de autoria da página Sobre (mascote, Codex, definições SVG) — [referência](../docs/referencia/codigo/templates/macros-e-telas.md#_autoriatwig) |
| `central.twig` | Visão geral — [guia](../docs/guias/visao-geral.md) |
| `about.twig` | Sobre — [guia](../docs/guias/sobre-e-interface.md) |
| `error.twig` | página de erro com mensagem segura e código de referência |
| [`accounts/`](accounts/README.md) · [`agents/`](agents/README.md) · [`analysis/`](analysis/README.md) · [`auth/`](auth/README.md) · [`equipment/`](equipment/README.md) · [`fines/`](fines/README.md) · [`maintenance/`](maintenance/README.md) · [`planning/`](planning/README.md) | telas de cada área |
