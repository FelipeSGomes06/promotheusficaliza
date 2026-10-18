<?php

// Avaliação do método de atenção preventiva em várias sementes do cenário sintético.
// ATENÇÃO: recarrega o banco de demonstração (exige DEMO_ALLOW_RESET=true). Uso: php scripts/avaliar-sementes.php 2027 2028 ...
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$config = Fiscaliza\Config::load(dirname(__DIR__));
if (!$config->bool('DEMO_ALLOW_RESET')) {
    fwrite(STDERR, "Recusado: DEMO_ALLOW_RESET=false\n");
    exit(1);
}
$seeds = array_map('intval', array_slice($argv, 1)) ?: [2027, 2028, 2029, 2030, 2031];
$total = ['model' => ['tp' => 0, 'fp' => 0, 'fn' => 0, 'positives' => 0, 'pairs' => 0], 'base' => ['tp' => 0, 'fp' => 0, 'fn' => 0]];
printf("%-6s %-6s %-30s %-30s %s\n", 'seed', 'limiar', 'modelo (tp/fp/fn p r)', 'referência (tp/fp/fn p r)', 'prevalência');
foreach ($seeds as $seed) {
    $services = (new Fiscaliza\Services($config->with(['SIM_SEED' => (string) $seed]), dirname(__DIR__)))->asMigrationIdentity();
    $hasData = (int) $services->database()->value('SELECT COUNT(*) FROM EQUIPMENT') > 0;
    $services->seeder()->run($hasData);
    $ev = $services->analysisRepository()->latestRun()['evaluation'];
    $m = $ev['model'];
    $b = $ev['baseline_recent_failure'];
    printf(
        "%-6d %-6d %-30s %-30s %.3f\n",
        $seed,
        $ev['chosen_threshold'],
        sprintf('%d/%d/%d %.3f %.3f', $m['tp'], $m['fp'], $m['fn'], $m['precision'] ?? 0, $m['recall'] ?? 0),
        sprintf('%d/%d/%d %.3f %.3f', $b['tp'], $b['fp'], $b['fn'], $b['precision'] ?? 0, $b['recall'] ?? 0),
        $m['prevalence'] ?? 0,
    );
    foreach (['tp', 'fp', 'fn', 'positives', 'pairs'] as $k) {
        $total['model'][$k] += $m[$k];
    }
    foreach (['tp', 'fp', 'fn'] as $k) {
        $total['base'][$k] += $b[$k];
    }
}
$p = static fn($t) => $t['tp'] + $t['fp'] === 0 ? 0 : $t['tp'] / ($t['tp'] + $t['fp']);
$r = static fn($t) => $t['tp'] + $t['fn'] === 0 ? 0 : $t['tp'] / ($t['tp'] + $t['fn']);
printf(
    "\nAgregado: modelo precisão %.3f revocação %.3f | referência precisão %.3f revocação %.3f | prevalência %.3f (%d pares, %d falhas)\n",
    $p($total['model']),
    $r($total['model']),
    $p($total['base']),
    $r($total['base']),
    $total['model']['positives'] / max(1, $total['model']['pairs']),
    $total['model']['pairs'],
    $total['model']['positives'],
);
