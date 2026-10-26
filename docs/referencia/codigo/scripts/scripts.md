# Scripts de apoio

<!-- fontes: scripts/ci-preparar-banco.php scripts/backup-restore-teste.sh scripts/sondagem-firebird.php scripts/avaliar-sementes.php scripts/capturar-telas.mjs scripts/verificacao-interface/comum.cjs scripts/verificacao-interface/verify.cjs scripts/verificacao-interface/record.cjs scripts/verificacao-interface/captures.cjs scripts/verificacao-interface/fps.cjs scripts/verificacao-interface/multas.cjs scripts/verificacao-interface/agentes.cjs scripts/verificacao-interface/acompanhamento.cjs scripts/verificacao-interface/preparar-revisao.php scripts/verificacao-interface/revisao-fluxos.cjs scripts/verificacao-interface/auditoria.cjs scripts/verificacao-interface/ajuda.cjs scripts/documentacao/inventario.mjs scripts/documentacao/simbolos-php.php scripts/documentacao/simbolos-py.py -->

Scripts fora da aplicação: preparo de CI, prova de backup, sondagem do driver, avaliação do método preventivo,
verificação da interface no navegador e o inventário desta documentação. **Nenhum roda por HTTP.** A coluna "Efeito"
diz o que cada um muda — vários **criam ou substituem dados** e só podem rodar num banco de demonstração.

## Resumo

| Script | Para quê | Efeito | Requisitos |
|---|---|---|---|
| `scripts/ci-preparar-banco.php` | só na CI: espera o Firebird de serviço (até 30 tentativas, 2 s) e cria o usuário da aplicação | cria/atualiza **usuário** do Firebird | `DB_TEST_DSN`, `DB_USER`, `DB_PASSWORD`, `FIREBIRD_ROOT_PASSWORD` |
| `scripts/backup-restore-teste.sh` | `gbak` do banco de demonstração e restauração num banco **separado** (`fiscaliza_restaurado.fdb`), comparando contagens | cria `.fbk` e o banco restaurado no volume do Firebird | Compose rodando; lê só `DB_MIGRATION_USER`/`DB_MIGRATION_PASSWORD` do `.env` |
| `scripts/sondagem-firebird.php` | sondagem exploratória do PDO_FIREBIRD (tipos, `RETURNING`, paginação, `CONTAINING`, índice por expressão, rollback, lock entre conexões) | cria e apaga a tabela `PROBE` no banco de `DB_TEST_DSN` | `DB_TEST_DSN` e dono do esquema |
| `scripts/avaliar-sementes.php` | avalia a heurística preventiva em várias sementes do cenário | **SUBSTITUI os dados de domínio** de `DB_DSN` pelo cenário de cada semente (contas preservadas); termina com a última semente carregada | `DEMO_ALLOW_RESET=true` e marcador do conjunto sintético |
| `scripts/capturar-telas.mjs` | capturas PNG por cenários JSON, pelo Chrome/Edge headless via DevTools Protocol (sem dependências) | só lê, salvo o que os cenários fizerem (`antes`) | navegador local; conta de teste descartável |
| `scripts/verificacao-interface/*` | verificação da interface com Playwright | depende do script (tabela abaixo) | `PF_BASE`, `PF_USUARIO`, `PF_SENHA`; Playwright |
| `scripts/documentacao/*` | inventário e verificação desta documentação | escreve só `docs/referencia/catalogo-simbolos.md` (com `--catalogo`) | Node ≥ 20.19 com as dependências do projeto, PHP e Python 3 |

## `scripts/avaliar-sementes.php`

Uso: `php scripts/avaliar-sementes.php [--perfil=compacto|completo] 2027 2028 …` (padrão: 2027–2031 e `DEMO_PROFILE`).
Para cada semente, carrega o cenário pelo `DemoSeeder` com a identidade de migração e lê a avaliação da última
execução da análise; imprime por semente o limiar escolhido, `tp/fp/fn`, precisão e revocação do **modelo** e da
**referência "falha recente"**, e a prevalência; depois o agregado. Valores indefinidos (denominador zero) aparecem
como "—", nunca 0 (`$fmt`). Recusa sem `DEMO_ALLOW_RESET=true` ou com dados sem o marcador
([`MetaRepository::DATASET_MARKER`](../infrastructure/repositorios-operacao.md)). Para voltar ao padrão:
`php bin/fiscaliza demo:seed --reset`. Resultados registrados: [verificação](../../../verificacao.md) e
[análise preventiva](../../../analise-preventiva.md).

## `scripts/capturar-telas.mjs`

Uso: `node scripts/capturar-telas.mjs <cenarios.json> <pasta-de-saida>`; `BROWSER` (caminho do Chrome) e `BASE`
(padrão `http://127.0.0.1:8080/promotheusfiscaliza`). Cada cenário: `nome`, `caminho`, `largura`, `altura`, `movel?`,
`reduzir?` (movimento reduzido), `login?` (`[usuario, senha]`), `antes?` (JS após carregar), `esperar?`,
`esperarDepois?`, `inteira?`, `medir?` (expressão JS que devolve um objeto). Imprime um JSON com arquivo, medidas e erros
de console por cenário.

