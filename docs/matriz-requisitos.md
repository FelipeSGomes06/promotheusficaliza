# Matriz de requisitos

Requisito → onde está (fluxo/documento) → como foi verificado → o que falta. IDs do
[planejamento original](planejamento-codex-2026-10-18/01-requisitos-e-escopo.md); `AUD-*` são os achados e critérios
da auditoria de 18/10/2026. Estado em 18/10/2026.

A extensão "Planejar reforço" (`ML-*`, critérios de `ml/marilia-setores/02-ESPECIFICACAO-PARA-CLAUDE.md`) foi
acrescentada em 20/10/2026, e a aba Multas (`MUL-*`) em 21/10/2026 — seções no fim.

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
| INV-07a Base do agente pelo mapa ou pelo endereço (coordenadas nunca digitadas); rua pelo CEP; bairro do ponto ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)) | Cadastrar agente, etapa 2 | T `AgentBaseTest`, `CepLookupTest`, `AddressLocatorTest`; H `AgentsHttpTest` (entradas inválidas sem chamada externa, sem login, falha do provedor, cadastro sem mapa); N `agentes.cjs` | Chamadas reais ao ViaCEP e ao Nominatim não verificadas aqui (rede bloqueada) |
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

## Extensão — planejamento preventivo por setores (20/10/2026)

[ADR-0016](adr/0016-planejamento-preventivo-por-setores.md) · [método e resultados](planejamento-por-setores.md) ·
[evidência](evidencias/2026-10-20-planejar-reforco.md). Capturas citadas: `2026-10-20-planejar-reforco/capturas/`.

