# Evidência — revisão de lógica e fluxos (R01–R08), 24/10/2026

Decisões: [ADR-0023](../adr/0023-revisao-de-logica-e-fluxos.md). Relatório de origem (Codex, fora do repositório, mantido
intacto): `~/.codex/entregas-para-claude/promotheus-revisao-logica-fluxos-2026-10-24/00-RELATORIO.md`.

Ambiente: Windows 11, Docker Desktop 29.7.2, projeto Compose isolado `pf-rev` (porta 8097; PHP 8.5.11, Firebird 5.0.4)
sobre esta pasta, branch `claude/revisao-logica-fluxos-1024` criada de `main` em `6dd9eea` (que já contém o
acompanhamento de alertas de agentes, ADR-0022, preservado). Banco de verificação com o perfil completo (48
equipamentos, 21 agentes, 40 multas). Navegador: Chromium 1248 headless via Playwright 1.56.1
([`revisao-fluxos.cjs`](../../scripts/verificacao-interface/revisao-fluxos.cjs)), com conta de teste descartável criada
por `user:create` nesta rodada; nenhuma conta existente teve a senha alterada.

## Avaliação de cada achado

Todos foram reproduzidos no código de `6dd9eea` antes de mudar qualquer linha (leitura dos caminhos citados; as
sondagens em memória do Codex foram usadas só para localizar os gatilhos). Nenhum já estava corrigido.

| ID | Avaliação | Mudança | Validação | Limite restante |
|---|---|---|---|---|
| R01 | Confirmado: `update()` não reconferia `FACT_DATES` | Retificação recusada no campo da data, nomeando cada fato e o limite; ajuda do campo mostra o limite antes; link para a situação da multa em outra aba | `FinesTest::testRetificationCannotPlaceTheOccurrenceAfterFactsAlreadyRecorded` (nada gravado; mesmo dia vale; vencimento não conta); HTTP 422 com digitação mantida; navegador na multa 573 (1440 e 390/360 px) | — |
| R02 | Confirmado: abrir permitido em toda situação, iniciar recusado em 3 | `EquipmentStatus::maintenanceStartBlocker()` no serviço, na ficha (próxima ação, ordem, formulário) e no quadro | `DomainRulesTest` (impedimento = tabela de transições), `MaintenanceInvariantsTest` (3 recusas com o pré-requisito, ordem e situação intactas), HTTP e navegador (ordem aberta pela ficha de um não instalado) | — |
| R03 | Confirmado: `ROWS 30` sobre todas as situações | Pendentes todas + 30 encerradas, total explícito; fila leva a `#ordem-N` | `MaintenanceInvariantsTest` e HTTP com 1 agendada + 31 encerradas; navegador: link da fila → ordem → cancelada pela ficha | Encerradas além de 30 ficam só como contagem (histórico de eventos da ficha tem limite próprio de 40) |
| R04 | Confirmado: projeção só da conferência `OCORRENCIA`; `<` em vez de `<=` | Lembrete da última conferência anotada, de qualquer momento (migração 0010); conta a partir do próprio dia; filtro "Só as que estão a conferir" | `FineRulesTest` (véspera × dia, origem do lembrete), `FinesTest::testLaterCheckReminderFollowsUpFromItsDayAndTheLastCheckDecides` (02:59 × 03:00 UTC, ficha, lista, resumo, filtro, "Não precisa", nova identidade); navegador na multa 572 | — |
| R05 | Confirmado: `catch` afirmava "nada foi registrado" | "Resultado não confirmado" + reconferência automática e "confira o resultado" quando o retrato chega | Navegador: a requisição chega ao servidor e a resposta é cortada (`route.fetch` + `abort`): aviso honesto, equipamento de fato em falha no retrato seguinte | Só a central faz ações por `fetch`; os formulários comuns recarregam a página |
| R06 | Lacuna confirmada | `POST /contas/{id}/senha`, migração 0011 (`CREDENTIAL_NO`, autoria, instante), orientação no login e em Contas | `ReviewFlowsHttpTest` (mesma conta, sessão antiga encerrada, senha anterior recusada, operador 403, própria conta recusada, senha fora da sessão e do histórico); navegador com conta de teste criada e desativada no percurso | Só a última redefinição fica registrada na conta |
| R07 | Confirmado (acima de 20 confirmadas) | Vigentes todas + 20 encerradas; `#missao-N`; ficha do agente leva à missão | HTTP com 22 confirmadas; navegador com 22 (preparadas por [`preparar-revisao.php`](../../scripts/verificacao-interface/preparar-revisao.php)): ficha do agente → missão → concluída | — |
| R08 | Confirmado (clareza) | "Operacionais · N com sinal", sem mudar cálculo | HTTP com um operacional sem sinal; navegador: 39/44 operacionais e 1 com sinal com o simulador parado | — |

## Verificações

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **278 testes, 1842 asserções, OK** (eram 267) |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` · `npm run build` (Node 24) | 0 erros · 0 de 131 · sem erros (mudam `app` e `central`) |
| Migrações 0010 e 0011 no banco populado | backup gbak antes; contagens iguais antes e depois; lembrete existente (#571) preservado como `OCORRENCIA`; segunda execução sem efeito |
| Navegador (`revisao-fluxos.cjs`) | 35 verificações OK, 0 erros de console e de CSP; 1440, 390 e 360 px sem rolagem lateral, movimento reduzido no celular, foco visível em "Redefinir senha" |
| Site publicado | **não verificado** (não implantado) |

Capturas: [`capturas-2026-10-24-revisao-logica-fluxos/`](capturas-2026-10-24-revisao-logica-fluxos/) — a senha nova
aparece mascarada na captura de Contas.

Os dados criados no banco de verificação (câmeras e agentes "Revisão", ordens, missões, conferências na multa 572, uma
conta de teste desativada) ficaram arquivados, cancelados ou desativados por `preparar-revisao.php --limpar` e pelo
próprio percurso; nada foi apagado.
