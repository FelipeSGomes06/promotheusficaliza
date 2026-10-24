# ADR-0023 — Revisão de lógica e fluxos (24/10/2026): a tela oferece o que o serviço aceita

Data: 24/10/2026 · Situação: aceita · Relacionadas: [ADR-0009](0009-uma-intervencao-por-vez-e-arquivamento-explicito.md),
[ADR-0014](0014-acesso-somente-com-conta-cadastrada.md), [ADR-0016](0016-planejamento-preventivo-por-setores.md),
[ADR-0017](0017-aba-multas.md), [ADR-0022](0022-acompanhamento-dos-alertas-de-agentes.md)

## Contexto

A revisão de lógica do Codex (fora do repositório: `~/.codex/entregas-para-claude/promotheus-revisao-logica-fluxos-2026-10-24/`)
apontou oito pontos, R01–R08, fora do acompanhamento de alertas de agentes (ADR-0022, preservado). Os oito foram
reproduzidos no código de `main` em `6dd9eea` antes de qualquer mudança; nenhum estava corrigido. Três padrões se
repetiam: uma tela prescrevia uma ação que outra regra recusa; uma informação importante ficava fora do conjunto que a
tela usa para decidir; um texto prometia certeza ou acompanhamento que o comportamento não entregava. Avaliação item a
item e evidência: [evidência](../evidencias/2026-10-24-revisao-logica-e-fluxos.md).

## Decisões

- **R01 — Retificar a ocorrência confere os fatos já anotados.** Ao mudar `OCCURRED_AT`, `FineService::update` aplica a
  mesma regra do andamento: nenhum fato de `FACT_DATES` (avisos expedidos e recebidos, pagamento, protocolo e resposta
  da contestação, encerramento) pode ficar antes da nova data local. Vencimentos e prazos (`payment_due_on`,
  `defense_due_on`, `appeal_due_on`) ficam de fora, porque podem ser anteriores ou futuros. Com conflito, a retificação é
  recusada no próprio formulário (422, digitação mantida, erro no campo da data) nomeando cada data e o limite
  ("use uma data até…"), com a saída: corrigir antes a anotação na situação da multa, com motivo — que já guarda o valor
  anterior no histórico. Nada é ajustado em silêncio. A ajuda do campo mostra o limite antes do erro.
- **R02 — Uma regra de início só.** `EquipmentStatus::maintenanceStartBlocker()` diz, em palavras, por que uma ordem
  agendada não pode começar (não instalado, desativado, furtado) e é usada pelo `MaintenanceService::start` (recusa com o
  pré-requisito), pela ficha (próxima ação, cada ordem, formulário de abertura) e pelo quadro de Manutenção. Um teste
  unitário amarra o impedimento à tabela de transições. Agendar continua possível em qualquer situação (ADR-0009). Não
  instalado continua com "Registrar a instalação" como próxima ação, agora dizendo que a ordem fica agendada até lá;
  desativado com ordem passa a "Decidir sobre a ordem" (voltar a uso ou cancelar); furtado mantém "Decidir a reposição
  ou a baixa". Nenhuma transição foi liberada e nada recomenda marcar "Operacional" para destravar uma ordem.
- **R03 — Pendentes sem limite, encerradas com limite explícito.** `MaintenanceRepository::forEquipment` lê todas as
  ordens agendadas e em execução e só as 30 encerradas mais recentes; a ficha mostra "Mostrando as 30 encerradas mais
  recentes de N" quando há mais. Próxima ação, início, cancelamento e a confirmação do arquivamento usam o conjunto
  completo. A fila global leva direto à ordem (`#ordem-N`) em vez do topo da seção. Cada ordem aparece numa seção só.
