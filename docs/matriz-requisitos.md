# Matriz de requisitos

Requisito → onde está (fluxo/documento) → como foi verificado → o que falta. IDs do
[planejamento original](planejamento-codex-2026-10-18/01-requisitos-e-escopo.md); `AUD-*` são os achados e critérios
da auditoria de 18/10/2026. Estado em 18/10/2026.

Legenda da coluna Verificação: **T** = teste automatizado (Firebird real), **N** = verificação no navegador (capturas,
medidas, console), **M** = conferência manual por comando. "Implementado" não significa "publicado".

## Parte 1 — inventário

| ID | Fluxo / documento | Verificação | Pendência |
|---|---|---|---|
| INV-01 Diversas categorias | Equipamentos → cadastrar/editar/arquivar; [guia](guia-de-uso.md#equipamentos-equipamentos) | T `InventoryTest`, `MaintenanceInvariantsTest`; N `depois/08`, `14`, `15` | — |
| INV-02 Tipo, série, marca, modelo | Formulário etapa 1; [contratos](http.md#equipamento--post-equipamentos-e-post-equipamentosid) | T série única por fabricante (inclusive concorrente) | — |
| INV-03 Endereço / base | Formulário etapa 2 (mapa primeiro, coordenadas avançadas) | T `InputAndSpecsTest`; N `depois/14` | — |
| INV-04 Situação atual | Ficha → Mais ações; [domínio](dominio.md#situações-e-transições) | T transições, instalação | — |
| INV-05 Manutenção imediata/periódica | Manutenção; ficha | T ciclo, preventiva periódica, uma intervenção por vez | — |
| INV-06 Características por tipo | Etapa 3 do formulário; [ADR-0003](adr/0003-caracteristicas-por-tipo-em-json-validado.md) | T `InputAndSpecsTest`, `InventoryTest`; N erro de limite do radar | — |
| INV-07 Agentes | Agentes; base × última posição | T `InventoryTest::testMobileResource…`, status do simulador sem conflito | — |
| INV-08 Histórico e autoria | Histórico da ficha (tracejado explicado) | T eventos com autor; arquivamento preserva histórico | — |

## Parte 2 — central

| ID | Fluxo / documento | Verificação | Pendência |
|---|---|---|---|
| OPS-01 Mapa com equipamentos e agentes | Visão geral | N `depois/01`, `02`, `05` | — |
| OPS-02 Situação reconhecível | Selos com texto + símbolo; legenda; formas no mapa | N legenda; contraste medido | Leitor de tela real não testado |
| OPS-03 Indicadores | Três resumos + "Mais indicadores" | T `CentralAndAnalysisTest` (mesmo conjunto, denominador zero) | — |
| OPS-04 Filtros | Busca, tipo, situação + Mais filtros | T filtros e busca/atenção afetam tudo; N contagens | — |
| OPS-05 Manutenção urgente/próxima | Resumo e página Manutenção | N `depois/12`, `13` | — |
| OPS-06 Alertas | Ficha resumida e completa; "precisam de atenção" | T ciclo idempotente, concorrente | — |
| OPS-07 Detalhes a partir do mapa | Ficha resumida; `?selecionar=` | N teclado e clique; seleção mantida | — |
| OPS-08 Por região | "Mais indicadores" → tabela por bairro | T resumo por bairro (instantâneo) | — |
| OPS-09 Atualização contínua | Consulta a cada `POLL_INTERVAL_SECONDS`; simulador | N 13 s com simulador: foco, seleção e mapa preservados | Latência em rede real não medida |
| OPS-10 Simulação declarada | Aviso "Demonstração", Sobre, rodapé | N todas as telas | — |

## Parte 3 — preditiva

| ID | Fluxo / documento | Verificação | Pendência |
|---|---|---|---|
| PRE-01 Problema útil antecipado | Revisões recomendadas (7 dias) | T reprodutível | — |
| PRE-02 Explicar | Por quê / o que fazer; método e retrospecto | N `depois/16`, `17` | — |
| PRE-03 Método livre | Heurística `atencao-v1` ([ADR-0007](adr/0007-analise-preventiva-heuristica.md)) | — | — |
| PRE-04 Prioridade preventiva | [Análise preventiva](analise-preventiva.md) | T classes, F1, separação temporal | Sem dados reais |
| PRE-05 Insuficiência de evidência | "Histórico curto", "Não avaliado", "—" nas métricas | T `AttentionModelTest` | — |

## Pedidos do proprietário

| ID | Situação | Verificação | Pendência |
|---|---|---|---|
| USR-01 PHP · USR-02 Firebird | PHP 8.5.11, Firebird 5.0.4 | T suíte no Firebird real | — |
| USR-03 Código aberto | MIT, CI GitHub/GitLab | M CI reproduzida antes (verificacao.md) | Repositório público, push, varredura do histórico |
| USR-04 Preservar site principal | Nada do portal no projeto | M | Conferir cookies/CSP do portal antes de publicar |
| USR-05 Caminho `/promotheusfiscaliza` | Compose padrão; origem dedicada com redirecionamento | N/M local (prefixo e origem dedicada) | Publicação real |
| USR-06 Mapa de Marília | Visão geral | N | — |
| USR-07 Divertido e animado | Marca de trânsito, ilustrações, marcadores; movimento coordenado com GSAP (ficha, avisos, filtro com Flip, destaque finito do ponto, realce do dado recebido) e desligável ([ADR-0012](adr/0012-identidade-motion-e-build-de-assets.md)) | N navegador: 11 cenários de comportamento + vídeos ([evidência](evidencias/2026-10-18-identidade-e-motion.md)); movimento reduzido pelo sistema e por escolha | Opinião de pessoas usuárias não coletada |
| USR-08 Modelos dos pontos | Ícones e miniaturas próprias por categoria | N `depois/05`, `10` | 3D não feito (opcional) |
| USR-09 Documentação | README, guia, ADRs, contratos, evidências | M | — |
| USR-10 Acesso só com cadastro feito pelo administrador (19/10/2026) | Telas, mapa e API exigem conta; contas só em "Contas" ou `user:create` ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)) | T `WebSecurityTest`; N navegador sem e com conta, sessão encerrada → login ([evidência](evidencias/2026-10-19-identidade-com-acesso-por-conta.md)) | — |

## Auditoria de 18/10/2026

| ID | Situação | Verificação | Pendência |
|---|---|---|---|
| AUD OP-01 a OP-05, SEC-01 | Corrigidos | T + concorrência real ([evidência](evidencias/2026-10-18-correcoes-da-auditoria.md)) | — |
| AUD proteções operacionais | Corrigidas (sementes, banco de testes, credenciais por processo) | T `DatabaseSeparationTest`; M | `.env` legível no contêiner local montado |
| AUD DOC-01 Publicação | [operacao.md](operacao.md#publicação), `compose.origem-dedicada.yaml` | M local | DNS, TLS, redirecionamento no portal |
| AUD DOC-02 Cron | Diretório explícito + trava `var/simulador.lock` | T sobreposição | — |
| AUD DOC-03/04 Classes e promessa temporal | [analise-preventiva.md](analise-preventiva.md) | T | — |
| AUD DOC-05 Tracejado | Ficha e [domínio](dominio.md#eventos) | N | — |
| AUD DOC-06 Sobre | Intervalo do simulador × consulta separados | N `depois/21` | — |
| AUD backup / sementes | [operacao.md](operacao.md#backup-e-restauração) | M | Ensaio de restauração não repetido nesta etapa |
| Critério 1 — 390×844 | mapa em y≈475 | N | — |
| Critério 2 — 1440×900 | uma ação principal, três resumos | N | — |
| Critério 3 — mapa/lista coerentes | mesmo instantâneo e seleção | T + N | — |
| Critério 4 — rótulos de situação/atualidade | macros únicos; "sem informação recente ≠ falha" | N | — |
| Critério 5 — teclado, foco, contraste, toque | Enter/Esc, foco visível, ≥ 44 px nos controles principais, contraste medido | N | Leitor de tela e axe/Lighthouse não executados |
| Critério 6 — erros e conflitos | resumo de erros, digitação mantida, conflito recuperável | T + N | — |
| Critério 7 — demonstração e incerteza | aviso permanente discreto; "não é probabilidade" | N | — |
| Critério 8 — cadastro, radar, manutenção | tarefas percorridas | T + N | — |
| Critério 9 — movimento reduzido | regra global + preferência | N | — |
| Critério 10 — capturas e console | 9 antes / 22 depois, console sem erros | N | — |