| Função | Contrato |
|---|---|
| `sleep` | `(ms)` — espera. |
| `target` | `()` — até 50 tentativas em `/json/list` até achar a página e devolve o `webSocketDebuggerUrl`. |
| `send` | `(method, params = {})` — comando do DevTools Protocol com id sequencial; rejeita com a mensagem de erro do navegador. |
| `evaluate` | `(expression)` — `Runtime.evaluate` aguardando promessas; lança a exceção da página. |
| `navigate` | `(url)` — navega e espera `Page.loadEventFired` (no máximo 15 s). |

## Verificação da interface (`scripts/verificacao-interface/`)

Instruções de execução no [README da pasta](../../../../scripts/verificacao-interface/README.md). Todos entram com conta
(o sistema não tem leitura pública — ADR-0014) e bloqueiam os ladrilhos do OpenStreetMap.

| Script | Verifica | Grava dados? |
|---|---|---|
| `verify.cjs` | CSP com e sem conta, polling, filtro, ficha, rede, envio, movimento, teclado, celular, ladrilhos sintéticos, sessão encerrada | **sim**: falhas simuladas e ciência de alertas |
| `record.cjs` | vídeos WebM das interações (`clip` por cena; 3º argumento filtra pelo prefixo) | **sim** (mesmas ações) |
| `captures.cjs` | capturas JPEG 1440×900 e 390×844 da evidência de 18/10 | não |
| `fps.cjs` | intervalos entre quadros (mediana, p95, máximo) durante as animações | não |
| `multas.cjs` | aba Multas: filtro, origem, 422, 409, sem JS, movimento reduzido, 1440/390/360 px | **sim**: rascunhos e edições de teste |
| `agentes.cjs` | cadastro de agente: base pelo mapa ou endereço, rua pelo CEP (respostas simuladas + servidor real), sem JS | **sim**: agentes de teste |
| `acompanhamento.cjs` | alerta de agente: ciência → responsável → providência → conflito → conta desativada → posição simulada | **sim**: conta de teste, ciência, providências, posição |
| `preparar-revisao.php` | prepara volumes da revisão de 24/10 (ordem antiga + 31 encerradas, equipamento não instalado, 22 missões); `--limpar` cancela/arquiva (nada é apagado) | **sim** (pelos serviços, com o usuário da aplicação) |
| `revisao-fluxos.cjs` | R01–R08 da revisão de 24/10 (ADR-0023) | **sim**: ordens, conferências, falha simulada, conta de teste, encerramento de missões |
| `auditoria.cjs` | regressões A01, A10, A11 da auditoria de 25/10 | não (respostas simuladas no navegador) |
| `ajuda.cjs` | ajuda contextual "?" em 16 telas: mouse, teclado, toque, Esc, bordas, leitor de tela, sem JS | não |

### Funções

| Função | Onde | Contrato |
|---|---|---|
| `entrar` | `comum.cjs` (exportada) | `(page)` — abre `/entrar`, preenche `#u`/`#p` com `PF_USUARIO`/`PF_SENHA` e envia; lança "Login recusado" se continuar no login. |
| `sessao` | `comum.cjs` (exportada) | `(browser)` — faz login num contexto descartável e devolve o `storageState` (vídeos começam já na tela pedida). |
| `instantaneo` | `comum.cjs` (exportada) | `(page)` — `GET /api/central` com a sessão da página (sem conta: 401). |
| `sleep` | vários | `(ms)` — espera. |
| `check` | vários | `(nome, ok, detalhe = null)` — registra uma verificação no relatório JSON. |
| `page` | vários | `(size, opções)` — contexto novo (viewport, movimento reduzido, JS, toque), bloqueio de ladrilhos, captura de erros de console e violações de CSP, login. |
| `shot` | vários | `(p, name, full)` — captura JPEG e registra no relatório. |
| `noHScroll` | `acompanhamento`, `agentes`, `multas`, `revisao-fluxos` | `(p)` — confere que não há rolagem horizontal. |
| `flash` / `submit` / `z` | `acompanhamento`, `revisao-fluxos` | textos dos avisos (`[data-toasts]`, `[data-flash-stack]`) · clica e espera a navegação · `z` (dentro de uma avaliação na página) completa com zero à esquerda para montar `datetime-local`. |
| `text` / `signIn` | `revisao-fluxos` | texto do primeiro elemento do seletor · login da conta de teste com a senha mostrada uma vez pela redefinição. |
| `waitStreet` / `waitStatus` / `status` / `typeCep` | `agentes` | esperam (até 5 s) o valor da rua e a mensagem do estado do CEP · lê `[data-cep-status]` · limpa e digita o CEP tecla a tecla. |
| `open` / `inside` / `firstLink` / `strip` / `describe` | `ajuda` | o `<details>` da dica está aberto? · a dica cabe na janela? · `href` do primeiro link de uma tela · tira o prefixo da instalação de uma URL · descrição acessível (`aria-describedby`) de um campo. |
| `key` / `groups` / `until` / `toast` | `auditoria` | chave `tipo:id` de um recurso · rótulo e posição dos grupos do mapa (`.cluster-wrap`) · espera até o N-ésimo polling simulado (máx. 30 s) · classe, ícone e texto do aviso atual. |
| `fineId` / `hidden` / `shown` | `multas` | id da multa a partir de um texto da lista · o seletor está oculto? · visibilidade de vários seletores. |
| `clip` / `hover` | `record` | grava uma cena num contexto com `recordVideo` · passa o mouse e espera. |
| `tileSvg` / `ctx` / `central` / `diag` / `panelState` | `verify` | ladrilho SVG sintético com as coordenadas · contexto com captura de violações de CSP e opções (tamanho, toque, sem conta, ladrilhos) · abre a Visão geral com a consulta e espera `window.__pfCentral` · lê o diagnóstico `__pfCentral` (consultas, aplicadas, canceladas, animações, camadas) · visibilidade, opacidade, `inert`, título e URL da ficha. |
| `measure` / `measure.loop` | `fps` | mede intervalos com `requestAnimationFrame` por 700 ms depois de disparar a ação. |

