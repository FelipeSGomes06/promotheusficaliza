# Identidade nova com acesso somente por conta — 19/10/2026

Junção de duas linhas de trabalho feitas em paralelo sobre o commit `52d2d15`: a identidade de trânsito
([ADR-0012](../adr/0012-identidade-motion-e-build-de-assets.md), [ADR-0013](../adr/0013-perfil-compacto-de-demonstracao.md))
e o acesso somente com conta cadastrada pelo administrador ([ADR-0014](../adr/0014-acesso-somente-com-conta-cadastrada.md),
que nasceu como "ADR-0012" na outra linha e foi renumerada). O cadastro obrigatório foi feito sobre a interface
antiga; aqui ele foi levado para as telas novas.

## O que foi ajustado na junção

| Onde | Ajuste |
|---|---|
| Rotas, `RequireRole`, `SessionContext`, sessão, contas, testes | vieram da outra linha sem alteração de comportamento |
| `layout.twig` | sem conta: trilho só com a marca, preferência de animação e "Entrar" (fora da própria tela de login); sem links internos nem "o que é real" |
| `auth/login.twig`, `error.twig` | desenho novo com os textos do acesso restrito; na página de erro, sem conta o botão leva ao login |
| `resources/js/pages/central.js` | a consulta periódica que recebe 401 recarrega a página e o servidor leva ao login (o `public/assets/js/central.js` antigo, já removido, tinha a mesma regra) |
| `resources/js/lib/shell.js` | gaveta do celular sem links: a cascata e o foco passam para o rodapé (antes o GSAP avisava no console) |
| Sobre, Agentes | textos sem "consulta aberta" nem "entrar para editar" |
| `scripts/verificacao-interface/` | `comum.cjs` faz o login e lê a API com a sessão; nova seção M (sessão encerrada → login) |
| Documentação | README, operação ("Atualizar uma instalação publicada"), verificação e matriz |

## Verificação (contêiner de nuvem, projeto Compose `fiscaliza-claude`, PHP 8.5.11, Firebird 5.0.4)

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **100 testes, 447 asserções, OK** |
| `phpstan analyse` (nível 6) · `php-cs-fixer --dry-run` | 0 erros · 0 de 88 arquivos |
| `npm run build` duas vezes | saída idêntica; é a versionada |
| Navegador ([relatório](2026-10-19-identidade-com-acesso-por-conta/verificacao-navegador.json)) | ver abaixo |
| Comando de backup do guia de atualização (`gbak` com o SYSDBA do contêiner) | gerou o `.fbk` no volume |

| Seção | Resultado |
|---|---|
| A. 54 carregamentos (1440×900 e 390×844): 11 endereços sem conta, depois os mesmos e 6 de escrita com conta | sem conta, todos terminam no login sem menu (o endereço inexistente mostra 404 sem menu); **0 violações de CSP, 0 erros ou avisos de console, 0 rolagem horizontal** |
| B–L (polling, corrida de filtro, ficha interrompida, rede, envio, formulário clássico, movimento reduzido, voltar, celular, teclado, tiles) | mesmos resultados da [verificação de 18/10](2026-10-18-identidade-e-motion.md#navegador-chromium-headless-141-via-playwright-cabeçalhos-reais-do-servidor), agora com conta |
| M. Sessão encerrada no servidor com a central aberta | a consulta seguinte recebe 401 e a tela vai para `/entrar?voltar=/promotheusfiscaliza/` |

Capturas ([pasta](2026-10-19-identidade-com-acesso-por-conta/capturas)): 01 sem conta, `/equipamentos` leva ao login ·
02 central depois do login · 03 Sobre com as miniaturas corrigidas · 04 Contas · 05 login no celular · 06 gaveta do
celular sem conta. O mapa aparece sem fundo porque os servidores de tiles são bloqueados neste ambiente.
