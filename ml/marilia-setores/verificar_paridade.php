<?php
declare(strict_types=1);
// Executar localmente: php verificar_paridade.php runs/NOME/vetores_paridade.json
$path = $argv[1] ?? throw new InvalidArgumentException('Informe o JSON de vetores.');
$payload = json_decode(file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
$model = $payload['model'];
$maxError = 0.0;
foreach ($payload['vectors'] as $vector) {
    $linear = $model['intercept'];
    foreach ($model['coefficients'] as $i => $coefficient) {
        $linear += $coefficient * ($vector['x'][$i] - $model['mean'][$i]) / $model['scale'][$i];
    }
    $actual = exp($linear);
    $error = abs($actual - $vector['expected']);
    $maxError = max($maxError, $error);
    if (!is_finite($actual) || $error > 1e-10 * max(1.0, abs($vector['expected']))) {
        throw new RuntimeException('Previsao PHP diverge do Python.');
    }
}
echo json_encode(['status' => 'ok', 'vectors' => count($payload['vectors']), 'max_error' => $maxError], JSON_THROW_ON_ERROR) . PHP_EOL;
