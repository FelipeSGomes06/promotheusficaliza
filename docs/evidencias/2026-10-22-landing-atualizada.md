# Evidência — Landing atualizada: seis pilares, foto de Marília e correções da auditoria (22/10/2026)

Pedido: aplicar a auditoria da landing pública feita pelo Codex (pacote externo
`C:/Users/FelipedosSantosGomes/.codex/entregas-para-claude/fiscaliza-landing-auditoria-2026-10-22/`, achados A01–A06 e
matriz F01–F30) e trocar o fundo da hero por uma foto real de Marília que reage ao mouse. Decisões no
[ADR-0018](../adr/0018-landing-seis-pilares-e-foto-de-marilia.md). Base: `c9e95b1` (a mesma inspecionada pela
auditoria; `origin/main` sem commits novos e checkout limpo antes de começar).

**Implementado e verificado localmente. Não publicado**: nada foi enviado à VPS; a compressão em produção continua
pendente até o próximo `git pull` + `docker compose up -d --build` no servidor.

## Ambiente

- Windows 11, Docker Desktop 29.7.2. Compose isolado `-p pf-landing` a partir deste worktree (porta 127.0.0.1:8099,
  volume próprio, `.env` local com senhas aleatórias de teste). A landing e o sistema servidos pelo mesmo Apache do
  contêiner, como na VPS.
- Carga: `composer install`, `bin/fiscaliza migrate`, `demo:seed` (perfil compacto, inclui as multas), `urban:import`
  (execução e 64 quadrados), `user:create gestora --role=ADMIN` (conta de teste; senha só no rascunho local, fora do
  repositório), três `sim:tick` e o serviço `simulator` ligado durante as capturas. Nenhuma operação contra a produção.
- Navegador: Playwright 1.62.1 + Chromium 156.0.8078.4 headless (o runtime usado pela auditoria), locale pt-BR.
  axe-core 4.11.0 do pacote da auditoria, injetado via `page.evaluate` (a CSP da página não foi relaxada).

## Capturas do sistema

Feitas no ambiente acima, só navegando (abrir fichas, rolar, alternar mapa/lista): visão geral com a ficha de um radar
aberta, Equipamentos, ficha de agente, Manutenção, ficha de multa, Revisões recomendadas e Planejar reforço (passo 1 com
o mapa de calor; passos 2 e 3). Desktop 1440 × 900 @2x e celular 390 × 844 @2x, com recortes próprios para o celular.
Saída: `landing/public/landing-assets/promotheus/img/*.webp` (as antigas de 19/10 foram substituídas; `atencao-*` saiu).
O menu das capturas já tem Multas e Planejar reforço; os créditos do OpenStreetMap ficam visíveis na central e no
mapa de calor e também nas legendas.

## Cobertura F01–F30

| ID | Onde está na página |
|---|---|
| F01–F04 | `#sistema`: texto, captura atual (desktop com ficha; celular com mapa), pontos 1–3 e legenda (resumos, filtros combinados, formas de instalado/planejado/base/última posição, falta de sinal ≠ falha) |
| F05 | Legenda 4 e ponto 4 (no celular, no indicador "Ao vivo"): consulta a cada 5 s sem tirar seleção, mapa ou foco; dados simulados |
| F06–F09 | Cena 01 `#inventario`: sete tipos com características próprias, cadastro por etapas, endereço e ponto, local instalado/planejado/base, histórico; "Mais sobre o cadastro" (campos por tipo, série por fabricante, arquivar sem apagar); captura da ficha de agente (base × última posição) |
| F10–F11 | Cena 02 `#manutencao`: corretiva/programada/preventiva, uma intervenção em execução, alertas; "Como o histórico é preservado" (ciência, cancelamento com motivo, falha × falta de sinal, autoria e origem, aviso de mudança durante a edição) |
| F12–F13 | Cena 04 `#revisoes`: sete dias, motivos, próxima ação, método e retrospecto, a equipe decide; nota "não é probabilidade" |
| F14–F21 | Cena 03 `#multas` com selo "Registro demonstrativo" e nota de limites; destaques (origem com retrato, placa/RENAVAM separados das viaturas, etapa/pagamento/contestação); "O que também pode ser acompanhado" (AIT × código interno, justificativa de ausência, velocidades e considerada calculada, medidor/evidência, conferência temporal e "Não verificada", nova conferência ao corrigir identidade, lembrete opcional sem prazo legal, filtros e três resumos, retificação, cancelamento com motivo, campos pertinentes, conflito de edição e envio repetido) |
| F22–F27 | `#planejamento`: contagens reais agregadas e quadrados de ~2 km (rótulo, texto e fonte CC BY 4.0), mapa de calor com legenda "cor = estimativa semanal, não emergência", três passos (onde → quem → confirmar missão fictícia, concluir/cancelar libera o agente), captura dos passos 2 e 3, outro agente pede justificativa, retrospecto com previsões registradas antes; "Como interpretar" (referência histórica, setores experimentais, linha reta sem rota, atraso dos dados, sem prova de prevenção, rotina agendável cuja frequência depende da instalação) |
| F28 | Hero (nota de acesso), placa de chamada e `#dados` ("Posso acessar sem conta?") |
| F29 | `#dados` ("Como posso acompanhar as mudanças?") e detalhamento da cena 02 |
| F30 | Verificado abaixo (teclado, menu, movimento reduzido, sem JS/WebGL) |

