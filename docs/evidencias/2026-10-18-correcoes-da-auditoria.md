# Correções da auditoria e redesenho — 18/10/2026

Ambiente: Windows 11 + Docker Desktop 29.7.2; contêineres `php:8.5.11-apache-trixie` (PDO_FIREBIRD) e
`firebirdsql/firebird:5.0.4-trixie` (Firebird 5.0.4). Tudo rodou num **projeto Compose isolado** (`-p fiscaliza-claude`,
porta 8091, volume próprio), sem tocar no banco de demonstração do projeto padrão. Dados sintéticos e conta de teste
descartável criada só nesse ambiente.

A auditoria (Codex, 18/10/2026) partiu de 62 testes / 218 asserções verdes e reproduziu, fora do projeto, cinco
defeitos funcionais e um achado de segurança de baixa gravidade. Linha de base reproduzida aqui antes de alterar:
**62 testes, 218 asserções, OK**; os 164 arquivos próprios coincidiam com o manifesto da auditoria.

## Achados, correções e regressões

| Achado | Causa | Correção | Regressões (todas passam) |
|---|---|---|---|
| OP-01 — concluir uma ordem liberava o equipamento com outra em execução | Início permitido com intervenção já em execução | Uma intervenção em execução por equipamento: serviço + índice parcial `UX_MAINT_ONE_RUNNING`; mudança manual de situação recusada durante a intervenção; bloqueio sempre equipamento → ordem ([ADR-0009](../adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)) | `MaintenanceInvariantsTest`: segunda intervenção recusada e liberada após a conclusão; banco recusa duas `EM_EXECUCAO` mesmo fora do serviço; situação manual recusada; **três inícios concorrentes em processos separados → exatamente um** |
| OP-02 — arquivar durante intervenção deixava a ordem sem saída | Arquivamento sem olhar ordens | Recusa com intervenção em execução; agendadas só com confirmação explícita, canceladas com motivo, autoria e histórico | Arquivamento recusado e ordem concluível depois; cancelamento só com confirmação e com autoria; **iniciar × arquivar concorrentes, 3 rodadas → nunca ordem sem saída** |
| OP-03 — posição recebida invalidava edição de marca/modelo | `updateState` incrementava `VERSION_NO` para posição/situação | `VERSION_NO` = versão do cadastro (só colunas do formulário e arquivamento); conflito real mostra "salvo agora × sua versão", mantém a digitação e passa a versão atual ([ADR-0010](../adr/0010-versao-do-cadastro-separada-da-telemetria.md)) | `InventoryTest`: posição + telemetria + situação de agente durante a edição não geram conflito; instalação gera; diferenças normalizadas; `WebSecurityTest`: conflito HTTP mantém digitação, mostra o salvo e salva na segunda tentativa; teste antigo de duas edições humanas continua passando |
| OP-04 — periodicidade de hoje entrava num corte do passado | Cortes usavam o cadastro atual | Periodicidade vigente reconstruída pelo histórico; eventos só se recebidos antes do corte; população inclui quem foi arquivado depois ([analise-preventiva.md](../analise-preventiva.md#dados-usados-e-separação-temporal)) | `CentralAndAnalysisTest`: 90 dias no corte mesmo após mudar para 7; falha recebida depois do corte é desconhecida nele; arquivado depois continua no corte |
| OP-05 — F1 nulo com TP=0, FP=1, FN=1 | F1 a partir de precisão e revocação arredondadas | F1 = 2TP/(2TP+FP+FN), sem arredondamento intermediário; `null` só com denominador zero | `AttentionModelTest`: erros sem acerto = 0,0; vazio, sem falhas/alertas, só falsos alertas, só omissões; 4/9 exato |
| SEC-01 — cota de login não atômica (baixa, CWE-362) | Contagem antes do hashing, gravação depois | Vaga reservada antes do hashing com índice único parcial; quitação por linha nova ([ADR-0011](../adr/0011-cota-de-login-atomica.md)) | `LoginQuotaTest`: reserva em andamento conta; login correto quita; **4 tentativas concorrentes (conta existente e inexistente) → 1 verificação e 3 bloqueios, total 5**; **HTTP com duas sessões e CSRF válido obtido do formulário → 401 + 401, uma "inválidos" e uma "muitas tentativas", total 5**; logins corretos repetidos com falhas antigas |

Proteções operacionais (não são vulnerabilidades exploráveis): `scripts/avaliar-sementes.php` com o marcador sintético
e mensagem do que substitui; testes destrutivos recusam `DB_TEST_DSN` igual a `DB_DSN` (DSN e arquivo físico) —
`DatabaseSeparationTest`; web e simulador sem credenciais de dono/SYSDBA na configuração e no ambiente do processo;
trava real contra sobreposição do simulador (`OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock`).
Também corrigido: resultados de equipamentos fora de serviço gravados como `BAIXA` passam a `NAO_AVALIADO`; furto
explicado como corretiva/reposição.

**Contraprova com os testes originais da auditoria** (`AuditRegressionTest.php`, executado sem alterações contra o
código corrigido, numa pasta temporária fora do repositório): OP-02, OP-03 e OP-05 passam; o teste de OP-01 agora
para no segundo início com `RuleViolation: A ordem #1 já está em execução neste equipamento…` — é a regra escolhida
(a sequência da auditoria deixou de ser possível), não regressão. O teste temporal original chama `features()` sem
carregar o histórico e foi substituído pelo equivalente com `load()`.

## Defeitos encontrados durante a verificação (e corrigidos)

| O que apareceu | Onde | Correção |
|---|---|---|
| Segundo login correto do mesmo usuário dava 500: o Firebird trata `(chave, NULL)` repetido como duplicata no índice único composto; a migração também falharia num banco com falhas antigas | Navegação real no ambiente isolado | Índice parcial `WHERE SLOT_NO IS NOT NULL`; teste novo de logins repetidos com linhas antigas |
| Simulador (`www-data`) não abria `var/simulador.lock` criado por outro usuário | Log do simulador | Abrir só para leitura basta para `flock` |
| Enter em marcador do mapa não abria a ficha (o Leaflet trata só `keypress`) | Teste de teclado no navegador | Marcadores e grupos tratam Enter/Espaço em `keydown` |
| Borda âmbar do marcador "em manutenção" (2,4:1) e borda dos campos (1,8:1) abaixo de 3:1 | Medição de contraste | `#a87b00` (3,8:1) e `--control-line #7d8896` (3,6:1) |

## Migração 0001 → 0002 com dados antigos

Banco novo com só a 0001, três falhas de login antigas para a mesma chave e, em seguida, a 0002 completa:
`aplicadas: 0001_esquema_inicial` → `falhas legadas: 3` → `aplicadas: 0002_invariantes_e_cota_de_login` →
`falhas na janela após 0002 (3 legadas + 1 reserva): 4`. GRANTs reaplicados sem erro.

## Comandos finais (18/10/2026, 21:20 UTC)

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` (unidade + integração Firebird + HTTP) | **89 testes, 314 asserções, OK** (~33 s) |
| Testes concorrentes repetidos (`--filter 'Concurrent\|Racing'`, 3 execuções) | 3 × OK (7 testes, 20 asserções cada) |
| `vendor/bin/phpstan analyse` (nível 6) | 0 erros |
| `vendor/bin/php-cs-fixer fix --dry-run` | 0 de 85 arquivos a corrigir |
| `composer validate --strict` · `composer audit` | válido · nenhum aviso de segurança nas bases consultadas (não prova ausência de vulnerabilidades) |
| `php scripts/avaliar-sementes.php 2027 … 2031` | Reavaliação registrada em [avaliação das sementes](2026-10-18-avaliacao-sementes.md#reavaliação-após-as-correções-op-04-e-op-05-18102026) |

## Verificação no navegador

Chrome headless pelo DevTools Protocol ([`scripts/capturar-telas.mjs`](../../scripts/capturar-telas.mjs)) e navegador
embutido do Claude Code. Playwright, pressuposto pela skill `webapp-testing`, não estava instalado; o script cumpre o
mesmo papel (navegar, agir, medir o DOM, capturar, ler o console) sem dependências.

| Critério | Medida |
|---|---|
| 390×844, modo mapa | cabeçalho 57 px; mapa começa em **y≈475 px** (antes: y≈825 px); ação mapa/lista e resumos na primeira tela; sem rolagem horizontal em nenhuma das 22 telas |
| 1440×900 | uma ação principal ("Ver o que precisa de atenção"), três resumos, filtros essenciais; mapa em y≈411 |
| Atualização periódica com o simulador ligado | com foco numa linha da lista, 13 s e novos dados depois: foco mantido; com ficha aberta: mesma seleção e mapa parado (transformação do painel do Leaflet idêntica) |
| Teclado | `Tab` percorre pular-conteúdo → marca → aviso de demonstração → menu; Enter num marcador abre a ficha com foco no título; `Esc` fecha e devolve o foco ao marcador; menu do celular abre com foco no primeiro link e fecha com `Esc` devolvendo o foco ao botão |
| Console | nenhum erro ou aviso; só o `422` esperado ao enviar o formulário com erro |
| Movimento reduzido (`prefers-reduced-motion: reduce`) | sem animações nem transições (regra global), conteúdo idêntico |
| Origem dedicada (`compose.origem-dedicada.yaml`) | raiz, `/api/central`, assets e `/equipamentos` 200; caminho antigo 301 preservando caminho e consulta; cookie `path=/` |

Contraste (WCAG, calculado sobre os tokens): texto 16,2:1; secundário 6,9:1; links/ação 6,5:1; botão primário 6,5:1;
aviso de demonstração 7,5:1; erro 7,5:1; texto de aviso 6,7:1; glifos dos selos ≥ 4,7:1; bordas de controles 3,6:1;
foco 6,5:1. Não substitui auditoria com leitor de tela ou ferramenta automática (não executadas).

### Tarefas de iniciante (verificação feita por mim, sem pessoa iniciante)

Não houve teste de usabilidade com pessoa real. Percorri as cinco tarefas pedidas, no papel de quem nunca usou o
sistema, e registrei o que a tela oferece em cada passo:

| Tarefa | Caminho percorrido | Captura |
|---|---|---|
| Entender a central | Título "Visão geral de Marília", frase de uma linha, três resumos, aviso "Demonstração" no topo | `depois/01`, `02` |
| Localizar um radar | Tipo → "Radar de fiscalização" (12 no filtro) → tocar no primeiro da lista → ficha "Radar · Avenida das Esmeraldas…" | `depois/05` |
| Interpretar situação e atualidade | Ficha resumida: "Mau funcionamento", "Sinal atual · há menos de 1 min", "No local de instalação", motivo e alerta | `depois/05`, `06` |
| Encontrar uma manutenção | Resumo "manutenções urgentes" → Manutenção → "Em andamento" e "Urgentes", cada uma com motivo e efeito da ação | `depois/12`, `13` |
| Preencher e corrigir um cadastro | Radar com "Velocidade" e sem limite → resumo "Corrija o campo abaixo. O que você digitou foi mantido." → informar 50 km/h → "Equipamento cadastrado." | `depois/14`, `15` |

## Capturas

[`capturas-2026-10-18/antes`](capturas-2026-10-18/antes) (9 telas, mesma base de dados e viewports, antes do
redesenho) e [`capturas-2026-10-18/depois`](capturas-2026-10-18/depois) (22 telas). Mapa de fundo © colaboradores do
OpenStreetMap. Dados e nomes fictícios; a conta "Operador de teste (Claude)" existia só no ambiente isolado.

## Limites

- Sem teste com leitor de tela real, axe/Lighthouse ou pessoa iniciante.
- Publicação, DNS/TLS, redirecionamento no portal e `mod_remoteip` não executados.
- A trava do simulador vale para processos que compartilham o mesmo `var/`.
- Bancos anteriores à correção podem ter ordens abertas em equipamentos arquivados; não há conversão automática.
