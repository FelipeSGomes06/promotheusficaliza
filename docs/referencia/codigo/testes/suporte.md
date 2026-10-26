# Suporte dos testes

<!-- fontes: tests/bootstrap.php tests/Support/ArraySession.php tests/Support/DatabaseSeparation.php tests/Support/FakeCepLookup.php tests/Support/FakeGeocoder.php tests/Support/FirebirdTestCase.php tests/Support/Interleaving.php tests/Support/Parallel.php tests/Support/parallel-worker.php phpunit.xml -->

Infraestrutura comum das três suítes do PHPUnit ([`phpunit.xml`](../../../../phpunit.xml)): `unidade` (`tests/Unit`,
sem banco), `integracao-firebird` (`tests/Integration`) e `http` (`tests/Http`), ambas contra um **Firebird real**. O
catálogo dos casos está em [casos de teste](casos.md); como rodar, em [`tests/README.md`](../../../../tests/README.md).

`phpunit.xml` falha em aviso, teste arriscado e depreciação (`failOnWarning`, `failOnRisky`, `failOnDeprecation`), força
`APP_ENV=test` e `APP_BASE_PATH=/promotheusfiscaliza`. `tests/bootstrap.php` carrega o autoload do Composer e fixa o fuso
do PHP em UTC (as conversões para Marília são sempre explícitas — [`Time`](../domain/tempo.md)).

## `FirebirdTestCase`

Classe base abstrata dos testes de integração e HTTP. Roda os casos de uso com o **usuário da aplicação** (só DML), como
em produção; o esquema é preparado com a identidade do dono.

| Método | Contrato |
|---|---|
| `FirebirdTestCase::setUpBeforeClass` | `static (): void` — **uma vez por execução** do PHPUnit: abre o banco de testes como dono, exige a separação do banco principal (`assertSeparateFromMainDatabase`), apaga tudo (`Migrator::dropEverything`), aplica todas as migrações e os GRANTs. Prova que o esquema nasce de um banco vazio. |
| `FirebirdTestCase::setUp` | `(): void` — numa transação do dono, `DELETE` em todas as tabelas (filhas antes das mães); relógio fixo em **2026-10-18 15:00 UTC**; `Services` novos; cria o órgão `DMT` e o bairro `Centro`. |
| `FirebirdTestCase::config` | `static (): Config` — configuração do `.env`/ambiente com `DB_DSN` trocado por `DB_TEST_DSN`, `DB_STATEMENT_TIMEOUT_SECONDS=2` e `DEMO_ALLOW_RESET=true`. Usada também pelos processos de `parallel-worker.php`. |
| `FirebirdTestCase::assertSeparateFromMainDatabase` | `private static (Database $testOwner): void` — recusa ("Testes destrutivos recusados: … Configure um banco de testes dedicado.") se `DB_TEST_DSN` estiver vazio, apontar para o mesmo alvo de `DB_DSN` ou, quando o principal está acessível, se os dois abrirem o mesmo arquivo físico (`MON$DATABASE_NAME`). Sem banco principal acessível (CI) vale a comparação dos DSNs. |
| `FirebirdTestCase::ownerDatabase` | `protected static (): Database` — conexão do dono (`DB_MIGRATION_USER`) ao banco de testes, timeout 10 s. |
| `FirebirdTestCase::newServices` | `protected (): Services` — nova conexão com a identidade da aplicação (para simular duas sessões) com CEP e geocodificação **falsos**: os testes nunca acessam a rede. |
| `FirebirdTestCase::equipmentData` | `protected (array $override = []): array` — dados válidos de uma câmera instalada no Centro (CEP `'17500-200'` como texto), sobrescrevíveis. |
| `FirebirdTestCase::createEquipment` | `protected (array $override = [], ?Services $services = null): int` — cria pelo `EquipmentService` e devolve o id. |
| `FirebirdTestCase::createAgent` | `protected (array $override = []): int` — cria o agente "Hermes 99" em atividade com base fixa. |
| `FirebirdTestCase::equipmentStatus` | `protected (int $id): string` — situação atual lida do repositório. |

Efeito colateral: **apaga e recria o banco apontado por `DB_TEST_DSN`**. Nunca aponte essa variável para um banco com
dados que importam.

## `DatabaseSeparation`