Nenhum módulo ficou só no rodapé. Não há "três momentos", contadores animados, cadastro aberto, emissão/consulta de
multas, rota otimizada ou redução comprovada de acidentes no texto.

## Verificação

Rodada agrupada (script `verificar.cjs` no rascunho da sessão; o mesmo roteiro foi repetido uma vez inteira depois das
correções). Resultado da confirmação final:

| Cenário | Resultado |
|---|---|
| Larguras 320×640, 360×640, 390×844, 768, 899, 900, 959, 960, 1024×768, 1079, 1080, 1280×800, 1366×657, 1440 | 0 px de rolagem horizontal em todas; menu compacto até 1079 px, barra completa a partir de 1080 px com cada link numa linha ([1079](2026-10-22-landing-atualizada/10-cabecalho-1079.jpg), [1080](2026-10-22-landing-atualizada/10-cabecalho-1080.jpg)); foto sem sobrepor o texto nem o nome da marca; 360×640: as duas chamadas terminam em 514 e 578 px ([captura](2026-10-22-landing-atualizada/03-celular-360x640-hero.jpg)) |
| Menu (390 e 1024 px) | abre; Esc fecha e devolve o foco ao botão; clique fora fecha; link fecha e chega a `#recursos` |
| Âncoras `#inicio`, `#conteudo`, `#sistema`, `#recursos`, `#inventario`, `#manutencao`, `#multas`, `#revisoes`, `#planejamento`, `#dados`, `#avaliador` (1440 e 390) | todas existem; título abaixo do cabeçalho fixo e na tela |
| Teclado | 1º Tab = "Pular para o conteúdo" visível; ordem lógica (marca, menu, ações, chamadas, nota, Ampliar, índice, `<summary>`, …); nenhum elemento focável dentro de camada `aria-hidden` |
| Ampliar | Enter abre o diálogo com a imagem de 1920 px e foco em Fechar; Tab vai à área rolável; Esc fecha e o foco volta ao link; clique fora fecha. No celular a imagem rola na horizontal dentro do diálogo, sem rolagem da página ([desktop](2026-10-22-landing-atualizada/14-ampliacao-desktop.jpg), [celular](2026-10-22-landing-atualizada/15-ampliacao-celular.jpg)) |
| Sem JavaScript / sem WebGL / módulo 3D bloqueado / foto bloqueada (1440 e 390) | conteúdo e chamadas íntegros; sem WebGL o 3D nem é pedido e o SVG oficial fica; com o módulo bloqueado (falha provocada), SVG no lugar; sem a foto, a hero fica com o fundo de asfalto, sem ícone quebrado; nenhuma exceção ([sem WebGL](2026-10-22-landing-atualizada/16-sem-webgl-1440.jpg), [sem foto](2026-10-22-landing-atualizada/17-foto-ausente-1440.jpg), [sem JS](2026-10-22-landing-atualizada/18-sem-javascript-390.jpg)) |
| Ponteiro (depois da montagem) | nos quatro extremos a foto vai a (±12, ±8) px e a borda nunca aparece; parado 2,2 s volta a (0, 0); ao sair da hero volta a (0, 0); "Reduzir movimento" no meio do movimento leva a (0, 0) na hora e o mouse deixa de mexer ([extremo 1](2026-10-22-landing-atualizada/12-ponteiro-extremo-esquerda-cima.jpg), [extremo 2](2026-10-22-landing-atualizada/13-ponteiro-extremo-direita-baixo.jpg), [gravação](2026-10-22-landing-atualizada/11-ponteiro-hero.webm)) |
| Movimento reduzido pelo sistema e por `pf-motion=reduced` salvo | foto sem transformação, botão `aria-pressed="true"`, marca pronta |
| Toque (390, `hasTouch`) | toque no palco não desloca a foto |
| Fora da tela (3 s com a hero fora) | 9 ms de script e 53 ms de tarefas no total (o ticker do GSAP; nada é redesenhado) |
| Entrada direta por `/#sistema` | 1440 e 390: o módulo 3D não é pedido e nenhum canvas é criado; ao rolar até a hero (celular) ou aos objetos (desktop), carrega |
| Texto 200% (1280 e 390) e reflow 320 × 256 (≈ 400%) | 0 px de rolagem horizontal; a 320 × 256 o painel do menu cabe (183 px, termina em 247 de 256) e rola por dentro se precisar ([captura](2026-10-22-landing-atualizada/20-reflow-320x256-menu.jpg)) |
| Rede, CSP e cookies (página inteira rolada) | 0 erros de console, 0 respostas ≥ 400, 0 violações de CSP, 0 pedidos a outra origem, 0 cookies |
| Conteúdo | 6 legendas com a data 22/10/2026, 2 com crédito do OpenStreetMap; nenhuma imagem quebrada; vídeo ausente com "Vídeo de apresentação em breve." e sem botão de play |
| Metadados | title, description, `canonical` e `og:url` = `https://fiscaliza.devfelipegomes.com.br/`; `og:image` local (200, `image/jpeg`, 1200 × 630) |
| CTA "Acessar sistema" | chega a `/promotheusfiscaliza/entrar?voltar=…` com 200 |
| axe 4.11.0 (WCAG 2.0/2.1 A e AA + boas práticas): 1440, 900, 390, menu aberto, diálogo aberto | **0 violações**; 38 regras passaram (24 com o diálogo). Na primeira rodada o diálogo tinha 1 violação (`scrollable-region-focusable` na legenda), corrigida |
| Contraste dos 20 nós que o axe deixa inconclusivos (gradientes/camadas) | medido no pixel de fundo dominante de cada elemento renderizado: mínimo **6,18:1** (branco sobre o azul da placa e do botão), demais entre 7,4 e 18:1, em 1440 e 390 px; nenhum texto fica sobre a foto (interseção 0 em todas as larguras) |

