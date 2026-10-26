# Inventário da documentação

Ferramenta que mantém a documentação honesta: lista os símbolos nomeados do código próprio, gera o
[catálogo de símbolos](../../docs/referencia/catalogo-simbolos.md) e verifica cobertura, pastas, links e âncoras. Só lê
arquivos (e escreve o catálogo com `--catalogo`); não executa o código analisado.

```bash
node scripts/documentacao/inventario.mjs --verificar
```

```bash
node scripts/documentacao/inventario.mjs --catalogo
```

Requer `node` (≥ 20.19, com `npm ci` feito: usa `rolldown/parseAst`, que vem com o Vite), `php` e `python` (3.x) no
`PATH`.

| Arquivo | Papel |
|---|---|
| `inventario.mjs` | orquestra: arquivos do Git, símbolos PHP/Python/JS/Twig, catálogo e verificações |
| `simbolos-php.php` | símbolos PHP pelo tokenizer (uma linha JSON por símbolo) |
| `simbolos-py.py` | símbolos Python pela `ast` |

Como manter: ao criar ou renomear uma função, método, classe ou macro, descreva-a na página de referência que lista o
arquivo em `<!-- fontes: … -->` (em [`docs/referencia/codigo/`](../../docs/referencia/codigo/README.md)); ao criar uma
pasta com arquivos próprios, dê a ela um `README.md` ou uma linha na
[matriz de cobertura](../../docs/cobertura-documentacao.md). Regras completas e funções:
[referência](../../docs/referencia/codigo/scripts/scripts.md#inventário-da-documentação-scriptsdocumentacao).