Evidências produzidas por estes scripts: [`docs/evidencias/`](../../../evidencias/README.md).

## Inventário da documentação (`scripts/documentacao/`)

Ferramenta desta documentação, sem efeito no sistema. Uso (na raiz):

```bash
node scripts/documentacao/inventario.mjs --verificar
```

| Modo | Faz |
|---|---|
| sem argumento | imprime `{arquivos, simbolos, documentados}` |
| `--lista` | TSV: arquivo, tipo, nome qualificado, visibilidade, assinatura, linha, `ok`/`-` |
| `--catalogo` | regenera [`docs/referencia/catalogo-simbolos.md`](../../catalogo-simbolos.md) |
| `--verificar` | falha (código 1) se faltar página de referência para um arquivo de código, símbolo na sua página, README ou linha na [matriz de cobertura](../../../cobertura-documentacao.md) para uma pasta, ou se um link relativo/âncora de Markdown estiver quebrado |

Regras: arquivos vêm de `git ls-files --cached --others --exclude-standard` (o que o `.gitignore` exclui, como
`vendor/` e `node_modules/`, já fica de fora), excluindo `.claude/`, `.playwright-mcp/` e, da contagem de símbolos, os
arquivos gerados (`build/`, `public/assets/vendor/`). Cada página de referência declara no topo
`<!-- fontes: … -->`; um símbolo conta como documentado quando o nome qualificado aparece na página do seu arquivo entre
crases, seguido de `(` ou num título. Âncoras seguem a regra do GitHub (minúsculas, sem pontuação, acentos mantidos,
espaço → hífen, sufixo `-1`, `-2` em repetições). Os Markdown copiados de fora (`docs/planejamento-codex-*`,
`ml/marilia-setores/0*`) não têm links verificados: são histórico.

| Função | Contrato |
|---|---|
| `git` | `(...a)` — executa `git` na raiz e devolve a saída. |
| `isCode` / `generated` / `lines` | extensão de código (`.php`, `.js`, `.mjs`, `.cjs`, `.py` e `bin/fiscaliza`; macros Twig são lidas à parte) · caminho de `build/` ou `public/assets/vendor/` · linhas JSON não vazias. |
| `jsSymbols` | `(file)` — analisa com `rolldown/parseAst` e devolve funções, classes, métodos, objetos com funções e componentes `Alpine.data('nome')`, com nome qualificado pelo escopo (ex.: `central.init`, `initCep.locate`). |
| `jsSymbols.lineOf` / `jsSymbols.add` / `jsSymbols.isFn` / `jsSymbols.keyName` / `jsSymbols.visit` | linha de um deslocamento · registra símbolo · nó é função · nome de chave · percorre a árvore acumulando o escopo. |
| `walkDocs` | `(dir)` — Markdown sob um diretório. |
| `anchor` | `(text)` — âncora no padrão do GitHub. |
| `writeCatalog` / `writeCatalog.rel` / `writeCatalog.esc` | escreve o catálogo agrupado por pasta · caminho relativo · escape de `|`. |
| `verify` / `verify.anchorsIn` | as verificações acima · âncoras de um arquivo (ignora blocos de código). |
| `simbolos-php.php` | tokenizer do PHP (não executa nada): classes, enums, interfaces, traits, funções e métodos com visibilidade, `static`, assinatura e linha; uma linha JSON por símbolo. Modificadores são zerados em `{`, `}`, `;`, `(`, `)` e `,` para que parâmetros promovidos do construtor não contaminem o método seguinte. Twig: macros são extraídas por `inventario.mjs`. |
| `walk` (`simbolos-py.py`) | `(node, file, prefix)` — percorre a AST do Python e imprime funções e classes com nome qualificado; saída em UTF-8. |
