# Verificação executada

Ambiente: Windows 11 + Docker Desktop 29.7.2; contêineres `php:8.5.11-apache-trixie` (PDO_FIREBIRD, fbclient 4.0.5)
e `firebirdsql/firebird:5.0.4-trixie`. Data: 18/10/2026. Só está listado aqui o que de fato rodou.

## Depois das correções da auditoria e do redesenho (18/10/2026, 21:20 UTC)

Projeto Compose isolado (`-p fiscaliza-claude`, porta 8091, volume próprio), PHP 8.5.11, Firebird 5.0.4. Detalhes,
contraprova e defeitos encontrados no caminho: [correções da auditoria](evidencias/2026-10-18-correcoes-da-auditoria.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **89 testes, 314 asserções, OK** (inclui testes concorrentes com processos separados, repetidos 3 vezes sem falha) |
| `vendor/bin/phpstan analyse` (nível 6) | 0 erros |
| `vendor/bin/php-cs-fixer fix --dry-run` | 0 de 85 arquivos a corrigir |
| `composer validate --strict` · `composer audit` | válido · nenhum aviso nas bases consultadas |
| Migração 0001 → 0002 com falhas de login antigas | OK |
| Navegador: 22 telas (1440×900 e 390×844), teclado, atualização com simulador, movimento reduzido, console | OK; mapa no celular em y≈475 px; sem rolagem horizontal; console sem erros |
| Origem dedicada (`compose.origem-dedicada.yaml`) local | OK (raiz 200; caminho antigo 301) |

Novas coberturas: uma intervenção por vez (inclusive concorrente e no banco), arquivamento × intervenção, versão do
cadastro × telemetria, conflito HTTP recuperável, periodicidade/recebimento/arquivamento nos cortes da análise,
convenções de F1, cota de login concorrente (serviço e HTTP com sessões e CSRF distintos), separação do banco de
testes, trava do simulador, busca e "precisa de atenção" no instantâneo.

As seções abaixo registram a verificação anterior (62 testes), mantida como histórico.

## Automatizado (primeira entrega)

| Verificação | Comando | Resultado |
|---|---|---|
| Testes (unidade + integração Firebird + HTTP) | `docker compose exec app vendor/bin/phpunit` | **62 testes, 218 asserções, OK** (~21 s). A primeira classe de integração apaga e recria o esquema do banco de testes pelas migrações (prova em banco vazio a cada execução). |
| Análise estática | `vendor/bin/phpstan analyse` (nível 6, `src`, `bin`, `public/index.php`) | **0 erros** (achou e corrigiu um `sprintf` do log de erros com marcador a menos). |
| Estilo | `vendor/bin/php-cs-fixer fix --dry-run` (`@PER-CS`) | **0 arquivos a corrigir**. |
| Dependências | `composer update` / `composer audit` | Nenhum alerta conhecido nas bases consultadas (não prova ausência de vulnerabilidades). |

### O que os testes cobrem

- **Driver/Firebird:** UTF-8 (inclui emoji) e BLOB, decimal, booleano, `RETURNING`, `OFFSET/FETCH`, rollback de
  transação com várias gravações, espera por lock limitada virando conflito (conexão reutilizável depois),
  usuário da aplicação sem DELETE/UPDATE no histórico e sem DDL.
- **Inventário:** características por tipo, série única por fabricante (com séries nulas), **duas conexões
  concorrentes** gravando a mesma série, versão otimista, transições, instalação, arquivamento, base × posição.
- **Operação:** duplicata, evento atrasado, estado administrativo prevalecendo, posição antiga, alerta idempotente,
  reconhecer ≠ resolver, reconciliação concorrente sem alerta duplicado, ciclo completo de manutenção, conclusão
  ainda em falha, preventiva periódica agendada com data local.
- **Central e análise:** mapa/lista/indicadores do mesmo conjunto filtrado, denominador zero, recurso sem
  coordenadas, análise reproduzível, ajuste antes da avaliação, classes insuficiente/corretiva, recusa de reset.
- **HTTP:** cabeçalhos e CSP, visitante sem cookie, CSRF ausente/errado, `Origin` estranho, 401/403 por papel,
  escape de HTML/JSON com texto malicioso, mensagens de login iguais, limite de tentativas, redirecionamento externo
  descartado, parâmetros malformados, 404 sem SQL, reconhecimento de alerta via JSON com token no cabeçalho.

## Manual (navegador do Claude Code e `curl`)

| Verificação | Resultado |
|---|---|
| Todas as rotas sob `/promotheusfiscaliza` (públicas 200; protegidas → login; `.env` e caminhos inexistentes → 404); raiz `/` continua servindo o "portal" simulado | OK |
| Cabeçalhos efetivos do Apache (CSP, nosniff, frame, referência, permissões, COOP/CORP, `no-store`, `Server: Apache` sem versão, cookie só no login com `Path=/promotheusfiscaliza/; HttpOnly; SameSite=Lax`) | OK |
| Central em 1440×900: mapa de Marília, 69 marcadores, KPIs, filtros, abas, ficha lateral com miniatura; simulador ligado atualiza a disponibilidade confirmada | OK |
| Login, cadastro de radar (erro de limite condicional, erro de série duplicada vindo do banco, sucesso), instalação (planejado → instalado), `<script>` exibido como texto | OK |
| Backup `gbak` + restauração em banco separado com contagens idênticas | OK ([evidência](evidencias/2026-10-18-backup-restauracao.md)) |
| Avaliação da análise em 5 sementes inéditas | Registrada ([evidência](evidencias/2026-10-18-avaliacao-sementes.md)) |

## Não executado (ainda)

- Instalação a partir de **clone limpo** do repositório público (repositório ainda não criado) — ver pendências.
- Publicação, varredura de segredos no histórico Git, CI remota, ZIP final e vídeo.
- Leitor de tela real e ferramenta automática de acessibilidade (axe/Lighthouse); feitas checagens de estrutura,
  teclado, foco visível, contraste calculado e movimento reduzido.
- Teste de usabilidade com pessoa iniciante: as cinco tarefas foram percorridas por mim, sem participante real.
- Medição de desempenho em rede real; no ambiente local o instantâneo da visão geral (69 recursos) tinha ~66 KB de JSON na primeira entrega e ~78 KB depois do redesenho (títulos, endereços e motivos de atenção).
- Ensaio de backup/restauração depois da migração 0002 (o script não mudou; a evidência é da primeira entrega).

## Ambiente limpo (18/10/2026)

Cópia do projeto **sem** `vendor/`, `.env`, `var/`, `docs/privado/`, caches e bancos (o que um clone público teria):

| Verificação | Resultado |
|---|---|
| Pipeline de CI reproduzido em contêiner novo `php:8.5.11-cli-trixie` + Firebird 5.0.4 de serviço: instalação do `pdo_firebird`, `composer install` pelo lock, `composer validate --strict`, `composer audit`, estilo, PHPStan, preparo do banco e PHPUnit | **OK — 62 testes, 218 asserções** |
| Instalação seguindo só o README (Compose com outro nome de projeto e porta 8091): `up --build`, `composer install`, `migrate`, `demo:seed`, `user:create`, simulador | **OK** — central 200, API com 69 recursos, simulador ativo; ambiente removido depois (`down -v`) |

Pendente: repetir a partir do `git clone` real quando o repositório público existir (mesmos passos).

## Preparação do repositório (18/10/2026, sem commit)

| Verificação | Resultado |
|---|---|
| `git add -A` com `.gitignore`: 170 arquivos; nenhum `.env`, `var/`, `vendor/`, `docs/privado/`, `*.fdb`/`*.fbk` | OK |
| `.gitattributes` força LF (o Git do Windows converteria scripts bash para CRLF e quebraria o init do Firebird) | OK |
| `gitleaks v8.30.1 dir` sobre o conteúdo indexado (`git checkout-index`) | **no leaks found** (~1,05 MB) |
| ZIP de prévia por `git archive` da árvore indexada | ~0,5 MB, dentro do limite de 50 MB; sem arquivos sensíveis |

Pendente: repetir a varredura sobre o **histórico** depois do primeiro commit e antes do push (`gitleaks git`), e gerar o
ZIP final a partir do commit entregue.