- **R04 — Um lembrete em vigor por multa: o da última conferência anotada.** Cada conferência responde de novo "Quer ser
  lembrado de conferir de novo?"; vale a resposta da anotada por último (maior ID) na identidade atual do veículo, de
  qualquer momento — "Não precisa" retira o lembrete anterior, que continua no histórico. A migração 0010 renomeia
  `OCC_DOC_REVIEW_ON` para `DOC_REVIEW_ON`, acrescenta `DOC_REVIEW_SCOPE` (de qual momento veio) e recalcula a projeção.
  A situação no dia da multa (`OCC_DOC_STATUS`) e a regularidade posterior continuam separadas: o lembrete não muda
  nenhuma das duas. Uma correção de placa ou data (nova identidade) zera o lembrete, como as conferências.
  Escolhemos o lançamento, não o instante da conferência (`CHECKED_AT`, usado para a situação), para que a resposta que a
  pessoa acabou de dar valha sempre; com o instante, um lançamento atrasado perderia o lembrete escolhido em silêncio.
- **R04 — "Na data escolhida" significa no próprio dia.** O lembrete passa a contar a partir da data escolhida, na data
  local de Marília (`review_on <= hoje`), na ficha, no selo, nos motivos, no resumo "a conferir" e no filtro novo "Só as
  que estão a conferir" (link junto do resumo e caixa em "Mais filtros"), todos com a mesma condição. Antes contava só no
  dia seguinte. O texto do formulário diz isso e que a conferência nova substitui o lembrete atual.
- **R05 — Resultado desconhecido não é recusa.** Na central, uma falha de transporte (sem resposta HTTP) vira "resultado
  não confirmado" — "Não foi possível confirmar o resultado. Atualize os dados antes de tentar novamente." —, com
  "Atualizar agora"; a central recarrega o retrato na hora e, quando ele chega (inclusive depois de voltar a conexão),
  troca o aviso por "confira o resultado", dizendo de quando são os dados. Recusa do servidor (4xx/5xx com resposta)
  continua como erro, e sucesso só depois da confirmação. As proteções de cada comando não mudaram.
- **R06 — Redefinição de senha pelo administrador, na mesma conta.** `POST /contas/{id}/senha` (só administrador, CSRF,
  confirmação que descreve o efeito) gera 20 caracteres aleatórios, mostrados uma única vez como na criação; ID,
  usuário, papel, situação e todo o histórico continuam. A migração 0011 acrescenta `CREDENTIAL_NO` (versão da
  credencial, guardada na sessão no login: quando muda, as sessões abertas da conta terminam na próxima requisição, com
  aviso no login), `PASSWORD_RESET_AT` e `PASSWORD_RESET_BY` (instante e autoria, mostrados em Contas). A senha nunca vai
  para sessão, log, histórico ou documento. A própria senha não se redefine por aqui (encerraria a sessão de quem
  redefine): é pedido a outro administrador. Conta desativada pode receber senha nova, mas continua desativada. O
  bloqueio de 5 tentativas em 15 minutos não muda. O login diz o que fazer quando a senha se perde.
  Limite aceito: só a última redefinição fica registrada na conta (não há trilha de várias redefinições).
- **R07 — Todas as missões vigentes na lista.** `MissionRepository::recent` lê todas as confirmadas e só as 20 encerradas
  mais recentes. Cada missão tem âncora `#missao-N`, e a ficha do agente leva direto à dele ("Concluir ou cancelar em
  Planejar reforço"). O encerramento continua manual: sem calendário nem expiração automática.
- **R08 — "Operacionais · N com sinal".** O resumo da central troca "funcionando" por "operacionais" (o nome da
  situação registrada) e mostra, na segunda linha do mesmo cartão, quantos deles têm sinal recente — a diferença fica
  visível sem abrir os indicadores, e o cartão mantém as duas linhas dos vizinhos ("em situação operacional · N com
  sinal recente" quebrava em quatro). Cálculos e denominadores não mudaram; ausência de sinal
  continua não sendo falha (o painel de indicadores repete isso).

## Consequências

- Duas migrações incrementais (0010, 0011), sem perda de dados e idempotentes pelo registro de migrações; o banco
  populado de verificação manteve todas as contagens.
- Componentes reutilizados: próxima ação, `order__effect`, `flash`, `error-summary`, `kpi`, `checks`, avisos (toasts) e
  `data-confirm`. Nenhum CSS novo.
- O volume de R03 e R07 não existe na carga de demonstração; `scripts/verificacao-interface/preparar-revisao.php` o cria
  pelos serviços da aplicação (e `--limpar` cancela e arquiva o que criou), e `revisao-fluxos.cjs` percorre as telas.
