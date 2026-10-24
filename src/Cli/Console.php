<?php

declare(strict_types=1);

namespace Fiscaliza\Cli;

use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Application\UrbanUpdater;
use Fiscaliza\Demo\DemoScenario;
use Fiscaliza\Demo\FineScenario;
use Fiscaliza\Infrastructure\Repository\MetaRepository;
use Fiscaliza\Services;

/** Comandos de operação. Saída curta em texto; segredos nunca são impressos, exceto a senha gerada na criação de conta. */
final class Console
{
    private const SKIPPED = 'Ciclo ignorado: outro ciclo do simulador ainda está em execução (var/simulador.lock).';

    public function __construct(private readonly Services $services) {}

    /** @param list<string> $args */
    public function run(array $args): int
    {
        $command = $args[0] ?? 'ajuda';
        $options = self::options(array_slice($args, 1));
        try {
            return match ($command) {
                'migrate' => $this->migrate(),
                'demo:seed' => $this->seed($options),
                'demo:seed-multas' => $this->seedFines(),
                'user:create' => $this->createUser($options),
                'sim:tick' => $this->simTick(),
                'sim:run' => $this->simRun($options),
                'analysis:run' => $this->analysis(),
                'urban:import' => $this->urbanImport($options),
                'urban:update' => $this->urbanUpdate($options),
                default => $this->help(),
            };
        } catch (\Fiscaliza\Domain\ValidationFailed $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL . implode(PHP_EOL, array_map(static fn($k, $v) => " - {$k}: {$v}", array_keys($e->errors), $e->errors)) . PHP_EOL);

            return 1;
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
            // Fora de produção, a causa do driver ajuda o operador local (nunca vai para respostas HTTP).
            if (!$this->services->config->isProduction() && $e->getPrevious() !== null) {
                fwrite(STDERR, 'Causa: ' . $e->getPrevious()->getMessage() . PHP_EOL);
            }

            return 1;
        }
    }

    private function migrate(): int
    {
        $migrator = $this->services->migrator();
        $applied = $migrator->migrate();
        echo $applied === [] ? "Nenhuma migração pendente.\n" : 'Aplicadas: ' . implode(', ', $applied) . PHP_EOL;
        $tables = $migrator->grantApplicationAccess($this->services->config->require('DB_USER'));
        echo 'Privilégios DML concedidos ao usuário da aplicação em ' . count($tables) . " tabelas.\n";

        return 0;
    }

    /** @param array<string, string|bool> $options */
    private function seed(array $options): int
    {
        $services = $this->services;
        if (is_string($options['perfil'] ?? null)) {
            $profile = \Fiscaliza\Demo\DemoScenario::profile($options['perfil']);
            $services = new Services($services->config->with(['DEMO_PROFILE' => $profile]), $services->root);
        }
        $owner = $services->asMigrationIdentity();
        $meta = $owner->metaRepository();
        $hasData = (int) $owner->database()->value('SELECT COUNT(*) FROM EQUIPMENT') > 0;
        if ($hasData) {
            if (!($options['reset'] ?? false)) {
                fwrite(STDERR, "O banco já tem dados. Use --reset para apagar e recarregar a demonstração.\n");

                return 1;
            }
            // Recusa apagar qualquer banco que não seja explicitamente a demonstração sintética.
            if (!$this->services->config->bool('DEMO_ALLOW_RESET') || $meta->text('dataset') !== MetaRepository::DATASET_MARKER) {
                fwrite(STDERR, "Recusado: DEMO_ALLOW_RESET=true e o marcador do conjunto sintético são obrigatórios para apagar dados.\n");

                return 1;
            }
        }
        $summary = ['perfil' => $owner->config->get('DEMO_PROFILE', 'compacto')] + $owner->seeder()->run($hasData);
        foreach ($summary as $label => $count) {
            echo str_pad($label, 34) . $count . PHP_EOL;
        }

        return 0;
    }

    /**
     * Acrescenta o cenário de multas a uma demonstração já carregada, sem apagar nada. Exige o marcador do conjunto
     * sintético; a mesma versão não é carregada duas vezes; carga anterior parcial ou de outra versão é conflito.
     */
    private function seedFines(): int
    {
        $owner = $this->services->asMigrationIdentity();
        $meta = $owner->metaRepository();
        if ($meta->text('dataset') !== MetaRepository::DATASET_MARKER) {
            fwrite(STDERR, "Recusado: este banco não tem o marcador do conjunto sintético de demonstração.\n");

            return 1;
        }
        if ($meta->text('fines_seed') === FineScenario::VERSION) {
            echo 'Cenário de multas já carregado (' . FineScenario::VERSION . "): nada foi duplicado.\n";

            return 0;
        }
        if (in_array($meta->text('fines_seed'), FineScenario::PREVIOUS, true)) {
            $count = FineScenario::upgrade($owner);
            echo 'Cenário de demonstração atualizado para ' . FineScenario::VERSION . ": {$count} registro(s) alterado(s), cada um com a mudança no histórico. Nada foi apagado.\n";

            return 0;
        }
        if ((int) $owner->database()->value("SELECT COUNT(*) FROM FINE WHERE CREATED_SOURCE = 'SEMENTE'") > 0) {
            fwrite(STDERR, "Conflito: há multas de uma carga anterior (parcial ou de outra versão) sem o marcador desta versão. Nada foi alterado.\n");

            return 1;
        }
        $profile = $meta->text('profile') ?? DemoScenario::COMPACT;
        $count = FineScenario::seed($owner, $profile);
        echo "Multas de demonstração acrescentadas: {$count} (perfil {$profile}). Registros já existentes foram mantidos.\n";

        return 0;
    }

    /** @param array<string, string|bool> $options */
    private function createUser(array $options): int
    {
        $username = (string) ($options['_0'] ?? '');
        $role = strtoupper((string) ($options['role'] ?? 'OPERADOR'));
        $name = (string) ($options['name'] ?? $username);
        $created = $this->services->auth()->createUser($username, $name, $role);
        echo "Conta criada: {$username} ({$role}).\n";
        echo "Senha (mostrada só agora, guarde em local seguro): {$created['password']}\n";

        return 0;
    }

    private function simTick(): int
    {
        $stats = $this->exclusiveTick();
        echo $stats === null ? self::SKIPPED . PHP_EOL : json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        return 0;
    }

    /**
     * Executa um ciclo só se nenhum outro processo do mesmo diretório (cron com sim:tick ou sim:run) estiver no meio
     * de um: trava exclusiva em var/simulador.lock, liberada ao fim do ciclo (ou se o processo morrer).
     *
     * @return array<string, int|string>|null null quando o ciclo foi ignorado por sobreposição
     */
    private function exclusiveTick(): ?array
    {
        $dir = $this->services->root . '/var';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Não foi possível criar var/ para a trava do simulador.');
        }
        // Abrir só para leitura basta para flock: o arquivo pode ter sido criado por outro usuário do sistema.
        $lock = @fopen($dir . '/simulador.lock', 'c') ?: @fopen($dir . '/simulador.lock', 'r');
        if ($lock === false) {
            throw new \RuntimeException('Não foi possível abrir var/simulador.lock.');
        }
        try {
            return flock($lock, LOCK_EX | LOCK_NB) ? $this->services->simulator()->tick() : null;
        } finally {
            fclose($lock);
        }
    }

    /** @param array<string, string|bool> $options */
    private function simRun(array $options): int
    {
        $interval = max(2, (int) ($options['interval'] ?? $this->services->config->int('SIM_INTERVAL_SECONDS', 5)));
        $duration = (int) ($options['duration'] ?? 0);
        $started = time();
        echo "Simulador iniciado (intervalo {$interval}s" . ($duration > 0 ? ", duração {$duration}s" : '') . ").\n";
        do {
            $began = microtime(true);
            try {
                $stats = $this->exclusiveTick();
                echo date('H:i:s') . ' ' . ($stats === null ? self::SKIPPED : json_encode($stats, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
            } catch (\RuntimeException $e) {
                // Banco indisponível: registra e tenta de novo no próximo ciclo.
                fwrite(STDERR, date('H:i:s') . ' ciclo falhou: ' . $e->getMessage() . PHP_EOL);
                $this->services->database()->disconnect();
            }
            usleep((int) max(0, ($interval - (microtime(true) - $began)) * 1_000_000));
        } while ($duration === 0 || time() - $started < $duration);

        return 0;
    }

    private function analysis(): int
    {
        $run = $this->services->analysis()->run(null);
        echo "Análise #{$run['id']} registrada: " . json_encode($run['summary'], JSON_UNESCAPED_UNICODE) . PHP_EOL;

        return 0;
    }

    /** @param array<string, string|bool> $options */
    private function urbanImport(array $options): int
    {
        $path = fn(string $key, string $default): string => is_string($options[$key] ?? null) ? $options[$key] : $this->services->root . '/' . $default;
        $result = $this->services->urbanImporter()->import(
            $path('dir', UrbanImporter::DEFAULT_RUN),
            $path('setores', UrbanImporter::DEFAULT_SECTORS),
            $path('manifesto', UrbanImporter::DEFAULT_MANIFEST),
            ($options['sem-grade'] ?? false) ? null : $path('grade', UrbanImporter::DEFAULT_GRID),
        );
        echo ($result['created'] ? "Execução #{$result['id']} importada" : "Execução já importada antes (#{$result['id']}); nada foi duplicado")
            . ": previsão de {$result['as_of']} a {$result['until']} (fim exclusivo), {$result['sectors']} setores, {$result['replay_rows']} linhas de retrospecto.\n";
        echo "Quadrados de 2 km: {$result['cells']} de 64.\n";
        echo "SHA-256 do conjunto: {$result['sha256']}\n";

        return 0;
    }

    /** @param array<string, string|bool> $options */
    private function urbanUpdate(array $options): int
    {
        $result = $this->services->urbanUpdater()->update(
            $this->services->config->get('INFOSIGA_URL', UrbanUpdater::SOURCE_URL),
            $this->services->root . '/var/infosiga',
            is_string($options['arquivo'] ?? null) ? $options['arquivo'] : null,
            (bool) ($options['forcar-download'] ?? false),
            is_string($options['semana'] ?? null) ? $options['semana'] : null,
        );
        foreach ($result['messages'] as $message) {
            echo date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        }

        return $result['ok'] ? 0 : 1;
    }

    private function help(): int
    {
        echo <<<TXT
            Uso: php bin/fiscaliza <comando>

              migrate                         aplica migrações pendentes e concede DML ao usuário da aplicação
              demo:seed [--reset] [--perfil=compacto|completo]
                                              carrega o cenário sintético de Marília (--reset apaga e recarrega;
                                              compacto = 17 equipamentos e 7 agentes, o padrão; completo = 48 e 21)
              demo:seed-multas                acrescenta o cenário de multas (12 ou 40, conforme o perfil) a uma
                                              demonstração já carregada, sem apagar nada; repetir não duplica;
                                              uma carga de versão anterior é atualizada (relatos, modalidade dos
                                              radares e o caso do radar portátil)
              user:create <usuario> [--role=ADMIN|OPERADOR] [--name="Nome"]
                                              cria conta com senha gerada (exibida uma vez)
              sim:tick                        executa um ciclo do simulador (para cron); ignora o ciclo se
                                              outro ainda estiver rodando (trava em var/simulador.lock)
              sim:run [--interval=5] [--duration=0]
                                              executa o simulador continuamente (mesma trava por ciclo)
              analysis:run                    recalcula a análise preventiva de 7 dias
              urban:import [--dir=…] [--setores=…] [--manifesto=…] [--grade=…|--sem-grade]
                                              importa uma execução completa do treino por setores (JSON validado;
                                              padrão: ml/marilia-setores/runs/primeiro-treino-verificado) e os
                                              quadrados de 2 km (ml/marilia-grade-2km/dados/contagens_grade.csv);
                                              repetir com os mesmos arquivos não duplica nada
              urban:update [--forcar-download] [--arquivo=dados_infosiga.zip] [--semana=AAAA-MM-DD]
                                              rotina agendável: baixa o Infosiga-SP uma vez por mês (guarda só
                                              12 meses de contagens de Marília) e calcula a previsão da semana

            TXT;

        return 0;
    }

    /**
     * --chave=valor, --flag e argumentos posicionais (_0, _1…).
     *
     * @param list<string> $args
     * @return array<string, string|bool>
     */
    private static function options(array $args): array
    {
        $options = [];
        $position = 0;
        foreach ($args as $arg) {
            if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m) === 1) {
                $options[$m[1]] = $m[2] ?? true;
            } else {
                $options['_' . $position++] = $arg;
            }
        }

        return $options;
    }
}
