# Identidade de trânsito, GSAP e reatividade — 18/10/2026

Pedido: substituir a direção neutra por uma identidade de fiscalização de trânsito, com interações perceptíveis
(hover, avisos animados, reatividade), mantendo PHP/Slim, Twig, Firebird e Leaflet, sem enfraquecer a CSP, e reduzir o
cenário de demonstração. Decisões: [ADR-0012](../adr/0012-identidade-motion-e-build-de-assets.md) e
[ADR-0013](../adr/0013-perfil-compacto-de-demonstracao.md). Base de partida: commit `52d2d15` (correções da auditoria
preservadas — nenhum teste antigo foi alterado além do ajuste de `var/` descrito abaixo).

## Ambiente

Contêiner de nuvem do Claude Code (Linux), Docker 29.8.2, projeto Compose isolado `fiscaliza-claude` (porta 8091,
volume próprio), banco novo com dados sintéticos e contas de teste descartáveis. Diferenças em relação ao ambiente do
autor, todas fora do repositório:

- `deb.debian.org` é bloqueado pela rede desta sessão, então a imagem PHP foi montada com uma variante local do
  Dockerfile: mesmo `php:8.5.11-apache-trixie`, PDO_FIREBIRD compilado com os cabeçalhos do pacote `firebird-dev` do
  Ubuntu (API 3.0) e o `libfbclient` 5.0.4 copiado da imagem oficial do Firebird (o projeto usa fbclient 4.0.5).
- Dependências PHP instaladas pelo Composer a partir das fontes git exatas do `composer.lock` (os zips do GitHub são
  bloqueados); o PHPStan, que só tem zip, foi montado do repositório na mesma referência.
- **Servidores de tiles bloqueados** (OpenStreetMap e alternativos): as capturas e vídeos mostram o mapa sem fundo, com
  o aviso "Mapa de fundo indisponível nesta conexão" e o fundo pontilhado próprio. O layout dos tiles sob o reset do
  Tailwind foi medido com tiles sintéticos (seção L), sem usá-los nas capturas.
- Simulador ligado durante as verificações de polling e nas gravações; por isso as situações variam entre capturas.

## O que foi entregue no primeiro recorte (central) e depois nas demais telas

| Item do pedido | Onde |
|---|---|
| Marca de trânsito no lugar da chama | `public/assets/img/favicon.svg`, símbolo `i-brand` (navbar, gaveta, login, erro, Sobre) — capturas 01, 17, 21 |
| Composição de aplicação | trilho grafite + barra superior + chips de filtro + lista/mapa/ficha — 01–06 |
| Mapa de Marília com seleção de um radar | 02 (radar da Av. Sampaio Vidal centralizado na área visível, ponto em azul com anel finito) |
| Filtro reativo e lista vinculada | 03–05; vídeo 02 |
| Botão com hover, foco, pressão e carregamento | vídeo 01 (hover/pressão), 07 e vídeo 04 ("Simulando…") |
| Aviso de sucesso/erro com entrada e saída | 08, 09; vídeos 04 e 05 |
| Ficha que abre, fecha e aceita interrupção | 02, 05, 23; vídeo 03 |
| Demais telas no mesmo sistema | 10–17, 24–26 |
| Cenário compacto (23 recursos) | todas; contagens 16/7 conferidas em `DemoProfileTest` |

## Testes e checagens (no ambiente isolado, depois das alterações)

