# ADR-0009 — Uma intervenção por vez e arquivamento sem ordens órfãs

Data: 18/10/2026 · Situação: aceita · Origem: achados OP-01 e OP-02 da auditoria de 18/10/2026

**Contexto.** Era possível iniciar duas ordens do mesmo equipamento; concluir a primeira como "operacional" liberava o
equipamento com a segunda ainda em execução. Também era possível arquivar durante uma intervenção: a ordem sumia do
painel, a conclusão falhava (equipamento arquivado) e o cancelamento só aceita ordens abertas.

**Decisão.**

- **No máximo uma intervenção em execução por equipamento.** A conclusão declara a situação real do equipamento; com
  duas intervenções simultâneas esse resultado seria ambíguo. Várias ordens podem continuar **agendadas** ao mesmo
  tempo; só o início é exclusivo. Alternativa descartada: agregar a situação entre várias intervenções (exigiria
  regra de precedência entre resultados e uma situação "parcial" que o domínio não tem).
- Garantia em duas camadas: o serviço recusa com mensagem clara ("A ordem #N já está em execução…") e o banco tem o
  índice único parcial `UX_MAINT_ONE_RUNNING` (`WHERE STATE = 'EM_EXECUCAO'`, Firebird 5), que vale para qualquer
  caminho e sob concorrência.
- Ordem única de bloqueio: equipamento e depois ordem, em abrir, iniciar, concluir, cancelar e arquivar. Operações
  concorrentes sobre o mesmo equipamento se enfileiram em vez de se travarem mutuamente.
- Mudança manual de situação é recusada enquanto há intervenção em execução (a conclusão define a situação).
- **Arquivar com intervenção em execução é recusado.** Ordens apenas agendadas só são canceladas se a pessoa confirmar
  explicitamente (`cancelar_agendadas=1`); cada cancelamento recebe o motivo do arquivamento, autoria e registro no
  histórico, e o evento de arquivamento lista as ordens canceladas. Nada é encerrado em silêncio.

**Consequências.** Disputas iniciar × iniciar e iniciar × arquivar terminam sempre com uma das operações recusada,
nunca com ordem sem saída (testes concorrentes em `MaintenanceInvariantsTest`, com processos separados). A migração
0002 falha se o banco já tiver duas intervenções em execução no mesmo equipamento; o comentário da migração traz a
consulta para encontrá-las. Bancos anteriores à correção podem ter ordens abertas em equipamentos arquivados; não há
fluxo automático para elas (ver docs/guia-de-uso.md, "Arquivamento").
