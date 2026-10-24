# Verificação executada

Ambiente: Windows 11 + Docker Desktop 29.7.2; contêineres `php:8.5.11-apache-trixie` (PDO_FIREBIRD, fbclient 4.0.5)
e `firebirdsql/firebird:5.0.4-trixie`. Data: 18/10/2026. Só está listado aqui o que de fato rodou.

## Acompanhamento dos alertas de agentes (24/10/2026)

Projeto Compose isolado `pf-rev` (porta 8097; PHP 8.5.11, Firebird 5.0.4, perfil completo) sobre
`claude/revisao-auditoria-codex-1023` em `b893aae`. [Evidência](evidencias/2026-10-24-acompanhamento-de-alertas.md),
[ADR-0022](adr/0022-acompanhamento-dos-alertas-de-agentes.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **267 testes, 1739 asserções, OK** |
| Testes novos | `AlertFollowUpTest` (mesmo alerta na pausa; ciência sem resolver nem atribuir; providência só depois da ciência e só de acréscimo; validação; conta recém-cadastrada atribuída e reatribuída com histórico; conta desativada; versão antiga → conflito; posição nova, antiga e fora de ordem; saída de turno e arquivamento; pendência antiga além dos 20 encerrados; motivo obrigatório), `AgentsHttpTest` (percurso na ficha com 404, 422 e 409 mantendo a digitação), `DomainRulesTest` (pausa, próxima ação, motivo de encerramento) |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` · `npm run build` (Node 24) | 0 erros · 0 de 130 · sem erros |
| Migração 0009 | aplicada sobre o banco populado; segunda execução sem efeito |
| Navegador (`scripts/verificacao-interface/acompanhamento.cjs`, Chromium headless) | 24 verificações OK, 0 erros de console e de CSP; 1440, 390 e 360 px, teclado, movimento reduzido |
| Site publicado | **não verificado** (não implantado) |

## Revisão da auditoria de interface do Codex (24/10/2026)

Projeto Compose isolado `pf-rev` (porta 8097; PHP 8.5.11, Firebird 5.0.4) sobre `main` em `a6a4aae`, perfil completo.
Relatório da revisão fora do repositório (entrega do Codex): `~/.codex/entregas-para-claude/auditoria-logica-fiscaliza-2026-10-23/REVISAO-E-ALTERACOES-CLAUDE.md`.

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | 254 testes; falha só `OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock`, porque outro projeto Compose do mesmo computador (`fiscaliza-claude`) roda `sim:run` sobre esta mesma pasta e segura `var/simulador.lock` — não depende das mudanças |
| Testes novos | `FineRulesTest` (pendência × regularização posterior), `FinesTest` (contador, lista e ficha; radar sem a função), `InventoryTest` (situação × local, reposição, entrada em operação), `OperationsTest` (resumo de urgentes), `PlanningHttpTest` (aviso de semana encerrada; datas do retrospecto) |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` · `npm run build` (Node 24) | 0 erros · 0 de 128 · sem erros (mudam `app`, `central` e `multas`) |
| Migração 0008 | antes: 1 multa com contador diferente da regra nova (#7); depois: 0; segunda execução sem efeito |
| Navegador (painel do Claude Code, 800 px e 375 px) | central (ficha e marcador do furtado, código na lista, resumo de urgentes = seção Urgentes, com e sem filtro de veículo), fichas de equipamento (furtado, revisão sem ordem, ordem existente, ordem fora da janela), cadastro (situação × local; recusa no servidor), reposição de furtado, multas #3, #2, #6 e #7, formulário de multa (observação para radar sem a função), planejar (designado, missão), retrospecto e revisões (janela e cálculo antigo) |
| Site publicado | **não verificado** (sem conta nesta sessão) |
| Adendo (modalidade do radar; radar nunca instalado ou fixo com operador) | `phpunit` 255 testes, 1664 asserções, OK; `FinesTest::testNeverInstalledOrFixedRadarWithAnOperatorNeedsAnExplanation`; PHPStan e estilo sem pendências; no navegador, cadastro do RAD-0001 como fixo e o formulário de multa mostrando a observação só nos casos da regra |
| Carga `multas-v3` (modalidade dos radares; radar portátil no caso 2; compacto com 17) | `phpunit` 255 testes: passa tudo, salvo o teste do cadeado do simulador quando o `fiscaliza-claude-simulator-1` está ligado sobre esta pasta; `DemoProfileTest` (17, 5 radares) e `FinesTest` (v1 → v3 e v2 → v3 sem duplicar). Banco local carregado com a v2: `demo:seed-multas` informou a modalidade dos radares que não a tinham (inclusive o sem série, pelo endereço) e retificou o caso 2; repetir não mudou nada. Carga nova: caso 2 registrado sem observação, no portátil a 60 km/h. `avaliar-sementes.php` 2027–2031: completo igual antes e depois (8,0% / 15,4%, 1.690 pares); compacto 6,9% / 12,9% (16) → 12,0% / 33,3% (17) |

## Cadastrar agente: base pelo mapa ou pelo endereço, rua pelo CEP (23/10/2026)

Contêiner de nuvem do Claude Code, Docker local (PHP 8.5.11, Firebird 5.0.4) sobre `main` em `730e303`.
[Evidência](evidencias/2026-10-23-agente-base-pelo-mapa-e-cep.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | 242 testes; falham só os 4 do `UrbanUpdaterTest` que exigem a extensão `zip`, ausente na imagem local (iguais em `main`) |
| `php-cs-fixer --dry-run` · PHPStan 2.3.1 nível 6 · `npm run build` | 0 de 126 · sem erros · sem erros |
| `scripts/verificacao-interface/agentes.cjs` | 32 verificações OK, 0 erros de console e de CSP |
| Chamadas reais ao ViaCEP e ao Nominatim | **não verificadas** (rede do ambiente bloqueada) |

## Aba Multas (21/10/2026)

Projeto Compose isolado `pf-multas` (PHP 8.5.11, Firebird 5.0.4) sobre `main` em `3ef2904`.
[Evidência completa](evidencias/2026-10-21-multas.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **199 testes, 1058 asserções, OK**. Antes da correção: 1 falha em `PlanningHttpTest::testReplayKeepsObservedCountsHiddenUntilRevealed`, igual no `HEAD` limpo (`3ef2904`) — o teste procurava textos que o redesenho do retrospecto trocou ("Revelar o que foi registrado" → "Revelar o que aconteceu"; "oculto" → "Registrado: ?"); o teste foi atualizado, o comportamento (registros ocultos até revelar) continua conferido |
| Testes novos | `FineRulesTest`, `FinesTest` (15, inclusive processos concorrentes e rollback por falha no histórico), `FinesHttpTest` (7); `DemoProfileTest` confere 12/40 multas |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` · `composer validate --strict` · `composer audit` | 0 erros · 0 arquivos · válido · sem avisos |
| `npm run build` · `npm run build:landing` | Node 24 (Windows) e `node:22-bookworm`: 20 arquivos com SHA-256 idênticos; landing sem mudança |
| Migração | banco populado pela versão anterior: só a 0007 aplicada, contagens iguais; segunda execução sem efeito; `demo:seed-multas` 2× sem duplicar |
| Navegador (`scripts/verificacao-interface/multas.cjs`) | 26 verificações OK, 0 erros de console, 0 violações de CSP; 19 capturas (1440, 390 e 360 px, movimento reduzido, sem JavaScript) |

## Planejar reforço por setores (20/10/2026)

Projeto Compose isolado `fiscaliza-marilia` (PHP 8.5.11, Firebird 5.0.4) sobre `main` em `132dc7f`.
[Evidência completa](evidencias/2026-10-20-planejar-reforco.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **140 testes, 672 asserções, OK** (inclui concorrência em processos separados e 40 ciclos do simulador) |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` | 0 erros · 0 arquivos a corrigir |
| `npm run build` | Node 24 local e `node:22-bookworm` (CI) com os mesmos 11 arquivos |
| `urban:import` duas vezes · dois conjuntos adulterados | 1 execução (4 setores, 204 linhas) · recusados, nada gravado |
| Treino reproduzido a partir de `ml/marilia-setores/` | saídas idênticas à execução de referência; paridade PHP/Python 2,7e-15 |
| Navegador (painel do Claude Code, desktop e 375 px) | 17 capturas; missão confirmada e retrospecto revelado pela interface |

## Cartão de autoria na página Sobre (19/10/2026)

Verificado só no navegador (Chromium headless 141 via Playwright, cabeçalhos reais de `SecurityHeaders`, destino
simulado): entrada, tremor, expansão, Esc, voltar pelo histórico, movimento reduzido e celular, com **0 violações de
CSP e 0 erros de console**; build reproduzível. PHPUnit, PHPStan e PHP-CS-Fixer **não** rodaram nesse ambiente (sem
Firebird nem PHP 8.4+); a asserção nova do `WebSecurityTest` foi conferida contra o HTML renderizado.
[Evidência](evidencias/2026-10-19-cartao-de-autoria.md).

## Identidade nova com acesso somente por conta (19/10/2026)

Junção da identidade de trânsito com o acesso somente por conta ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)),
no projeto Compose isolado `fiscaliza-claude` (PHP 8.5.11, Firebird 5.0.4). Detalhes, ajustes da junção e capturas:
[evidência](evidencias/2026-10-19-identidade-com-acesso-por-conta.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **100 testes, 447 asserções, OK** |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` | 0 erros · 0 de 88 arquivos a corrigir |
| `npm run build` | idêntico em duas execuções; é o versionado |
| Navegador: 54 carregamentos sem e com conta + 12 cenários (B–M) | 0 violações de CSP, 0 erros de console; sem conta tudo leva ao login sem menu; sessão encerrada com a central aberta → login |
| Depois, no VPS: tela de login antiga e sem estilo após o `git pull` (templates compilados da versão anterior em `var/cache/twig`) | corrigido com `auto_reload` no Twig; `ViewCacheTest` reproduz (falha sem a correção) e passa com ela: **101 testes, 454 asserções** |

## Acesso somente com conta cadastrada (19/10/2026)

Mudança do [ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md). Ambiente diferente do oficial: Linux,
Firebird 5.0.4 no contêiner oficial e PHP **8.5.4** (Ubuntu, PDO_FIREBIRD) em vez da imagem `php:8.5.11` — o
espelho do Debian não estava acessível. Servidor embutido do PHP no lugar do Apache para a conferência no navegador.

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **93 testes, 372 asserções, OK** (antes da mudança, no mesmo ambiente: 89/314, OK) |
| `phpstan analyse` (nível 6) · `php-cs-fixer fix --dry-run` | 0 erros · 0 de 85 arquivos a corrigir |
| Visitante (`curl`): `/`, `/equipamentos`, `/sobre`, `/contas` → 302 para o login; `/api/central` → 401 sem dados; `/saude` 200; `/entrar` 200 | OK |
| Navegador (1366×860 e 390×844): visitante levado ao login sem menu; login volta à página pedida; menu no celular; sair → login; console sem erros (exceto tiles do mapa, bloqueados pela rede do ambiente) | OK |

Novas coberturas HTTP: todas as telas e a API sem conta, cadastro pelo administrador → login → acesso, desativação
com efeito imediato (inclusive na API da visão geral), logout para o login, banco fora com sessão aberta → 503.

## Identidade, motion e perfil compacto (18/10/2026, ~23:50 UTC)

Contêiner de nuvem do Claude Code, projeto Compose isolado `fiscaliza-claude` (porta 8091), PHP 8.5.11, Firebird
5.0.4 (cliente PDO_FIREBIRD montado localmente; ver a evidência). Detalhes, relatório do navegador, capturas e vídeos:
[identidade e motion](evidencias/2026-10-18-identidade-e-motion.md).

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` | **96 testes, 385 asserções, OK** |
| `vendor/bin/phpstan analyse` (nível 6) · `php-cs-fixer --dry-run` | 0 erros · nenhum arquivo a corrigir |
| `npm ci && npm run build` | build idêntico em duas execuções; é o que está versionado em `public/assets/build/` |
| Navegador (Chromium 141, cabeçalhos reais): 34 carregamentos + 11 cenários de comportamento | 0 violações de CSP, 0 erros de console, sem rolagem horizontal; todos os cenários OK |
| Avaliação das sementes 2027–2031 nos perfis compacto e completo | registrada em [avaliação das sementes](evidencias/2026-10-18-avaliacao-sementes.md#perfis-compacto-e-completo-18102026) |

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
- **HTTP:** cabeçalhos e CSP, visitante sem cookie (desde o ADR-0014, levado ao login), CSRF ausente/errado, `Origin` estranho, 401/403 por papel,
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
