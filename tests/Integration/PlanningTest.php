<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use DateTimeImmutable;
use Fiscaliza\Application\UrbanImporter;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Fiscaliza\Tests\Support\Parallel;

/** Planejamento por setores no Firebird real: importação, sugestão, reserva atômica, ciclo da missão e retrospecto. */
final class PlanningTest extends FirebirdTestCase
{
    private const TABLES = ['URBAN_RUN', 'URBAN_SECTOR', 'URBAN_FORECAST', 'URBAN_REPLAY'];
    private ?int $uid = null;

    public function testImportPersistsTheRunWithFullPrecisionAndIsIdempotent(): void
    {
        $first = $this->import();
        $counts = $this->counts();
        $again = $this->import();

        self::assertTrue($first['created']);
        self::assertFalse($again['created']);
        self::assertSame($first['id'], $again['id']);
        self::assertSame(['URBAN_RUN' => 1, 'URBAN_SECTOR' => 4, 'URBAN_FORECAST' => 4, 'URBAN_REPLAY' => 204], $counts);
        self::assertSame($counts, $this->counts(), 'reimportar os mesmos bytes não duplica nada');

        $sectors = $this->services->urbanRepository()->sectors($first['id']);
        $json = json_decode((string) file_get_contents(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN . '/previsao.json'), true);
        foreach ($json['sectors'] as $s) {
            self::assertSame($s['expected_count'], $sectors[$s['sector_id']]['expected_count'], "{$s['sector_id']}: valor idêntico ao do JSON");
            self::assertSame($s['expected_count_ml'], $sectors[$s['sector_id']]['expected_ml']);
            self::assertSame($s['rank'], $sectors[$s['sector_id']]['rank_no']);
        }
        $run = $this->services->urbanRepository()->latestRun();
        self::assertSame(0.027747636950151033, $run['test_relative_gain']);
        self::assertSame('CC-BY-4.0', $run['provenance']['source']['license'], 'proveniência guardada com a execução');
    }

    public function testGridIsAddedToAnExistingRunOnceAndShownWithTheWeek(): void
    {
        $run = $this->import();
        self::assertSame(0, $run['cells']);
        $grid = self::ROOT . '/' . UrbanImporter::DEFAULT_GRID;
        $args = [self::ROOT . '/' . UrbanImporter::DEFAULT_RUN, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST, $grid];

        $added = $this->services->urbanImporter()->import(...$args);
        $again = $this->services->urbanImporter()->import(...$args);

        self::assertSame([$run['id'], false, 64], [$added['id'], $added['created'], $added['cells']]);
        self::assertSame(64, $again['cells']);
        self::assertSame(64, (int) $this->services->database()->value('SELECT COUNT(*) FROM URBAN_CELL'), 'reimportar não duplica quadrados');
        $cells = $this->services->planning()->week(null)['cells'];
        self::assertSame([1, 2, 3, 4], array_column($cells, 'rank_no'));
        self::assertSame([27, 'Setor sudoeste', 198], [$cells[0]['cell_no'], $cells[0]['sector_name'], $cells[0]['count_52w']]);
        self::assertSame((198 + 0.1) / 52.1, $cells[0]['expected'], 'valor idêntico ao calculado na importação');
        self::assertGreaterThan($cells[1]['share'], $cells[0]['share']);
        self::assertSame(['inside' => ['Centro'], 'near' => null], $cells[0]['places'], 'bairro cujo centro cai no quadrado');
        self::assertSame(['inside' => [], 'near' => 'Centro'], $cells[1]['places'], 'sem bairro dentro: o mais próximo');
    }

    public function testInvalidRunLeavesNothingWritten(): void
    {
        $dir = sys_get_temp_dir() . '/fiscaliza-urbano-' . bin2hex(random_bytes(6));
        mkdir($dir, 0700);
        foreach (glob(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN . '/*.json') ?: [] as $file) {
            copy($file, $dir . '/' . basename($file));
        }
        file_put_contents("{$dir}/retrospecto.json", substr((string) file_get_contents("{$dir}/retrospecto.json"), 0, 50_000));
        try {
            $this->services->urbanImporter()->import($dir, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST);
            self::fail('Retrospecto truncado foi aceito.');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('retrospecto.json', $e->errors);
        } finally {
            array_map(unlink(...), glob("{$dir}/*") ?: []);
            rmdir($dir);
        }
        self::assertSame(array_fill_keys(self::TABLES, 0), $this->counts());
    }