O console mostra o aviso conhecido do compilador de shader do Chromium/Windows (`X4122`); a marca renderiza normal e
ele foi filtrado. As falhas de rede nos cenários "módulo 3D bloqueado" e "foto bloqueada" são provocadas pelo teste.

Primeira rodada → correções: o celular ainda baixava o 3D na entrada por `#sistema` (a página pinta no topo e só
depois rola até a âncora); o painel do menu passava da altura da janela no reflow; a legenda do diálogo era uma região
rolável sem foco; o rótulo "Ampliar" sobre a imagem cobria texto; o ponto 1 do celular encostava num número. Todos
resolvidos antes da confirmação acima.

### Compressão

`docker compose -p pf-landing up -d --build app`, depois `apachectl configtest` (Syntax OK) e o mesmo teste com
`apache-origem-dedicada.conf` copiada temporariamente para `sites-enabled` (Syntax OK). `curl` com
`Accept-Encoding: gzip, br` nos arquivos com hash do build atual:

| Arquivo | Content-Type | Content-Encoding | Corpo sem / com gzip |
|---|---|---|---|
| `pagina-DR8XDeby.js` | `text/javascript` | gzip, `Vary: Accept-Encoding` | 122.466 / 47.383 B |
| `cena3d-DNsL0XS0.js` | `text/javascript` | gzip, `Vary: Accept-Encoding` | 617.157 / 158.510 B |
| `landing-DjYD9PlQ.css` | `text/css` | gzip | 26.692 / 6.866 B |

Sem `Accept-Encoding`, o corpo vem inteiro e sem codificação. `X-Content-Type-Options: nosniff`, `Cache-Control`, CSP
da landing, 302 do sistema para o login: inalterados. **Produção:** a auditoria mediu `text/javascript` sem
compressão em 22/10; a correção só vale lá depois da publicação, e não foi conferida no servidor.

### Custo de carga (antes × depois)

