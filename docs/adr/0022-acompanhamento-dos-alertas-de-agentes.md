# ADR-0022 — Acompanhamento dos alertas de agentes: ciência, responsável, providências e motivo de encerramento

Data: 24/10/2026 · Situação: aceita · Relacionadas: [ADR-0005](0005-estado-atual-versus-historico.md),
[ADR-0014](0014-acesso-somente-com-conta-cadastrada.md)

## Contexto

Quando um agente fica sem posição recente, o operador registrava ciência e não tinha mais o que fazer na ficha: não
havia onde anotar o que foi feito, quem acompanha nem quando conferir de novo; "Mudar situação" ficava abaixo de muitos
alertas antigos, e o limite de 20 alertas podia esconder uma pendência antiga. Análise e critérios de aceite do Codex:
`~/.codex/entregas-para-claude/promotheus-atendimento-alertas-agentes-2026-10-24/` (fora do repositório).

Havia também uma divergência: `AlertRules` usava `isActive()` (só "Em atividade") para `SEM_POSICAO`, enquanto ficha e
central usam `isOnShift()` (atividade e pausa). Entrar em pausa encerrava o alerta sem posição nova e o retorno abria
outro — daí a repetição de alertas "resolvidos" na ficha do Hermes 06.

## Decisões

- **Regra de turno única.** `SEM_POSICAO` vale em turno (atividade ou pausa), com a mensagem "em turno sem posição
  recente". Alertas antigos mantêm a mensagem gravada.
- **Motivo de encerramento** em `ALERT.RESOLVED_REASON`, decidido na reconciliação (`AlertRules::resolutionReason`):
  `POSICAO_RECEBIDA` (seguia em turno, então chegou posição recente), `FORA_DE_TURNO` (saiu de turno: a condição deixou
  de se aplicar, sem indicar que a comunicação voltou), `ARQUIVADO` e `CAUSA_ENCERRADA` (demais alertas). Alertas
  encerrados antes da migração ficam nulos e a ficha diz "motivo específico não registrado". Nada antigo é reescrito.
- **Responsável é uma conta de `APP_USER`, pelo ID** (`ALERT.ASSIGNEE_ID`), escolhida entre as contas **ativas** lidas
  a cada tela (`UserRepository::assignable`): uma conta criada em Contas aparece sem mudar código. Operador e
  administrador podem ser responsáveis, porque ambos operam o sistema. O servidor recusa conta inexistente ou
  desativada. Uma conta desativada depois continua ligada ao alerta e aos registros, marcada "conta desativada:
  reatribua". O agente acompanhado, quem registrou ciência e o responsável são três coisas distintas; ciência não
  atribui ninguém ("Sem responsável" até uma atribuição explícita).
- **Atribuição em formulário próprio**, disponível antes e depois da ciência. A orientação sugeria o responsável dentro
  do formulário de providência; com dois seletores de conta na mesma tela, trocar o responsável sem registrar uma
  providência ficaria confuso. Ficou um seletor só, logo acima da providência, com os três fatos (ciência, responsável,
  próxima conferência) visíveis ao lado.
- **Providências só depois da ciência**, em `ALERT_FOLLOWUP` (só leitura e inserção para a aplicação, como os demais
  históricos): resultado (contato tentado, contato confirmado, comunicação verificada, outra), relato (obrigatório em
  "outra"), autor e instante pelo servidor, próxima conferência opcional (depois de agora e em até 7 dias, hora de
  Marília). Cada providência redefine a próxima conferência; vazia = não definida. Atribuições também vão para
  `ALERT_FOLLOWUP` (de qual conta para qual, por quem, quando). `ACK_NOTE` continua sendo só a nota da ciência.
- **Concorrência por versão.** `ALERT.FOLLOWUP_NO` sobe a cada atribuição ou providência; o formulário envia a versão
  lida e o servidor trava a linha (`WITH LOCK`). Versão antiga → 409 com a digitação mantida e a versão atual no
  formulário (reenviar é decisão consciente). Alerta encerrado no meio do preenchimento → 409 explicando o motivo.
  Ciência registrada por outra pessoa no meio → a nota digitada passa para o formulário de providência.
- **Próxima ação centralizada** em `AlertFollowUp::nextAction` (domínio), usada pela ficha do agente e pela ficha resumida
  da central (`next_action` no JSON). Twig e JavaScript só apresentam. "Conferência vencida" é lembrete calculado pelo
  prazo, não diagnóstico do agente; contato confirmado mostra "ainda aguardando posição recente".
- **Mudar situação pede motivo** (origem `MANUAL`); o simulador continua sem motivo obrigatório. A mensagem de sucesso diz
  quando a saída de turno encerrou o alerta.
- **Pendentes sem limite.** A ficha lê os pendentes separados dos encerrados; o limite de 20 vale só para os encerrados
  (com o total ao lado). `forResource` mantém a mesma garantia para a ficha de equipamentos.
- **Demonstração:** "Simular recebimento de posição" (em "Mais ações") grava uma posição fictícia perto da base pelo
  `EventIngestor` e reconcilia na mesma operação; o histórico registra "recebimento simulado pela demonstração" e quem
  acionou. Não envia mensagem. É pontual: o simulador continua sem transmitir pelo Hermes 06, então o alerta volta depois
  de 30 min — não é retomada da transmissão.
- **Missão vigente** aparece na ficha com link para Planejar reforço; nada muda nela automaticamente.

## Consequências

- Migração incremental `0009_acompanhamento_de_alertas.sql` (colunas novas em `ALERT` e tabela `ALERT_FOLLOWUP`); aplicada
  num banco populado sem mudar linhas existentes; segunda execução sem efeito.
- Agentes em pausa sem posição há mais de 30 min passam a ter alerta (antes ficavam sem). No simulador as pausas duram
  em média ~3 min, então isso só aparece em pausas longas ou com o simulador parado.
- O acompanhamento ficou na ficha do agente e na ficha resumida da central. Equipamentos continuam só com ciência; a
  estrutura (`ALERT_FOLLOWUP`, `AlertService::assign/recordFollowUp`) não depende do tipo de recurso, se um dia for
  preciso. Uma página própria de acompanhamentos fica para quando houver volume.
