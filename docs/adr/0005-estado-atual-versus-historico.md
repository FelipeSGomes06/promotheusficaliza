# ADR-0005 — Estado atual separado do histórico de eventos

Data: 18/10/2026 · Situação: aceita

**Contexto.** Eventos podem chegar repetidos, atrasados ou contradizer uma decisão humana.
**Decisão.** Cada observação tem `EVENT_UID` único, instante ocorrido e recebido. Duplicata é ignorada e contada;
evento mais antigo que o estado atual vai só para o histórico; telemetria alterna apenas operacional/falha e não
sobrescreve estados administrativos; posição só avança. Alertas derivam das condições atuais, com chave única
enquanto abertos; reconhecer não resolve.
**Consequências.** Histórico explica cada mudança (append-only para a aplicação); estado atual nunca regride por
atraso. Sinais (heartbeat) não geram linhas de histórico, por volume.