    public function testImportedRunCannotBeRewrittenByTheApplicationUser(): void
    {
        $id = $this->import()['id'];

        $this->expectException(\RuntimeException::class);
        $this->services->database()->execute("UPDATE URBAN_FORECAST SET EXPECTED_COUNT = 0 WHERE RUN_ID = ? AND SECTOR_ID = 'SO'", [$id]);
    }

    public function testPoissonComparisonUsesItsOwnValuesWithoutChangingTheImportedRun(): void
    {
        $this->import();
        $baseline = $this->services->planning()->week(null);
        $poisson = $this->services->planning()->week('poisson');

        self::assertSame('baseline', $baseline['method'], 'padrão = método escolhido no ajuste');
        self::assertSame(5.7739887317055345, $poisson['sectors']['SO']['value']);
        self::assertSame(5.622641509433962, $baseline['sectors']['SO']['value']);
        self::assertSame(1, $poisson['sectors']['SO']['rank_no'], 'a ordem importada fica preservada');
        self::assertSame('FUTURA', $baseline['period'], 'relógio de teste em 18/10, previsão a partir de 20/10');
        self::assertStringContainsString('(297 + 1) ÷ 53 = 5,6', implode(' ', $baseline['sectors']['SO']['explanation']));
        self::assertStringContainsString('não decompõe', implode(' ', $poisson['sectors']['SO']['explanation']));

        // Numa semana do retrospecto em que os métodos discordam, trocar o método troca o setor priorizado.
        $week = $this->services->planning()->replay('2025-01-06', 'baseline', false);
        self::assertSame('NO', $week['first']);
        self::assertSame('SO', $this->services->planning()->replay('2025-01-06', 'poisson', false)['first']);
    }

    public function testSuggestionOnlyListsFreeTrafficAgentsAndFlagsPositionsThatAreNotCurrent(): void
    {
        $runId = $this->import()['id'];
        $inside = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $far = $this->agentAt('Hermes 11', '-22.150000', '-49.870000');
        $baseOnly = $this->createAgent(['call_sign' => 'Hermes 12', 'base_lat' => '-22.250000', 'base_lng' => '-49.990000']);
        $stale = $this->agentAt('Hermes 13', '-22.250000', '-49.970000', '-20 minutes');
        $guard = $this->createAgent(['call_sign' => 'Atena 10', 'role' => 'GUARDA', 'base_lat' => '-22.250000', 'base_lng' => '-49.980000']);
        $paused = $this->createAgent(['call_sign' => 'Hermes 14', 'status' => 'EM_PAUSA']);
        $archived = $this->createAgent(['call_sign' => 'Hermes 15']);
        $this->services->agents()->archive($archived, 'transferido (teste)', $this->userId());
        $reserved = $this->agentAt('Hermes 16', '-22.250000', '-49.980000');
        $this->confirm($runId, $reserved);

        $c = $this->services->planning()->candidates($this->services->urbanRepository()->sectors($runId)['SO']);

        self::assertSame([$inside, $far, $stale, $baseOnly], array_column($c['eligible'], 'id'), 'posição atual (perto → longe), atrasada, só base');
        self::assertSame([0, 0, 1, 2], array_column($c['eligible'], 'tier'));
        self::assertSame('dentro do setor', $c['eligible'][0]['distance_label']);
        self::assertStringContainsString('km em linha reta até a borda do setor', $c['eligible'][1]['distance_label']);
        self::assertSame('base de trabalho (sem posição recente)', $c['eligible'][3]['point']);
        $unavailable = array_column($c['unavailable'], 'why', 'id');
        self::assertSame('Em pausa', $unavailable[$paused]);
        self::assertStringStartsWith('Reservado na missão fictícia #', $unavailable[$reserved]);
        $listed = [...array_column($c['eligible'], 'id'), ...array_keys($unavailable)];
        self::assertNotContains($guard, $listed, 'guarda não é escalado como trânsito');
        self::assertNotContains($archived, $listed);
    }

