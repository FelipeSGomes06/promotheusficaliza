# Backup e restauração — 18/10/2026

Comando: `bash scripts/backup-restore-teste.sh` (Firebird 5.0.4 no Compose; simulador parado durante o backup).

- `gbak -b -g` como dono do esquema, pela rede interna: backup de 470 KB em `/var/lib/firebird/data/backup-<data>.fbk`
  (volume do contêiner — fora do Git e do alcance HTTP).
- Restauração **num banco separado** (`fiscaliza_restaurado.fdb`). Pela rede, o dono do esquema recebeu
  `no permission for CREATE access to DATABASE` — comportamento desejado, porque o papel ADMIN foi revogado no init.
  A restauração é tarefa do operador do servidor: o script usa o modo embarcado dentro do contêiner, como usuário
  do sistema `firebird`, mantendo o dono do banco restaurado.
- Conferência por `isql` nos dois bancos:

| Tabela | Original | Restaurado |
|---|---|---|
| EQUIPMENT | 49 | 49 |
| AGENT | 21 | 21 |
| RESOURCE_EVENT | 1356 | 1356 |
| MAINTENANCE_ORDER | 208 | 208 |
| SCHEMA_MIGRATION | 1 | 1 |

Observação: o processo do servidor Firebird roda como `root` dentro da imagem oficial (arquivos de dados pertencem ao
usuário `firebird`). O isolamento aqui é o do contêiner; em produção, preferir host/VM dedicados ou imagem endurecida.
