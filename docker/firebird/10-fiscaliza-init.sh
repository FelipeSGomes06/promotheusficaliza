#!/bin/bash
# Executado pela imagem oficial do Firebird somente na criação do banco principal.
# - cria o usuário da aplicação (sem papel administrativo; privilégios virão das migrações via GRANT);
# - revoga o papel ADMIN que a imagem concede ao dono do banco (FIREBIRD_USER);
# - cria o banco de testes, com o mesmo dono, para os testes de integração.
set -euo pipefail

sql_string() { printf "%s" "$1" | sed "s/'/''/g"; }

: "${FISCALIZA_APP_DB_USER:?defina FISCALIZA_APP_DB_USER}"
: "${FISCALIZA_APP_DB_PASSWORD:?defina FISCALIZA_APP_DB_PASSWORD}"

/opt/firebird/bin/isql -b -user SYSDBA security.db <<EOSQL
CREATE OR ALTER USER ${FISCALIZA_APP_DB_USER} PASSWORD '$(sql_string "$FISCALIZA_APP_DB_PASSWORD")';
ALTER USER ${FIREBIRD_USER} REVOKE ADMIN ROLE;
EXIT;
EOSQL

if [ -n "${FISCALIZA_TEST_DATABASE:-}" ] && [ ! -f "$FIREBIRD_DATA/$FISCALIZA_TEST_DATABASE" ]; then
  /opt/firebird/bin/isql -b -q <<EOSQL
CREATE DATABASE '$(sql_string "$FIREBIRD_DATA/$FISCALIZA_TEST_DATABASE")'
  USER '$(sql_string "$FIREBIRD_USER")' PASSWORD '$(sql_string "$FIREBIRD_PASSWORD")'
  DEFAULT CHARACTER SET UTF8;
EXIT;
EOSQL
fi
echo "fiscaliza: usuários e banco de testes prontos"
