<?php

declare(strict_types=1);

namespace Fiscaliza\Cli;

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
                'user:create' => $this->createUser($options),
                'sim:tick' => $this->simTick(),
                'sim:run' => $this->simRun($options),
                'analysis:run' => $this->analysis(),
                default => $this->help(),
            };
        } catch (\Fiscaliza\Domain\ValidationFailed $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL . implode(PHP_EOL, array_map(static fn($k, $v) => " - {$k}: {$v}", array_keys($e->errors), $e->errors)) . PHP_EOL);

            return 1;
        } catch (\RuntimeException $e) {
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
        $owner = $this->services->asMigrationIdentity();
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
        $summary = $owner->seeder()->run($hasData);
        foreach ($summary as $label => $count) {
            echo str_pad($label, 34) . $count . PHP_EOL;
        }

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

    private function help(): int
    {
        echo <<<TXT
            Uso: php bin/fiscaliza <comando>

              migrate                         aplica migrações pendentes e concede DML ao usuário da aplicação
              demo:seed [--reset]             carrega o cenário sintético de Marília (--reset apaga e recarrega)
              user:create <usuario> [--role=ADMIN|OPERADOR] [--name="Nome"]
                                              cria conta com senha gerada (exibida uma vez)
              sim:tick                        executa um ciclo do simulador (para cron); ignora o ciclo se
                                              outro ainda estiver rodando (trava em var/simulador.lock)
              sim:run [--interval=5] [--duration=0]
                                              executa o simulador continuamente (mesma trava por ciclo)
              analysis:run                    recalcula a análise preventiva de 7 dias

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
