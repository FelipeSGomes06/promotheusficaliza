<?php

// Avaliação do método de atenção preventiva em várias sementes do cenário sintético.
// ATENÇÃO: SUBSTITUI os dados de domínio do banco DB_DSN (equipamentos, agentes, ordens, eventos, alertas e análises;
// contas são preservadas) pelo cenário de cada semente, em sequência. Ao terminar, fica carregado o cenário da
// ÚLTIMA semente informada. Mesmas proteções de "demo:seed --reset": DEMO_ALLOW_RESET=true e, se houver dados,
// o marcador do conjunto sintético. Uso: php scripts/avaliar-sementes.php 2027 2028 ...
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$config = Fiscaliza\Config::load(dirname(__DIR__));
$owner = (new Fiscaliza\Services($config, dirname(__DIR__)))->asMigrationIdentity();
$hasData = (int) $owner->database()->value('SELECT COUNT(*) FROM EQUIPMENT') > 0;
if (!$config->bool('DEMO_ALLOW_RESET') || ($hasData && $owner->metaRepository()->text('dataset') !== Fiscaliza\Infrastructure\Repository\MetaRepository::DATASET_MARKER)) {
    fwrite(STDERR, "Recusado: DEMO_ALLOW_RESET=true e o marcador do conjunto sintético são obrigatórios para substituir dados.\n");
    exit(1);
}
$seeds = array_map('intval', array_slice($argv, 1)) ?: [2027, 2028, 2029, 2030, 2031];
fwrite(STDERR, 'Substituindo os dados de demonstração pelas sementes ' . implode(', ', $seeds) . '; ao final ficará a semente ' . end($seeds) . ".\n");
// Indefinido (denominador zero, ex.: nenhum alerta) aparece como "—", nunca como 0.
$fmt = static fn(?float $v): string => $v === null ? '  —  ' : sprintf('%.3f', $v);
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
        sprintf('%d/%d/%d %s %s', $m['tp'], $m['fp'], $m['fn'], $fmt($m['precision']), $fmt($m['recall'])),
        sprintf('%d/%d/%d %s %s', $b['tp'], $b['fp'], $b['fn'], $fmt($b['precision']), $fmt($b['recall'])),
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
printf("\nCenário carregado agora: semente %d. Para voltar ao padrão: php bin/fiscaliza demo:seed --reset (usa SIM_SEED).\n", end($seeds));