`medir.cjs`: cache frio, 6 s após a navegação, bytes transferidos (corpo + cabeçalhos) e CPU (`TaskDuration` e
`ScriptDuration` do CDP, tarefas longas). Celular = 390 × 844, toque, CPU 4× mais lenta; sem limitação de rede. "Antes"
é o build de `c9e95b1` servido pelo PHP embutido (sem gzip); "depois" é este build, no PHP embutido (mesma condição) e
no Apache do contêiner (com gzip). Uma execução por linha; o "antes" foi repetido e variou menos de 5%.

| Cenário | Antes: KB / JS KB / tarefas ms / longas | Depois (PHP): KB / JS KB / tarefas ms / longas | Depois (Apache gzip): KB / JS KB / tarefas ms |
|---|---|---|---|
| Desktop `/` | 908 / 739 / 1.252 / 4 (842 ms) | 1.014 / 741 / 1.192 / 4 (749 ms) | 435 / 208 / 1.205 |
| Desktop `/#sistema` | 930 / 739 / 290 / 2 (131 ms) | **419 / 123 / 229 / 1 (71 ms)** | 299 / 49 / 188 |
| Celular `/` | 863 / 739 / 2.468 / 4 (1.042 ms) | 958 / 741 / 2.525 / 4 (1.017 ms) | 380 / 208 / 2.591 |
| Celular `/#sistema` | 885 / 739 / 1.976 / 2 (377 ms) | **341 / 123 / 747 / 1 (203 ms)** | 221 / 49 / 720 |

Leitura: a entrada por âncora deixou de pagar o 3D (−617 KB de JS e ~1,2 s de tarefas no celular). Na entrada pela
hero, o custo de CPU é o mesmo de antes — é a mesma cena —; a transferência cai pela compressão. As imagens subiram
(~93 → ~170 KB no desktop em `/`): mais capturas abaixo da dobra (lazy) e a foto da hero (29 KB). Não é Lighthouse nem
aparelho físico.

### Comandos executados

```bash
php -l landing/pagina.php
php -l landing/config.php
php -l landing/public/index.php
npm ci
npm run build:landing
docker compose -p pf-landing up -d --build
docker compose -p pf-landing exec app apachectl configtest
```

Os três `php -l` sem erros; `npm run build:landing` sem avisos (manifest com `pagina-DR8XDeby.js`,
`cena3d-DNsL0XS0.js`, `landing-DjYD9PlQ.css`). O código da aplicação (PHP do sistema, Twig, banco) não foi alterado,
por isso `composer check` não foi rodado.

## Capturas desta evidência

`2026-10-22-landing-atualizada/`: [hero desktop](2026-10-22-landing-atualizada/01-hero-desktop-1440.jpg),
[página inteira desktop](2026-10-22-landing-atualizada/02-pagina-desktop-1440.jpg) (movimento reduzido, cabeçalho
solto para a captura), celular: [hero 360×640](2026-10-22-landing-atualizada/03-celular-360x640-hero.jpg),
[palco](2026-10-22-landing-atualizada/04-celular-390-palco.jpg),
[O sistema](2026-10-22-landing-atualizada/05-celular-390-sistema.jpg),
[inventário](2026-10-22-landing-atualizada/06-celular-390-inventario.jpg),
[multas](2026-10-22-landing-atualizada/07-celular-390-multas.jpg),
[planejamento](2026-10-22-landing-atualizada/08-celular-390-planejamento.jpg),
[dados](2026-10-22-landing-atualizada/09-celular-390-dados.jpg), [menu](2026-10-22-landing-atualizada/19-menu-390.jpg);
mais as listadas na tabela de verificação.

## Não executado / limites

- Publicação e prova em produção (compressão, cabeçalhos, capturas no servidor): pendentes do fluxo do autor.
- Lighthouse, Safari, Firefox, leitor de tela real, aparelho físico e zoom real do navegador (o 200% foi simulado com
  `font-size` na raiz e o 400% com a janela de 320 × 256).
- As capturas vêm do ambiente local com o mesmo cenário sintético da demonstração publicada; números como "9/14" e
  "3,8 sinistros" são desse ambiente e da execução importada (dados até 31/07/2026, arquivo Infosiga de 20/10/2026).
- A foto é de 2006 (Commons); serve à identidade, não retrata a cidade atual. Uma foto própria do autor pode substituí-la
  (ADR-0018, "Trocar a foto").
- Os dois logs automáticos do conector Playwright MCP que a auditoria deixou na pasta principal do projeto
  (`.playwright-mcp/console-2026-10-22T17-14-06-433Z.log` e `page-2026-10-22T17-14-06-908Z.yml`) continuam lá, não
  rastreados, para o autor conferir e remover.
