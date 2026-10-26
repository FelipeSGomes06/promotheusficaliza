# Referência técnica

| Página | Conteúdo |
|---|---|
| [codigo/](codigo/README.md) | contratos de cada classe, função, método e macro, por camada; testes; scripts; ML; landing; vídeo |
| [catalogo-simbolos.md](catalogo-simbolos.md) | todos os símbolos nomeados, com assinatura, linha, página e status (gerado) |
| [dicionario-de-dados.md](dicionario-de-dados.md) | esquema do Firebird após as 11 migrações, privilégios e convenções |
| [implantacao-e-ci.md](implantacao-e-ci.md) | versões, Compose, imagem PHP, Apache, Firebird, ferramentas de qualidade e CI |

Rotas HTTP: [http.md](../http.md). Operação: [operacao.md](../operacao.md). O catálogo é regenerado com
`node scripts/documentacao/inventario.mjs --catalogo` e a coerência é conferida com `--verificar`
([inventário](../../scripts/documentacao/README.md)).
