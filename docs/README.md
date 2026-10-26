# Documentação (`docs/`)

Ponto de partida: o [manual do projeto](MANUAL-DO-PROJETO.md). Este índice diz o que há em cada arquivo e qual é a fonte
canônica de cada assunto (uma explicação por contrato; as demais páginas apontam para ela).

## Para quem usa

| Arquivo | Conteúdo |
|---|---|
| [MANUAL-DO-PROJETO.md](MANUAL-DO-PROJETO.md) | visão de ponta a ponta: o que é, como está montado, cada área, dados, operação, verificação |
| [DICIONARIO-DO-DEV.md](DICIONARIO-DO-DEV.md) | termos técnicos do projeto explicados num tom de café, com links para aprofundar |
| [guias/](guias/README.md) | um guia por aba ou funcionalidade, com tarefas, estados, regras e o caminho no código |
| [guia-de-uso.md](guia-de-uso.md) | passeio rápido pelas telas, com os textos da interface |
| [dominio.md](dominio.md) | situações, transições, indicadores e regras em linguagem de negócio |
| [analise-preventiva.md](analise-preventiva.md) | **canônico** do método `atencao-v1`, ajuste, avaliação e resultado |
| [planejamento-por-setores.md](planejamento-por-setores.md) | **canônico** do método de Planejar reforço, dados reais e resultados |

## Para quem desenvolve ou opera

| Arquivo | Conteúdo |
|---|---|
| [arquitetura.md](arquitetura.md) | camadas, fluxo de uma requisição, decisões |
| [http.md](http.md) | **canônico** de rotas, acesso, campos e respostas |
| [referencia/](referencia/README.md) | referência de código, dicionário de dados, implantação e CI, catálogo de símbolos |
| [operacao.md](operacao.md) | **canônico** de configuração, instalação, atualização, backup, cron e publicação |
| [seguranca.md](seguranca.md) | controles implementados, riscos e limites |
| [verificacao.md](verificacao.md) | **canônico** das execuções de testes e verificações, datadas |
| [matriz-requisitos.md](matriz-requisitos.md) | requisito → implementação → teste |
| [cobertura-documentacao.md](cobertura-documentacao.md) | pastas, arquivos, símbolos, rotas e regras cobertos por esta documentação; divergências e pendências |

## Histórico e contexto

| Arquivo ou pasta | Conteúdo |
|---|---|
| [adr/](adr/README.md) | 23 decisões de arquitetura, datadas |
| [evidencias/](evidencias/README.md) | registros de verificação com capturas, vídeos e relatórios |
| [planejamento-codex-2026-10-18/](planejamento-codex-2026-10-18/00-LEIA-PRIMEIRO.md) | planejamento original recebido do Codex em 18/10/2026 (histórico; não é reescrito) |
| [prompt-ajuda-contextual.md](prompt-ajuda-contextual.md) | pedido que originou a ajuda contextual "?" (já implementada; preservado como registro) |
| [ia-e-autoria.md](ia-e-autoria.md) · [licencas-e-fontes.md](licencas-e-fontes.md) · [roteiro-video.md](roteiro-video.md) | autoria e uso de IA · licenças e créditos · roteiro do vídeo |

`docs/privado/` é local e ignorado pelo Git: não faz parte da documentação publicada.
