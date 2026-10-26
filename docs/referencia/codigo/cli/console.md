# CLI: `php bin/fiscaliza`

<!-- fontes: src/Cli/Console.php -->

Comandos de operação. Saída curta em texto; segredos nunca são impressos, exceto a senha gerada na criação de conta.
Nenhum comando é acessível por HTTP. Identidade usada por comando: dono do esquema em `migrate`, `demo:seed` e
`demo:seed-multas`; aplicação nos demais ([`bin/fiscaliza`](../raiz/configuracao-e-servicos.md#pontos-de-entrada)).
Guia operacional com exemplos e efeitos: [operação](../../../operacao.md#comandos-php-binfiscaliza-).

| Comando | Escreve? | Efeito resumido |
|---|---|---|
| `migrate` | esquema + GRANTs | aplica migrações pendentes e sincroniza privilégios do usuário da aplicação |
| `demo:seed [--reset] [--perfil=compacto\|completo]` | **sim** (apaga com `--reset`) | carrega o cenário sintético, 240 dias de histórico, uma análise e as multas |
| `demo:seed-multas` | sim (acrescenta) | acrescenta/atualiza o cenário de multas sem apagar nada |
| `user:create <usuário> [--role=ADMIN\|OPERADOR] [--name="…"]` | sim | cria conta e imprime a senha uma vez |
| `sim:tick` | sim | um ciclo do simulador (para cron), com trava |
| `sim:run [--interval=5] [--duration=0]` | sim | simulador contínuo |
| `analysis:run` | sim | nova execução da análise preventiva |
| `urban:import [--dir=…] [--setores=…] [--manifesto=…] [--grade=…\|--sem-grade]` | sim (idempotente) | importa uma execução do treino e os quadrados |
| `urban:update [--forcar-download] [--arquivo=…] [--semana=AAAA-MM-DD]` | sim (idempotente) | download mensal do Infosiga e previsão semanal |
| qualquer outro (ou nenhum) | não | imprime a ajuda |

## `Console::run`

`run(array $args): int` — despacha pelo primeiro argumento e devolve o código de saída. `ValidationFailed` → mensagem e
uma linha por campo (`- campo: mensagem`) em STDERR, saída 1; outras `RuntimeException`/`InvalidArgumentException` →
"Erro: …" (e "Causa: …" do driver fora de produção), saída 1.

## Comandos (métodos privados)

| Método | Contrato |
|---|---|
| `Console::migrate` | `Migrator::migrate()` → "Aplicadas: …" ou "Nenhuma migração pendente."; depois `grantApplicationAccess(DB_USER)` → "Privilégios DML concedidos … em N tabelas.". Saída 0. |
| `Console::seed` | Com `--perfil`, refaz os serviços com `DEMO_PROFILE` sobreposto (perfil inválido → erro). Usa a identidade de migração. Banco com equipamentos: sem `--reset` → recusa ("Use --reset…", saída 1); com `--reset` exige `DEMO_ALLOW_RESET=true` **e** o marcador `dataset = demo-sintetico` (senão recusa, saída 1). Executa [`DemoSeeder::run`](../demo/cenario-e-carga.md#demoseederrun) e imprime o resumo (perfil, órgãos, bairros, equipamentos, agentes, eventos, ordens, alertas abertos, execução da análise, multas). |
| `Console::seedFines` | Identidade de migração. Exige o marcador sintético; mesma versão já carregada (`fines_seed = multas-v3`) → "nada foi duplicado" (0); versão anterior (`multas-v1`/`v2`) → [`FineScenario::upgrade`](../demo/multas-de-demonstracao.md#finescenarioupgrade) no lugar, cada mudança no histórico (0); multas `SEMENTE` sem marcador desta versão → conflito, nada alterado (1); senão [`FineScenario::seed`](../demo/multas-de-demonstracao.md#finescenarioseed) com o perfil gravado. |
| `Console::createUser` | `AuthService::createUser(usuário, --name ou o usuário, --role em maiúsculas ou OPERADOR)`; imprime a conta e "Senha (mostrada só agora, guarde em local seguro): …". |
| `Console::simTick` | Um ciclo com [`exclusiveTick`](#consoleexclusivetick); imprime as estatísticas em JSON ou "Ciclo ignorado: outro ciclo do simulador ainda está em execução (var/simulador.lock)." (saída 0 nos dois casos). |
| `Console::simRun` | Laço: intervalo = `--interval` ou `SIM_INTERVAL_SECONDS` (mínimo 2 s); duração `--duration` (0 = sem fim). A cada ciclo, `exclusiveTick` e uma linha `HH:MM:SS {estatísticas}`; `RuntimeException` (banco fora) → registra em STDERR, desconecta e tenta no próximo ciclo. A duração só limita quanto tempo o processo roda; não protege contra sobreposição (a trava protege). |
| `Console::analysis` | `AnalysisService::run(null)` → "Análise #N registrada: {classes}". |
| `Console::urbanImport` | Caminhos de `--dir`, `--setores`, `--manifesto`, `--grade` (relativos à pasta atual) ou os padrões (relativos à raiz do projeto); `--sem-grade` omite os quadrados. Imprime se criou ou já existia, período (fim exclusivo), setores, linhas de retrospecto, quadrados (N de 64) e o SHA-256 do conjunto. |
| `Console::urbanUpdate` | `UrbanUpdater::update(INFOSIGA_URL ou o padrão, var/infosiga, --arquivo, --forcar-download, --semana)`; uma linha por mensagem com data e hora; saída 1 se o download falhou ou o arquivo foi recusado (a previsão semanal roda mesmo assim). |
| `Console::help` | Texto de uso com todos os comandos. |
| `Console::options` | `private static (array $args): array` — `--chave=valor`, `--flag` (true) e posicionais `_0`, `_1`… |

### `Console::exclusiveTick`

`private exclusiveTick(): ?array` — cria `var/` se preciso e abre `var/simulador.lock` (modo `c`, ou leitura se o
arquivo foi criado por outro usuário do sistema); com `flock(LOCK_EX | LOCK_NB)` obtido, roda
[`Simulator::tick`](../demo/simulador.md#simulatortick); senão devolve `null` (ciclo ignorado). A trava é liberada ao
fim do ciclo ou se o processo morrer. Vale para processos que usam o **mesmo diretório** `var/` (mesmo servidor ou
volume). Teste: `OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock`.

| Símbolo | Contrato |
|---|---|
| `Console::__construct` | `(Services $services)`. |
| `Console::SKIPPED` (privada) | texto do ciclo ignorado. |

## Relacionados

[Operação](../../../operacao.md) · [Demonstração](../demo/cenario-e-carga.md) · [`bin/README.md`](../../../../bin/README.md)