    public function testConfirmationRechecksEligibilityAndRequiresJustificationForAManualChoice(): void
    {
        $runId = $this->import()['id'];
        $suggested = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $other = $this->agentAt('Hermes 11', '-22.150000', '-49.870000');
        $guard = $this->createAgent(['call_sign' => 'Atena 10', 'role' => 'GUARDA']);

        $this->assertRefused(fn() => $this->confirm($runId, $guard), 'Só agentes de trânsito');
        try {
            $this->confirm($runId, $other, ['justification' => '']);
            self::fail('Escolha manual sem justificativa foi aceita.');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('justification', $e->errors);
        }
        $this->assertRefused(fn() => $this->services->planning()->confirm(['run_id' => $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => $suggested], $this->userId()), 'Marque a confirmação', ValidationFailed::class);
        $manual = $this->confirm($runId, $other, ['justification' => 'conhece o trecho']);
        self::assertSame(['MANUAL', 'conhece o trecho'], [$this->services->missionRepository()->find($manual)['selection'], $this->services->missionRepository()->find($manual)['justification']]);

        $id = $this->confirm($runId, $suggested);
        $mission = $this->services->missionRepository()->find($id);
        self::assertSame(['CONFIRMADA', 'SUGERIDO', 'SO', 'baseline', 1, 5.622641509433962, '2026-10-20', '2026-10-27'], [$mission['state'], $mission['selection'], $mission['sector_id'],
            $mission['method'], (int) $mission['rank_no'], $mission['expected_count'], $mission['period_from'], $mission['period_until']]);
        self::assertSame($this->userId(), (int) $mission['confirmed_by']);
        self::assertStringContainsString('Setor sudoeste: 1º de 4 (Referência histórica), 5,6 registros esperados em 7 dias. Hermes 10: em atividade, posição atual, dentro do setor.', $mission['reason']);
        $this->assertRefused(fn() => $this->confirm($runId, $suggested), 'já está reservado');
    }

    public function testClosingReleasesTheAgentAndKeepsTheHistory(): void
    {
        $runId = $this->import()['id'];
        $agent = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $first = $this->confirm($runId, $agent);

        $this->services->planning()->close($first, 'CANCELADA', 'chuva forte (teste)', $this->userId());
        $this->assertRefused(fn() => $this->services->planning()->close($first, 'CONCLUIDA', 'de novo', $this->userId()), 'já está cancelada');
        $second = $this->confirm($runId, $agent);
        $this->services->planning()->close($second, 'CONCLUIDA', 'ronda feita (teste)', $this->userId());

        $logs = $this->services->missionRepository()->logs([$first, $second]);
        self::assertSame([[null, 'CONFIRMADA'], ['CONFIRMADA', 'CANCELADA']], array_map(static fn($l) => [$l['from_state'], $l['to_state']], $logs[$first]));
        self::assertSame('ronda feita (teste)', $logs[$second][1]['note']);
        self::assertSame('CANCELADA', $this->services->missionRepository()->find($first)['state']);
        self::assertSame([$agent], array_column($this->services->planning()->candidates($this->services->urbanRepository()->sectors($runId)['SO'])['eligible'], 'id'), 'liberado de novo');
    }

    public function testTheDatabaseRefusesASecondActiveMissionForTheSameAgent(): void
    {
        $runId = $this->import()['id'];
        $agent = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $this->confirm($runId, $agent);

        $this->expectException(ConstraintViolation::class);
        $this->services->missionRepository()->insert(['run_id' => $runId, 'sector_id' => 'NO', 'period_from' => '2026-10-20', 'period_until' => '2026-10-27',
            'method' => 'baseline', 'expected_count' => 1.0, 'rank_no' => 2, 'agent_id' => $agent, 'selection' => 'SUGERIDO', 'reason' => 'atalho sem o serviço',
            'justification' => null, 'support_point' => null, 'note' => null, 'confirmed_at' => Time::toDb($this->clock->now()), 'confirmed_by' => $this->userId()]);
    }

    public function testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict(): void
    {
        $runId = $this->import()['id'];
        $agent = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $other = $this->services->auth()->createUser('gestora2', 'Gestora Dois', 'OPERADOR', 'senha-de-teste-longa')['id'];

        foreach (range(1, 3) as $round) {
            $results = Parallel::run([['mission', (string) $runId, 'SO', (string) $agent, (string) $this->userId()], ['mission', (string) $runId, 'SO', (string) $agent, (string) $other]], $this->clock->now());

            self::assertCount(1, array_filter($results, static fn($r) => $r['ok']), "rodada {$round}: " . (json_encode($results, JSON_UNESCAPED_UNICODE) ?: ''));
            foreach (array_filter($results, static fn($r) => !$r['ok']) as $refused) {
                self::assertContains($refused['class'], ['RuleViolation', 'ConcurrencyConflict'], $refused['message'] ?? '');
            }
            self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM URBAN_MISSION WHERE AGENT_ID = ? AND STATE = 'CONFIRMADA'", [$agent]));
            $active = (int) $this->services->database()->value("SELECT ID FROM URBAN_MISSION WHERE AGENT_ID = ? AND STATE = 'CONFIRMADA'", [$agent]);
            $this->services->planning()->close($active, 'CANCELADA', "fim da rodada {$round}", $this->userId());
        }
    }