| Método | Contrato |
|---|---|
| `DatabaseSeparation::problem` | `static (string $mainDsn, string $testDsn, ?string $mainFile = null, ?string $testFile = null): ?string` — motivo da recusa ou `null` quando a separação foi demonstrada. Compara os alvos (`host/porta:caminho` em minúsculas, sem opções) e, se informados, os arquivos físicos sem diferenciar maiúsculas. |
| `DatabaseSeparation::target` | `private static (string $dsn): string` — extrai o `dbname=` do DSN. |

Teste: `DatabaseSeparationTest` (mesmo alvo com opções diferentes, apelidos diferentes para o mesmo arquivo, bancos
separados ou sem principal).

## Falsos e sessão em memória

| Símbolo | Contrato |
|---|---|
| `ArraySession` | sessão em memória (estende [`Session`](../http/aplicacao-web.md)) com o mesmo nome de cookie e caminho; sem cookies nativos. |
| `ArraySession::__construct` | `()` — `PFSESSID`, caminho `/promotheusfiscaliza/`, não segura. |
| `ArraySession::start` / `ArraySession::regenerate` / `ArraySession::destroy` | marca iniciada · conta as regenerações em `$regenerations` (para provar a troca de identificador no login) · limpa os dados. |
| `FakeCepLookup` | implementa [`CepLookup`](../application/endereco.md). |
| `FakeCepLookup::__construct` | `(array $answers = [], bool $down = false)` — respostas por CEP no formato `00000-000`; CEP ausente = não encontrado. |
| `FakeCepLookup::find` | `(string $cep): ?CepAddress` — registra a chamada em `$calls`; com `$down` lança `ProviderUnavailable`. |
| `FakeGeocoder` | implementa [`Geocoder`](../application/endereco.md). |
| `FakeGeocoder::__construct` | `(array $points = [], bool $down = false)` — pontos por rua (`lat`, `lng` como texto e `box` opcional). |
| `FakeGeocoder::locate` | `(string $street): ?array` — registra em `$calls`; com `$down` lança `ProviderUnavailable`. |

## Concorrência: `Parallel` e `Interleaving`

Dois mecanismos, para dois tipos de corrida:

| Símbolo | Contrato |
|---|---|
| `Parallel` | concorrência **real**: cada caso roda num processo PHP próprio, com conexão própria; todos se preparam, avisam e só então largam juntos. Usado quando um lado precisa **esperar o lock** do outro. |
| `Parallel::run` | `static (array $jobs, \DateTimeImmutable $now): list<array{ok, class?, message?, status?}>` — `$jobs` = lista de `[caso, ...args]`. Cria um diretório temporário, inicia `parallel-worker.php` por caso, espera os arquivos `pronto-N` (até 20 s), cria `largada`, coleta a saída JSON de cada processo e limpa o diretório. |
| `tests/Support/parallel-worker.php` | processo de um caso. Uso: `parallel-worker.php <dir> <n> <agora-ISO> <caso> [args...]`; conecta **antes** da largada e imprime `{"ok": …}`. Casos: `start` (iniciar manutenção), `archive`, `login` (`AuthService::attempt`), `mission` (confirmar missão), `fine-create`, `http-login` (sessão e CSRF obtidos do formulário real antes da largada; o resultado traz o status HTTP e se a página disse "Muitas tentativas"). |
| `Interleaving` | corrida com **ordem controlada** num único processo: cada caso roda numa `Fiber` com conexão própria e pausa logo depois da primeira consulta que casar com um padrão; o teste decide quem continua, sem `sleep`. Só serve quando nenhum lado espera lock do outro. |
| `Interleaving::connect` | `static (Services $services): Services` — troca a classe de statement do PDO dessa conexão por `PausingStatement`. |
| `Interleaving::case` | `static (callable $work, string $pauseAfter): array{0: \Fiber, 1: \ArrayObject}` — Fiber ainda não iniciada e o resultado (`ok`, `error` = classe curta, `message`). |
| `Interleaving::after` | `static (string $sql): void` — `@internal`: se a Fiber atual tem padrão e o SQL casa, remove o padrão e suspende. |
| `PausingStatement` | `@internal`, estende `\PDOStatement`. |
| `PausingStatement::__construct` / `PausingStatement::execute` | construtor protegido exigido pelo PDO · executa e chama `Interleaving::after`. |

Quem usa: `Parallel` em `LoginQuotaTest`, `MaintenanceInvariantsTest`, `PlanningTest` e `FinesTest`; `Interleaving` em
`AccountsTest` e `FinesTest` ([casos](casos.md)).
