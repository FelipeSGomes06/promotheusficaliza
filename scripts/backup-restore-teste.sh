#!/bin/bash
# Backup (gbak) do banco de demonstração e restauração num banco SEPARADO, para provar a recuperação.
# Uso (na raiz do projeto): bash scripts/backup-restore-teste.sh
# O arquivo .fbk fica no volume do contêiner Firebird (fora do alcance HTTP e fora do Git).
set -euo pipefail
source <(grep -E '^(DB_MIGRATION_USER|DB_MIGRATION_PASSWORD)=' .env)
STAMP=$(date +%Y%m%d-%H%M%S)
DATA=/var/lib/firebird/data
# Restauração local (modo embarcado, usuário do SO "firebird"): o dono do esquema não tem permissão de criar bancos pela rede.
docker compose exec -T -u firebird -e U="$DB_MIGRATION_USER" -e P="$DB_MIGRATION_PASSWORD" firebird bash -c "
  set -e
  gbak -b -g -user \"\$U\" -password \"\$P\" localhost:$DATA/fiscaliza.fdb $DATA/backup-$STAMP.fbk
  rm -f $DATA/fiscaliza_restaurado.fdb
  gbak -c -user \"\$U\" $DATA/backup-$STAMP.fbk $DATA/fiscaliza_restaurado.fdb
  ls -l $DATA/backup-$STAMP.fbk $DATA/fiscaliza_restaurado.fdb
  for DB in fiscaliza fiscaliza_restaurado; do
    echo \"-- \$DB\"
    echo \"SELECT 'equipamentos', COUNT(*) FROM EQUIPMENT UNION ALL SELECT 'agentes', COUNT(*) FROM AGENT UNION ALL SELECT 'eventos', COUNT(*) FROM RESOURCE_EVENT UNION ALL SELECT 'ordens', COUNT(*) FROM MAINTENANCE_ORDER UNION ALL SELECT 'migracoes', COUNT(*) FROM SCHEMA_MIGRATION;\" \
      | isql -q -user \"\$U\" -password \"\$P\" localhost:$DATA/\$DB.fdb
  done
"