    public function testSimulatorTicksNeverTouchAConfirmedMission(): void
    {
        $runId = $this->import()['id'];
        $agent = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $id = $this->confirm($runId, $agent);
        $before = $this->services->database()->one('SELECT * FROM URBAN_MISSION WHERE ID = ?', [$id]);

        foreach (range(1, 40) as $i) {
            $this->services->withClock(new Clock($this->clock->now()->modify("+{$i} minutes")))->simulator()->tick();
        }

        self::assertSame($before, $this->services->database()->one('SELECT * FROM URBAN_MISSION WHERE ID = ?', [$id]), 'reserva, método e evidência intactos');
        self::assertNotSame('-22.250000', (string) $this->services->agentRepository()->find($agent)['pos_lat'], 'o simulador mexeu na posição, não na missão');
    }

    public function testFinishedPeriodRefusesNewMissions(): void
    {
        $runId = $this->import()['id'];
        $agent = $this->agentAt('Hermes 10', '-22.250000', '-49.980000');
        $later = $this->services->withClock(new Clock(new DateTimeImmutable('2026-10-27 12:00:00', Time::utc())));

        self::assertSame('ENCERRADA', $later->planning()->week(null)['period']);
        $this->assertRefused(fn() => $later->planning()->confirm(['run_id' => $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => $agent, 'confirm' => '1'], $this->userId()), 'já terminou');
    }

    public function testReplayShowsFrozenPredictionsForEveryWeekAndRevealsObservedOnlyOnRequest(): void
    {
        $this->import();
        $file = json_decode((string) file_get_contents(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN . '/retrospecto.json'), true);
        $hidden = $this->services->planning()->replay(null, null, false);

        self::assertCount(51, $hidden['weeks']);
        self::assertSame('2025-01-06', $hidden['week']);
        self::assertSame('2025-01-01', $hidden['run']['model_issued_at'], 'modelo congelado no início do teste, não o de 2026');
        self::assertSame([null, null, null, null], array_column($hidden['sectors'], 'observed'));
        self::assertNull($hidden['week_result']);

        $last = $this->services->planning()->replay('2025-12-22', 'poisson', true);
        $rows = array_filter($file['rows'], static fn($r) => $r['from'] === '2025-12-22');
        foreach ($rows as $r) {
            $s = array_values(array_filter($last['sectors'], static fn($x) => $x['sector_id'] === $r['sector_id']))[0];
            self::assertSame([$r['expected_count_ml'], $r['observed_count']], [$s['value'], $s['observed']], $r['sector_id']);
        }
        self::assertSame(array_sum(array_column($rows, 'observed_count')), $last['week_result']['observed']);
        self::assertNull($last['next']);
    }

    /** @return array{id: int, created: bool} */
    private function import(): array
    {
        return $this->services->urbanImporter()->import(self::ROOT . '/' . UrbanImporter::DEFAULT_RUN, self::ROOT . '/' . UrbanImporter::DEFAULT_SECTORS, self::ROOT . '/' . UrbanImporter::DEFAULT_MANIFEST);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return array_combine(self::TABLES, array_map(fn(string $t): int => (int) $this->services->database()->value("SELECT COUNT(*) FROM {$t}"), self::TABLES));
    }

    /** Agente de trânsito em atividade com posição observada (atual por padrão). */
    private function agentAt(string $callSign, string $lat, string $lng, string $age = '-1 minute'): int
    {
        $id = $this->createAgent(['call_sign' => $callSign]);
        $at = Time::toDb($this->clock->now()->modify($age));
        $this->services->agentRepository()->movePosition($id, $lat, $lng, $at, $at);

        return $id;
    }

    /** @param array<string, string> $extra */
    private function confirm(int $runId, int $agentId, array $extra = []): int
    {
        return $this->services->planning()->confirm($extra + ['run_id' => (string) $runId, 'sector_id' => 'SO', 'method' => 'baseline', 'agent_id' => (string) $agentId,
            'confirm' => '1', 'justification' => 'teste'], $this->userId());
    }

    /** @param class-string<\Throwable> $class */
    private function assertRefused(callable $action, string $message, string $class = RuleViolation::class): void
    {
        try {
            $action();
            self::fail("Era esperada a recusa: {$message}");
        } catch (\Throwable $e) {
            self::assertInstanceOf($class, $e);
            self::assertStringContainsString($message, $e instanceof ValidationFailed ? implode(' ', $e->errors) : $e->getMessage());
        }
    }

    private function userId(): int
    {
        return $this->uid ??= $this->services->auth()->createUser('gestora', 'Gestora de Teste', 'OPERADOR', 'senha-de-teste-longa')['id'];
    }
}
