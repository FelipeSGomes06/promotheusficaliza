<?php

// Prepara, pelos serviços da aplicação, os volumes que a revisão de 24/10/2026 (ADR-0023) precisa ver no navegador e
// que a carga de demonstração não tem: uma ordem antiga agendada atrás de 31 encerradas (R03), um equipamento não
// instalado para abrir ordem pela ficha (R02) e 22 missões confirmadas ao mesmo tempo (R07). Use só em banco de
// demonstração. `--limpar` cancela as missões e arquiva os agentes e equipamentos criados aqui (nada é apagado).
// Uso: php scripts/verificacao-interface/preparar-revisao.php <usuario> [--limpar]   (imprime JSON com os IDs)
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$s = new Fiscaliza\Services(Fiscaliza\Config::load($root)->without(Fiscaliza\Config::PRIVILEGED), $root);
$actor = (int) ($s->userRepository()->findByUsername((string) ($argv[1] ?? ''))['id'] ?? throw new RuntimeException('Conta não encontrada.'));
$db = $s->database();
$tag = 'REV1024';
$suffix = base_convert((string) time(), 10, 36); // séries únicas por fabricante: cada preparo usa as suas

if (in_array('--limpar', $argv, true)) {
    foreach ($db->all("SELECT m.ID FROM URBAN_MISSION m JOIN AGENT g ON g.ID = m.AGENT_ID WHERE m.STATE = 'CONFIRMADA' AND g.CALL_SIGN STARTING WITH 'Revisão '") as $m) {
        $s->planning()->close((int) $m['id'], 'CANCELADA', 'Fim da verificação da revisão de 24/10/2026.', $actor);
    }
    foreach ($db->all("SELECT ID FROM AGENT WHERE CALL_SIGN STARTING WITH 'Revisão ' AND ARCHIVED_AT IS NULL") as $a) {
        $s->agents()->archive((int) $a['id'], 'Fim da verificação da revisão de 24/10/2026.', $actor);
    }
    foreach ($db->all('SELECT ID FROM EQUIPMENT WHERE SERIAL_NUMBER STARTING WITH ? AND ARCHIVED_AT IS NULL', [$tag]) as $e) {
        $s->equipment()->archive((int) $e['id'], 'Fim da verificação da revisão de 24/10/2026.', $actor, true);
    }
    echo json_encode(['limpo' => true]), PHP_EOL;
    exit(0);
}

$agency = (string) $s->referenceRepository()->agencies()[0]['id'];
$region = (string) $s->referenceRepository()->regions()[0]['id'];
$camera = static fn(string $serial, array $extra = []): array => $extra + [
    'type_code' => 'CAMERA', 'status' => 'OPERACIONAL', 'manufacturer' => 'Óptica Revisão', 'model' => 'OR-1', 'serial_number' => $serial,
    'agency_id' => $agency, 'street' => 'Avenida Sampaio Vidal', 'address_ref' => 'verificação da revisão', 'cep' => '17500-200', 'region_id' => $region,
    'lat' => '-22.2112', 'lng' => '-49.9515', 'location_kind' => 'INSTALADO', 'specs' => ['resolution' => '1080P', 'night_vision' => '1'],
];

// R03: uma ordem agendada antiga e, depois, 31 encerradas mais recentes.
$history = $s->equipment()->create($camera("{$tag}-HIST-{$suffix}"), $actor);
$old = $s->maintenance()->open($history, ['kind' => 'PREVENTIVA', 'priority' => 'PROGRAMADA', 'due_date' => date('Y-m-d', strtotime('+20 days')), 'reason' => 'Revisão antiga ainda agendada (verificação).'], $actor);
for ($i = 1; $i <= 31; $i++) {
    $order = $s->maintenance()->open($history, ['kind' => 'CORRETIVA', 'priority' => 'IMEDIATA', 'reason' => "Ordem encerrada nº {$i} (verificação)."], $actor);
    $s->maintenance()->cancel($order, 'Aberta só para formar histórico na verificação.', $actor);
}

// R02: equipamento ainda não instalado; a ordem é aberta pela ficha, no navegador.
$planned = $s->equipment()->create($camera("{$tag}-PLAN-{$suffix}", ['status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO']), $actor);

// R07: 22 missões confirmadas ao mesmo tempo, uma por agente novo.
$run = $s->urbanRepository()->latestRun() ?? throw new RuntimeException('Sem execução importada (urban:import).');
$missions = [];
for ($i = 1; $i <= 22; $i++) {
    $agent = $s->agents()->create(['call_sign' => sprintf('Revisão %02d', $i), 'role' => 'TRANSITO', 'agency_id' => $agency, 'status' => 'EM_ATIVIDADE',
        'base_name' => 'Base da verificação', 'base_lat' => '-22.2165', 'base_lng' => '-49.9490'], $actor);
    $missions[$agent] = $s->planning()->confirm(['run_id' => (string) $run['id'], 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => (string) $agent,
        'confirm' => '1', 'justification' => 'Volume para a verificação da revisão.'], $actor);
}

echo json_encode(['historico' => $history, 'ordem_antiga' => $old, 'nao_instalado' => $planned, 'missoes' => $missions], JSON_PRETTY_PRINT), PHP_EOL;