| ID | Fluxo / documento | Verificação | Pendência |
|---|---|---|---|
| ML-01 Importar a execução com 4 setores e precisão | `php bin/fiscaliza urban:import`; `DECFLOAT(34)` | T `UrbanImportTest::testReferenceRunIsValidAndKeepsEveryDigit`, `PlanningTest::testImportPersists…` (valor do banco === valor do JSON); M saída do comando | — |
| ML-02 Reimportar sem duplicar; inválidos sem escrita parcial | Validação completa antes da transação; SHA-256 do conjunto | T 18 casos em `UrbanImportTest` (truncado, versão, setor, NaN, INF, negativo, geometria…), `PlanningTest::testInvalidRunLeavesNothingWritten`; M dois conjuntos adulterados | — |
| ML-03 Previsão, método, período, referência; contagem × prioridade × incidente | Topo de Planejar reforço e "Como ler" | T `PlanningHttpTest`; N `01`, `11` | — |
| ML-04 Métodos identificados e comparáveis; ordem muda com o método | Seletor de método; tabela de comparação | T `PlanningTest::testPoissonComparison…` (semana em que a prioridade troca), `PlanningHttpTest`; N `06` | Na semana atual os dois métodos têm a mesma ordem; a troca aparece no retrospecto |
| ML-05 Retrospecto com previsões congeladas, todas as semanas e setores | `/planejar/retrospecto` | T `PlanningTest::testReplayShowsFrozen…` (51 semanas, valores = arquivo, modelo de 01/01/2025, oculto até revelar); N `07`–`10`, `16`, `17` | — |
| ML-06 Sugestão exclui outros papéis, arquivados, fora de atividade e reservados; posição antiga explícita | Sugerir agente | T `PlanningTest::testSuggestionOnlyListsFreeTrafficAgents…`; N `04`, `14` | — |
| ML-07 POST com autenticação, autorização e CSRF; autoria e elegibilidade no servidor | Rotas de operador + `CsrfGuard` | T `PlanningHttpTest::testConfirmingAMissionNeedsCsrf…` (403, 401, guarda forjado, autor da sessão), `WebSecurityTest` (telas exigem conta) | — |
| ML-08 Duas confirmações simultâneas → uma reserva; concluir/cancelar libera com histórico | `UX_URBAN_MISSION_ACTIVE` + trava da linha do agente | T processos paralelos, 3 rodadas; índice único direto; `testClosingReleases…` | — |
| ML-09 Simulador não remove reserva nem altera a missão | Missão fora da tabela `AGENT` | T 40 ciclos do simulador, linha da missão idêntica | — |
| ML-10 Desktop e celular; estados vazios/erro; não depender de cor | Lista = mapa; posições escritas; selos com texto | T estado vazio; N `01`–`17`, 375 px sem rolagem lateral | Leitor de tela não testado; retângulos do mapa sem foco de teclado (a lista cobre) |
| ML-11 Testes, estilo, análise estática, builds, capturas, importação reproduzível | — | M 140 testes / 672 asserções, PHPStan 0, CS 0, build idêntico Node 22 × 24; treino reproduzido a partir da cópia | `TREINAR.cmd` em máquina limpa não executado |
| ML-12 Documentação, autoria, atribuição; sem promessa de probabilidade, causa ou precisão não medida | README, guia, este documento, [IA e autoria](ia-e-autoria.md), [licenças](licencas-e-fontes.md), avisos globais | M revisão do texto | — |
| Pedido de 20/10/2026 — grade de 2 km com 1 a 4 quadrados | Seção "Onde concentrar o reforço" ([método](planejamento-por-setores.md#quadrados-de-2-km-dentro-dos-setores)); experimento em `ml/marilia-grade-2km/` | T `UrbanImportTest` (grade confere com a execução; 4 arquivos fora do contrato), `PlanningTest::testGridIsAdded…`, `PlanningHttpTest` (1–4, padrão 4); N `18`, `19`; M teste único em 2026 | Missão continua por setor |
| Pedido de 20/10/2026 — atualização mensal com 12 meses | `urban:update` + serviço `atualizador` ([método](planejamento-por-setores.md#atualização-automática-mensal-e-semanal)) | T `UrbanUpdaterTest` (PHP reproduz a referência, filtros, idempotência, recusas, falha de download, paridade do Poisson); M download real (12 s, arquivo idêntico); N `20` | Revisões grandes do Infosiga só geram aviso |

## Extensão — aba Multas (21/10/2026)

[ADR-0017](adr/0017-aba-multas.md) · [guia](guia-de-uso.md#multas-multas) · [rotas](http.md#multas) ·
[evidência](evidencias/2026-10-21-multas.md). IDs do pacote de planejamento `promotheus-multas-2026-10-20` (documento 05).
Capturas citadas: `2026-10-21-multas/capturas/`. Testes: `FineRulesTest` (U), `FinesTest` (T), `FinesHttpTest` (H).

| ID | Fluxo / documento | Verificação | Pendência |
|---|---|---|---|
| MUL-01 Multas no menu; visitante sem dados; conta desativada perde acesso | Menu entre Agentes e Manutenção | H rotas sem conta, CSRF, `Origin`, conta desativada; N menu ativo | — |
| MUL-02 Rascunho → completar → registrar; código interno ≠ AIT | Cadastro + ficha (próxima ação) | T `testDraftIsCompleted…`; H fluxo completo; N `02`, `06` | — |
| MUL-03 Radar do inventário, agente do cadastro, recusa de origem manipulada, radar + operador | Etapa 2 do formulário | T `testSourceIsChecked…` (câmera, guarda, radar com origem Agente), snapshot com operador | — |
| MUL-04 Veículo autuado fora do inventário; zeros do RENAVAM | Etapa 3; colunas da multa | T zeros após salvar/reabrir/buscar; nenhum `EQUIPMENT` criado | — |
| MUL-05 RENAVAM ausente justificado registra; malformado → erro por campo | Etapa 3 | T `testRenavamKeeps…`; H 422 com link | — |
| MUL-06 Não verificada; regularidade com fonte/referência; pendência com descrição; sem consulta externa | Ficha → conferência | T `testDocumentChecks…`; H 422 de pendência; N `08` | — |
| MUL-07 Posterior não corrige a ocorrência; lançamento atrasado não substitui; retificação pede nova conferência | `FineDocumentStatus::select` + `SUBJECT_VERSION` | U seleção; T conferências; N `08`, `10` | — |
| MUL-08 Três velocidades distintas; nulo ≠ zero; sem tolerância inventada | Bloco de radar | U coerência; T nulos e registro recusado mantendo o rascunho | — |
| MUL-09 Pagamento + recurso coexistem; encerramento/cancelamento não simulam decisão | Ficha → andamento; Mais ações | T andamento e cancelamento; N `07`; `DemoProfileTest` (caso 8) | — |
| MUL-10 Autor/origem/instante/motivo com antes/depois; web não altera históricos | `FINE_EVENT`, `FINE_DOCUMENT_CHECK`, `Migrator::APPEND_ONLY` | T `UPDATE`/`DELETE` negados, inclusive após `GRANT` indevido; N `09` | — |
| MUL-11 Duas abas sem perda; repetição sem duplicar | `VERSION_NO` + `command_uid` | T versão, reenvio, outros dados, outra conta, processos paralelos; H duplo envio; N `05` | — |
| MUL-12 Lista, total e três resumos do mesmo filtro; paginação; sem duplicar por JOIN | `FineRepository::search` (SNAPSHOT) | T 24 multas, 3 conferências + eventos numa delas, filtros; N resumos = linhas | — |
| MUL-13 Sem JS, teclado, celular, movimento reduzido | Formulários comuns; `pages/multas.js` | H fluxo só com formulários; N sem JS, skip link, 390/360 px, zoom 200% (720 px), `prefers-reduced-motion` e `pf-motion` | Leitor de tela não testado |
| MUL-14 CSP, prefixo, builds, telas existentes | — | H sem inline/CDN, prefixo vazio; N 0 violações; M build idêntico Node 24 × 22; suíte inteira OK (teste do retrospecto atualizado aos textos do redesenho) | — |
| MUL-15 Migração vazia/existente; seed não destrutivo repetível; reset preserva contas | `0007_multas.sql`, `demo:seed-multas`, `DemoSeeder::wipe` | M banco da versão anterior atualizado sem reset (contagens iguais), segunda execução sem efeito, carga 2× sem duplicar; T recusa sem marcador, conflito de versão, manual preservado | Banco publicado não atualizado |