| Verificação | Resultado |
|---|---|
| Linha de base antes de alterar (`vendor/bin/phpunit`) | 89 testes / 314 asserções OK com `var/` existente; **num clone novo, 1 falha preexistente**: o teste da trava do simulador abria `var/simulador.lock` sem criar `var/` (ignorado pelo Git). Corrigido no próprio teste. |
| `vendor/bin/phpunit` | **96 testes, 385 asserções, OK** (novos: perfis compacto/completo, manifesto do Vite, tags de assets sem código inline, "Simular falha" em JSON) |
| `vendor/bin/phpstan analyse` (nível 6) · `php-cs-fixer --dry-run` | 0 erros · 0 de 88 arquivos |
| `npm run build` duas vezes | saída idêntica byte a byte (o CI confere o build versionado) |
| Reavaliação da análise nos dois perfis (sementes 2027–2031) | completo: precisão 8,5%, revocação 16,4% (referência 7,3% / 22,4%); compacto: 6,9% / 13,3% (referência 7,8% / 26,7%) — [detalhes](2026-10-18-avaliacao-sementes.md#perfis-compacto-e-completo-18102026) |
| Origem dedicada (`APP_BASE_PATH` vazio, `apache-origem-dedicada.conf`, porta 8092) | assets em `/assets/build/…`, fonte carregada, módulo da central carregado sob demanda, ficha abre, sem 4xx/5xx; caminho antigo redireciona |

## Navegador (Chromium headless 141 via Playwright, cabeçalhos reais do servidor)

Relatório completo: [`verificacao-navegador.json`](2026-10-18-identidade-e-motion/verificacao-navegador.json); scripts em
[`scripts/verificacao-interface/`](../../scripts/verificacao-interface/).

| Seção | O que foi feito | Resultado |
|---|---|---|
| A. CSP e console | 11 rotas sem conta + 6 com conta, em 1440×900 e 390×844 (34 carregamentos), ouvindo `securitypolicyviolation` | **0 violações, 0 erros de console, 0 rolagem horizontal** (a 1ª rodada achou rolagem lateral na central e em Sobre/Contas no celular — corrigido) |
| B. Atualizações | filtro Radar+Veículo, radar escolhido, foco na ficha, 17 s com simulador ligado | 3 consultas aplicadas; filtro, chips, seleção, URL, foco (`panel-title`) e posição do mapa idênticos; 0 animações ativas |
| C. Filtro antes da resposta | API atrasada 1,2 s; Radar → desmarca → Câmera | 2 pedidos cancelados, 1 aplicado; a lista final só tem câmeras |
| D. Abrir/fechar rápido | 6× abrir/Esc a cada 40 ms, depois abrir; depois fechar/abrir/fechar | termina **aberta** (opacidade 1) e depois **fechada** (`visibility:hidden`, `inert`); clique no centro da ficha fechada chega ao mapa; 0 animações após o anel finito |
| E. Falha de rede | `/api/central` abortado | indicador "Sem conexão", aviso vermelho **ainda presente após 12 s**; "Tentar agora" → "Conexão restabelecida", aviso de erro removido, seleção e filtro mantidos |
| F. Envio | POST atrasado 1,5 s, segundo clique durante o envio | `aria-busy`, rótulo "Simulando…", **1 POST**, nenhum sucesso antes da resposta, sucesso depois |
| G. Formulário clássico | "Registrar ciência" na ficha completa (POST + redirecionamento) | mensagem do servidor virou aviso animado; bloco estático removido |
| H. Movimento reduzido | sistema `reduce`; `pf-motion=reduced` salvo; `pf-motion=full` com sistema `reduce` | ficha completa em 30 ms nos dois primeiros (estado final); no terceiro anima; o botão diz "Pela preferência do sistema" / "Por sua escolha neste navegador" / "Ativadas por sua escolha (o sistema pede menos movimento)"; a escolha salva não é apagada |
| I. Trocar de rota e voltar | central → Equipamentos → voltar, 11 s | 2 consultas em 11 s (intervalo de 5 s): nenhum temporizador duplicado |
| J. Celular com toque | lista → toque na linha → folha → fechar; menu | folha visível e depois oculta/inert; gaveta em x=0 com foco no primeiro link |
| K. Teclado | foco num marcador, Enter, Esc | Enter abre a ficha com foco no título; Esc devolve o foco ao marcador |
| L. Tiles × reset do Tailwind | tiles sintéticos servidos pelo teste | imagens 256×256, `max-width: none`, aviso de mapa escondido quando os tiles carregam |

**Quadros (não é promessa de 60 fps):** intervalos medidos com `requestAnimationFrame` durante 700 ms, em Chromium
headless com renderização por software neste contêiner — ficha (desktop) mediana 16,7 ms, máx. 16,8; filtro + Flip
mediana 16,7, máx. 33,3 (um quadro perdido); gaveta (celular) 16,7 / 16,8; folha da ficha (celular) 16,7 / 33,3.
Não substitui medição em aparelhos reais.

## Capturas ([pasta](2026-10-18-identidade-e-motion/capturas))

01 central · 02 radar escolhido · 03 menu Tipo · 04 filtro Radar · 05 lista com ficha · 06 indicadores ·
07 envio carregando · 08 aviso de sucesso · 09 aviso de erro sem conexão · 10 ficha do equipamento · 11 cadastro com
erro (digitação mantida) · 12 manutenção · 13 revisões · 14 agente · 15 equipamentos · 16 Sobre (marca e F.O.G.O.) ·
17 entrar · 20–26 celular (central, menu, lista, folha da ficha, ficha completa, manutenção, entrar) ·
27 movimento reduzido pelo sistema (ficha já no estado final 100 ms depois do clique).

## Vídeos ([pasta](2026-10-18-identidade-e-motion/videos), WebM 1440×900 e 390×844, 6–16 s)

O cursor não aparece nas gravações; o hover aparece pela mudança do próprio elemento.

| Vídeo | Mostra |
|---|---|
| 01-hover-e-pressao | itens do menu, chips, botão atualizar, botão primário (cor, sombra, ícone) e pressão |
| 02-filtro-e-reorganizacao | Tipo → Radar, Câmera; "Precisa de atenção"; remover chip; limpar; "Ver os outros" (Flip e cascata) |
| 03-mapa-selecao-e-ficha | radar escolhido (voo do mapa, destaque do ponto), troca de recurso com a ficha aberta, fechar, abrir/fechar interrompido, lista e "Mostrar no mapa" |
| 04-envio-carregando-e-sucesso | hover no botão, "Simulando…" (1,2 s), aviso de sucesso, linha e ponto atualizados |
| 05-erro-sem-conexao-e-recuperacao | aviso de erro com tremor, persistente; "Tentar agora"; recuperação |
| 06-dados-recebidos-realce | outra sessão registra uma falha; a próxima consulta realça só a linha, o ponto e os números alterados e mostra um aviso de alerta |
| 07-celular-menu-lista-e-folha | gaveta, lista, folha da ficha e volta ao mapa (390×844, toque) |
| 08-movimento-reduzido | o botão de animações alterna e a ficha passa a abrir sem movimento |

## Limites e pendências

- Mapa de fundo não aparece nas evidências (rede do ambiente); o comportamento com tiles reais não foi revisto aqui.
- Sem leitor de tela real, sem axe/Lighthouse e sem teste com pessoa iniciante; contraste calculado sobre os tokens.
- Quadros medidos só em headless; nada medido em aparelho real.
- Termos da licença do GSAP não puderam ser lidos deste ambiente (página bloqueada): conferir antes de publicar.
- Os documentos de pesquisa do Codex (pasta fora do repositório) não estavam acessíveis no contêiner de nuvem; a
  implementação seguiu o texto do pedido e as skills lidas diretamente dos repositórios citados.
- Nada foi publicado; nenhuma configuração de produção foi alterada.
