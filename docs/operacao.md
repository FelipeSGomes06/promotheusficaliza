# Operação

## Configuração (`.env` ou variáveis de ambiente)

Valores reais ficam só no ambiente; o repositório traz [`.env.example`](../.env.example) com valores fictícios.

| Variável | Finalidade |
|---|---|
| `APP_ENV` | `production` (erros genéricos, cache de templates) · `development` · `test`. |
| `APP_BASE_PATH` | Prefixo público (`/promotheusfiscaliza`). Vazio para servir na raiz de uma origem dedicada. |
| `APP_SECURE_COOKIES` | `true` em HTTPS (cookie `Secure`). |
| `APP_ORIGIN` | Origem pública (`https://…`) para a checagem de `Origin` quando um proxy termina o TLS. |
| `APP_PORT` | Porta local do Compose (só em `127.0.0.1`). |
| `DB_DSN` | `firebird:dbname=<host>/<porta>:<caminho ou alias>;charset=UTF8;dialect=3`. |
| `DB_USER`, `DB_PASSWORD` | Usuário da aplicação (somente DML). |
| `DB_MIGRATION_USER`, `DB_MIGRATION_PASSWORD` | Dono do esquema: migrações e carga de demonstração. |
| `DB_TEST_DSN` | Banco de testes (apagado e recriado pela suíte). |
| `DB_STATEMENT_TIMEOUT_SECONDS` | Limite de espera por lock/instrução (padrão 5). |
| `FIREBIRD_ROOT_PASSWORD` | SYSDBA dentro do contêiner Firebird (a aplicação não usa). |
| `MAP_TILE_URL`, `MAP_TILE_ATTRIBUTION` | Provedor de tiles e atribuição obrigatória; a origem entra na CSP `img-src`. |
| `DEMO_ALLOW_RESET` | `true` só em ambientes de demonstração descartáveis. |
| `SIM_SEED`, `SIM_INTERVAL_SECONDS`, `POLL_INTERVAL_SECONDS` | Semente do cenário, ciclo do simulador e da consulta da central. |

## Comandos (`php bin/fiscaliza …`)

| Comando | O que faz |
|---|---|
| `migrate` | Aplica migrações pendentes (identidade de migração) e sincroniza GRANTs mínimos do usuário da aplicação. Nunca roda por HTTP. |
| `demo:seed` | Carrega o cenário sintético e 240 dias de histórico; recusa se já houver dados. `--reset` apaga dados de domínio (preserva contas) apenas com `DEMO_ALLOW_RESET=true` e o marcador do conjunto sintético. |
| `user:create <usuário> --role=ADMIN\|OPERADOR --name="…"` | Cria conta com senha aleatória exibida uma vez. |
| `sim:tick` | Um ciclo do simulador — para **cron** em hospedagens sem processo permanente. |
| `sim:run [--interval=5] [--duration=0]` | Simulador contínuo (serviço `simulator` do Compose). Falha de banco: registra e tenta no próximo ciclo. |
| `analysis:run` | Recalcula a análise preventiva (também pelo botão "Recalcular", para operadores). |

Cron sugerido sem processo permanente: `* * * * * php bin/fiscaliza sim:run --interval=10 --duration=55` (seis ciclos
por minuto, sem sobreposição) e `15 3 * * * php bin/fiscaliza analysis:run`.

## Backup e restauração

[`scripts/backup-restore-teste.sh`](../scripts/backup-restore-teste.sh) faz `gbak -b` do banco (dono do esquema) e
restaura num banco **separado** (`fiscaliza_restaurado.fdb`), comparando contagens. Resultado registrado em
[evidências](evidencias/2026-10-18-backup-restauracao.md). Para restaurar de verdade: pare app e simulador, restaure
o `.fbk` num arquivo novo, aponte `DB_DSN` para ele (ou renomeie com o servidor parado), rode `migrate` (não aplica
nada se o esquema estiver em dia) e confira `/saude` e a central. Nunca copie o `.fdb` com o servidor em uso.
Guarde backups fora de qualquer diretório servido e fora do Git.

## Logs e recuperação

- Erros PHP e exceções inesperadas: `/var/log/fiscaliza/php-error.log` no contêiner `app` (linhas com
  `[fiscaliza <referência>]` — a mesma referência mostrada ao usuário). Acesso Apache: `docker compose logs app`.
- Simulador: `docker compose logs -f simulator` (uma linha JSON por ciclo).
- Banco indisponível: páginas respondem 503 com mensagem genérica; a central mostra "sem conexão" e tenta de novo com
  recuo até 60 s; o simulador continua tentando.
- Lock preso: o timeout de instrução encerra a espera; a operação retorna 409 "registro ocupado, tente novamente".
- Ambiente local do zero: `docker compose down -v` apaga **todos** os dados locais (volume do Firebird).

## Publicação

**Não realizada.** Opções avaliadas para o endereço `https://devfelipegomes.com.br/promotheusfiscaliza`:

| Opção | Firebird | Isolamento de origem | Observação |
|---|---|---|---|
| A. Origem dedicada (`fiscaliza.<domínio>`) em VPS/contêiner + redirecionamento do caminho no portal | ✓ (Compose) | ✓ | **Recomendada.** Mesmo `compose.yaml`; `APP_BASE_PATH` vazio; TLS no proxy; `APP_SECURE_COOKIES=true`; `APP_ORIGIN` configurado. |
| B. Proxy reverso do portal (`/promotheusfiscaliza` → contêiner) | ✓ | ✗ (mesma origem) | Exige `mod_proxy` no servidor do portal; documentar o limite. |
| C. Hospedagem compartilhada do portal | Improvável (sem servidor Firebird; `pdo_firebird` incerto) | ✗ | Não recomendada; verificar com `php -m \| grep -i firebird` no PHP da conta. |

Checklist de produção (qualquer opção): `APP_ENV=production`, `DEMO_ALLOW_RESET=false` depois da carga, senhas novas
e exclusivas, Firebird sem porta pública, backups agendados, `user:create` executado no servidor, simulador por
serviço ou cron, conferência dos cabeçalhos efetivos (`curl -sI`) e da CSP do portal na raiz (não alterada).
