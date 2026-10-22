<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use DateTimeImmutable;
use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\MissionRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;
use Fiscaliza\Infrastructure\Repository\UrbanRepository;

/**
 * Planejamento preventivo semanal por setores (ADR-0016): a semana prevista pela execução importada, a explicação
 * pelas evidências, a sugestão de agente de trânsito, a missão fictícia confirmada pelo gestor e o retrospecto.
 *
 * A sugestão é uma regra operacional simples, separada do modelo: agentes de trânsito em atividade e sem missão
 * vigente, ordenados pela confiabilidade da posição (atual → atrasada → só a base) e pela distância em linha reta até o
 * retângulo do setor. Nada é enviado a pessoas: missão e agentes são fictícios.
 */
final class PlanningService
{
    public const STATES = ['CONFIRMADA' => 'Confirmada', 'CONCLUIDA' => 'Concluída', 'CANCELADA' => 'Cancelada'];
    /** Quadrados prioritários que a tela pode mostrar (1 a 4). */
    public const MAX_CELLS = 4;
    private const POINT = [0 => 'posição atual', 1 => 'posição atrasada', 2 => 'base de trabalho (sem posição recente)'];
    /** @var list<array<string, mixed>>|null bairros de referência, lidos uma vez (a grade pede 64 quadrados) */
    private ?array $regions = null;

    public function __construct(
        private readonly Database $db,
        private readonly UrbanRepository $urban,
        private readonly MissionRepository $missions,
        private readonly AgentRepository $agents,
        private readonly ReferenceRepository $references,
        private readonly Clock $clock,
    ) {}

    /**
     * Semana prevista pela execução mais recente, com os setores na ordem do método escolhido.
     *
     * @return array<string, mixed>|null null quando nenhuma execução foi importada
     */
    public function week(?string $method): ?array
    {
        $run = $this->urban->latestRun();
        if ($run === null) {
            return null;
        }
        $method = self::method($method, $run);

        return [
            'run' => $run + ['last_day' => self::addDays((string) $run['until_date'], -1)],
            'method' => $method,
            'sectors' => self::ordered($this->urban->sectors($run['id']), $method),
            // Quadrados de 2 km pela média simples (independem do método dos setores); vazio se a grade não foi importada.
            'cells' => array_map(fn(array $c): array => $c + ['places' => $this->places($c)], $this->urban->topCells($run['id'], self::MAX_CELLS)),
            // Grade inteira para o mapa de calor (a lista fica só com os prioritários de 'cells').
            'grid' => array_map(fn(array $c): array => $c + ['places' => $this->places($c)], $this->urban->allCells($run['id'])),
            'period' => self::period($run, Time::localDate($this->clock->now())),
        ];
    }

    /**
     * Candidatos para reforçar um setor: elegíveis em ordem de sugestão e, à parte, os de trânsito indisponíveis com o
     * motivo. Guarda municipal e polícia não entram (não são escalados como trânsito por proximidade).
     *
     * @param array<string, mixed> $sector linha de UrbanRepository::sectors
     * @return array{eligible: list<array<string, mixed>>, unavailable: list<array<string, mixed>>}
     */
    public function candidates(array $sector): array
    {
        $now = $this->clock->now();
        $reserved = $this->missions->activeByAgent();
        $eligible = [];
        $unavailable = [];
        foreach ($this->agents->allActive() as $agent) {
            if ($agent['role'] !== AgentRole::Transito->value) {
                continue;
            }
            $candidate = self::candidate($agent, $sector, $now);
            $mission = $reserved[(int) $agent['id']] ?? null;
            if ($agent['status'] !== AgentStatus::EmAtividade->value) {
                $unavailable[] = $candidate + ['why' => AgentStatus::from($agent['status'])->label()];
            } elseif ($mission !== null) {
                $unavailable[] = $candidate + ['why' => "Reservado na missão fictícia #{$mission}"];
            } else {
                $eligible[] = $candidate;
            }
        }
        usort($eligible, static fn(array $a, array $b): int => [$a['tier'], $a['distance_km'], $a['code']] <=> [$b['tier'], $b['distance_km'], $b['code']]);

        return ['eligible' => $eligible, 'unavailable' => $unavailable];
    }

    /**
     * Confirma uma missão fictícia. A elegibilidade é conferida de novo com a linha do agente travada; o índice
     * único parcial garante uma única missão vigente por agente mesmo com confirmações simultâneas.
     *
     * @param array<string, mixed> $data
     * @return int identificador da missão
     */
    public function confirm(array $data, int $actorId): int
    {
        $input = new Input($data);
        $runId = (int) $input->int('run_id', 1, PHP_INT_MAX);
        $sectorId = (string) $input->choice('sector_id', SectorForecast::SECTORS);
        $method = (string) $input->choice('method', array_keys(SectorForecast::METHODS));
        $agentId = (int) $input->int('agent_id', 1, PHP_INT_MAX);
        $suggestedId = $input->int('suggested_agent_id', 1, PHP_INT_MAX, false);
        $justification = $input->text('justification', 300, false);
        $support = $input->text('support_point', 160, false);
        $note = $input->text('note', 300, false);
        $cellNo = $input->int('cell_no', 0, 63, false);
        if (!$input->flag('confirm')) {
            $input->fail('confirm', 'Marque a confirmação de que esta é uma missão fictícia de demonstração.');
        }
        $input->assertValid();
        $now = $this->clock->now();

        return $this->db->transaction(function () use ($runId, $sectorId, $method, $agentId, $suggestedId, $justification, $support, $note, $cellNo, $now, $actorId): int {
            $run = $this->urban->latestRun();
            if ($run === null || $run['id'] !== $runId) {
                throw new RuleViolation('A previsão foi atualizada depois que você abriu a tela. Revise os números novos antes de confirmar.');
            }
            if (self::period($run, Time::localDate($now)) === 'ENCERRADA') {
                throw new RuleViolation('O período desta previsão já terminou. Importe uma execução nova antes de planejar (php bin/fiscaliza urban:import).');
            }
            $sectors = $this->urban->sectors($runId);
            $sector = $sectors[$sectorId] ?? throw new NotFound('Setor não encontrado.');
            $cell = $cellNo === null ? null : ($this->urban->cell($runId, $cellNo) ?? throw new NotFound('Quadrado não encontrado.'));
            if ($cell !== null && $cell['sector_id'] !== $sectorId) {
                throw new RuleViolation('O quadrado escolhido não fica no setor informado. Recarregue a tela.');
            }
            // A sugestão e a distância são para o quadrado, quando há; senão, para o setor.
            $target = $cell === null ? $sector : $cell + ['area_label' => 'quadrado'];
            $agent = $this->agents->lock($agentId) ?? throw new NotFound('Agente não encontrado.');
            if ($agent['role'] !== AgentRole::Transito->value) {
                throw new RuleViolation('Só agentes de trânsito entram no reforço preventivo; guarda e polícia não são escalados por proximidade.');
            }
            if ($agent['archived_at'] !== null) {
                throw new RuleViolation('Agente arquivado não recebe missão.');
            }
            if ($agent['status'] !== AgentStatus::EmAtividade->value) {
                throw new RuleViolation("{$agent['call_sign']} está agora \"" . AgentStatus::from($agent['status'])->label() . '"; escolha um agente em atividade.');
            }
            $busy = $this->missions->activeForAgent($agentId);
            if ($busy !== null) {
                throw new RuleViolation("{$agent['call_sign']} já está reservado na missão fictícia #{$busy}.");
            }
            $eligible = $this->candidates($target)['eligible'];
            $chosen = array_values(array_filter($eligible, static fn(array $c): bool => $c['id'] === $agentId))[0] ?? self::candidate($agent, $target, $now);
            // A ordem muda com o relógio e o simulador: vale o sugerido que a tela mostrou (ou o primeiro de agora).
            // Limite conhecido: suggested_agent_id vem do cliente e só decide se a justificativa é exigida (auditoria, não segurança).
            $selection = in_array($agentId, [$suggestedId, $eligible[0]['id'] ?? null], true) ? 'SUGERIDO' : 'MANUAL';
            if ($selection === 'MANUAL' && $justification === null) {
                throw new ValidationFailed(['justification' => 'Explique por que escolheu um agente diferente do sugerido.']);
            }
            $ranked = self::ordered($sectors, $method)[$sectorId];
            $place = $cell === null ? '' : sprintf('Quadrado %d (%s), %s registros esperados em 7 dias. ', $cell['rank_no'], self::placeLabel($this->places($cell)), self::fmt($cell['expected']));
            $reason = $place . sprintf(
                '%s: %dº de 4, %s registros esperados em 7 dias. %s: em atividade, %s, %s.',
                $sector['name'],
                $ranked['rank'],
                self::fmt($ranked['value']),
                $agent['call_sign'],
                self::POINT[$chosen['tier']],
                $chosen['distance_label'],
            );
            $at = Time::toDb($now);
            try {
                $id = $this->missions->insert([
                    'run_id' => $runId, 'sector_id' => $sectorId, 'period_from' => $run['as_of'], 'period_until' => $run['until_date'], 'method' => $method,
                    'expected_count' => $ranked['value'], 'rank_no' => $ranked['rank'], 'agent_id' => $agentId, 'selection' => $selection,
                    'reason' => mb_substr($reason, 0, 300), 'justification' => $selection === 'MANUAL' ? $justification : null, 'support_point' => $support,
                    'note' => $note, 'confirmed_at' => $at, 'confirmed_by' => $actorId, 'cell_no' => $cell === null ? null : $cellNo,
                ]);
            } catch (ConstraintViolation $e) {
                // Barreira do banco para a corrida que a trava e a verificação acima não pegarem.
                throw $e->constraint === 'UX_URBAN_MISSION_ACTIVE' ? new RuleViolation("Outra pessoa acabou de reservar {$agent['call_sign']}. Escolha outro agente.") : $e;
            }
            $this->missions->log($id, null, 'CONFIRMADA', $actorId, $at, $note);

            return $id;
        });
    }

    /** Conclui ou cancela uma missão vigente (libera o agente para novas sugestões), com motivo e histórico. */
    public function close(int $missionId, string $state, string $reason, int $actorId): void
    {
        if (!in_array($state, ['CONCLUIDA', 'CANCELADA'], true)) {
            throw new ValidationFailed(['state' => 'Situação final inválida.']);
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 300) {
            throw new ValidationFailed(['reason' => 'Informe o motivo (até 300 caracteres).']);
        }
        $this->db->transaction(function () use ($missionId, $state, $reason, $actorId): void {
            $mission = $this->missions->lock($missionId) ?? throw new NotFound('Missão não encontrada.');
            $at = Time::toDb($this->clock->now());
            if ($mission['state'] !== 'CONFIRMADA' || !$this->missions->close($missionId, $state, $at, $actorId, $reason)) {
                throw new RuleViolation("A missão #{$missionId} já está " . mb_strtolower(self::STATES[$mission['state']] ?? $mission['state']) . '; nada foi alterado.');
            }
            $this->missions->log($missionId, 'CONFIRMADA', $state, $actorId, $at, $reason);
        });
    }

    /**
     * Missões recentes (vigentes primeiro) com o histórico de cada uma.
     *
     * @return list<array<string, mixed>>
     */
    public function missions(): array
    {
        $missions = $this->missions->recent();
        $logs = $this->missions->logs(array_column($missions, 'id'));
        $cells = [];

        return array_map(function (array $m) use ($logs, &$cells): array {
            $cell = null;
            if ($m['cell_no'] !== null) {
                $cells[$m['run_id']] ??= $this->urban->allCells((int) $m['run_id']);
                $cell = array_values(array_filter($cells[$m['run_id']], static fn(array $c): bool => $c['cell_no'] === $m['cell_no']))[0] ?? null;
            }

            return $m + ['log' => $logs[$m['id']] ?? [], 'cell_label' => $cell === null ? null : 'Quadrado ' . $cell['rank_no'] . ' · ' . self::placeLabel($this->places($cell))];
        }, $missions);
    }

    /**
     * Retrospecto de uma semana do teste com as previsões do modelo congelado. Sem $reveal, os registros observados
     * nem saem do servidor.
     *
     * @return array<string, mixed>|null
     */
    public function replay(?string $week, ?string $method, bool $reveal): ?array
    {
        // O retrospecto é o da execução importada do treino; as semanais automáticas não têm retrospecto próprio.
        $run = $this->urban->referenceRun();
        if ($run === null) {
            return null;
        }
        $method = self::method($method, $run);
        $other = $method === 'baseline' ? 'poisson' : 'baseline';
        $weeks = $this->urban->replayWeeks($run['id']);
        // Sem semana escolhida, abre na 45ª (ou na última, se o teste for mais curto).
        $week = in_array($week, $weeks, true) ? $week : ($weeks === [] ? null : $weeks[min(44, count($weeks) - 1)]);
        $rows = [];
        foreach ($week === null ? [] : $this->urban->replay($run['id'], $week) as $row) {
            $rows[(string) $row['sector_id']] = $row;
        }
        $names = array_map(static fn(array $s): string => (string) $s['name'], $this->urban->sectors($run['id']));
        $rank = SectorForecast::rank(array_map(static fn(array $r): float => $r[SectorForecast::COLUMN[$method]], $rows));
        $otherRank = SectorForecast::rank(array_map(static fn(array $r): float => $r[SectorForecast::COLUMN[$other]], $rows));
        $sectors = [];
        foreach ($rank as $id => $position) {
            $r = $rows[$id];
            $sectors[] = [
                'sector_id' => $id, 'name' => $names[$id] ?? $id, 'rank' => $position, 'other_rank' => $otherRank[$id],
                'value' => $r[SectorForecast::COLUMN[$method]], 'other_value' => $r[SectorForecast::COLUMN[$other]],
                'count_13w' => $r['count_13w'], 'count_52w' => $r['count_52w'], 'history_through' => $r['history_through'],
                'observed' => $reveal ? $r['observed_count'] : null,
            ];
        }
        $index = $week === null ? 0 : (int) array_search($week, $weeks, true);
        $first = array_search(1, $rank, true);
        $firstOther = array_search(1, $otherRank, true);
        $timeline = $week === null ? [] : $this->replayTimeline($run['id'], $method, $reveal ? $index : $index - 1);

        return [
            'run' => $run,
            'method' => $method,
            'other' => $other,
            'weeks' => $weeks,
            'week' => $week,
            'week_last_day' => $week === null ? null : self::addDays($week, 6),
            'previous' => $weeks[$index - 1] ?? null,
            'next' => $weeks[$index + 1] ?? null,
            'position' => $index + 1,
            'sectors' => $sectors,
            'reveal' => $reveal,
            'first' => $first === false ? null : $first,
            'first_other' => $firstOther === false ? null : $firstOther,
            'week_result' => !$reveal || $rows === [] ? null : [
                'observed' => array_sum(array_column($rows, 'observed_count')),
                'captured' => $rows[$first]['observed_count'],
                'captured_other' => $rows[$firstOther]['observed_count'],
                'max' => max(array_column($rows, 'observed_count')),
            ],
            'timeline' => $timeline,
            'score' => self::replayScore($timeline),
        ];
    }

    /**
     * Semana a semana até $last (índice, inclusive): o setor priorizado acertou quando teve o maior número de
     * registros da semana (empates contam). Semanas sem registro não entram na conta.
     *
     * @return list<array<string, mixed>>
     */
    private function replayTimeline(int $runId, string $method, int $last): array
    {
        $byWeek = [];
        foreach ($this->urban->replay($runId) as $row) {
            $byWeek[(string) $row['week_from']][(string) $row['sector_id']] = $row;
        }
        $timeline = [];
        $hits = 0;
        $counted = 0;
        $captured = 0;
        $observed = 0;
        foreach (array_slice($byWeek, 0, max(0, $last + 1), true) as $week => $rows) {
            $rank = SectorForecast::rank(array_map(static fn(array $r): float => $r[SectorForecast::COLUMN[$method]], $rows));
            $top = (int) $rows[array_search(1, $rank, true)]['observed_count'];
            $total = array_sum(array_column($rows, 'observed_count'));
            $hit = $total > 0 ? $top === max(array_column($rows, 'observed_count')) : null;
            if ($hit !== null) {
                $counted++;
                $hits += $hit ? 1 : 0;
            }
            $captured += $top;
            $observed += $total;
            $timeline[] = [
                'week' => (string) $week, 'hit' => $hit, 'captured' => $top, 'observed' => $total,
                'rate' => $counted > 0 ? $hits / $counted : null,
                'capture_rate' => $observed > 0 ? $captured / $observed : null,
            ];
        }

        return $timeline;
    }

    /**
     * @param list<array<string, mixed>> $timeline
     * @return array<string, mixed>
     */
    private static function replayScore(array $timeline): array
    {
        $counted = array_values(array_filter($timeline, static fn(array $t): bool => $t['hit'] !== null));
        $hits = count(array_filter($counted, static fn(array $t): bool => $t['hit'] === true));
        $last = $timeline === [] ? null : $timeline[count($timeline) - 1];

        return [
            'weeks' => count($timeline),
            'counted' => count($counted),
            'hits' => $hits,
            'rate' => $counted === [] ? null : $hits / count($counted),
            'capture_rate' => $last['capture_rate'] ?? null,
        ];
    }

    /**
     * Setores com a estimativa e a posição pelo método escolhido (a ordem importada fica em rank_no) e a explicação
     * em frases a partir das evidências — associação, nunca causa.
     *
     * @param array<string, array<string, mixed>> $sectors
     * @return array<string, array<string, mixed>> na ordem do método
     */
    private static function ordered(array $sectors, string $method): array
    {
        $other = $method === 'baseline' ? 'poisson' : 'baseline';
        $rank = SectorForecast::rank(array_map(static fn(array $s): float => $s[SectorForecast::COLUMN[$method]], $sectors));
        $otherRank = SectorForecast::rank(array_map(static fn(array $s): float => $s[SectorForecast::COLUMN[$other]], $sectors));
        $ordered = [];
        foreach ($rank as $id => $position) {
            $s = $sectors[$id];
            $r13 = $s['count_13w'] / 13;
            $r52 = $s['count_52w'] / 52;
            $trend = $r13 > $r52 * 1.1 ? 'acima da' : ($r13 < $r52 * 0.9 ? 'abaixo da' : 'perto da');
            $ordered[$id] = $s + [
                'value' => $s[SectorForecast::COLUMN[$method]],
                'rank' => $position,
                'other_rank' => $otherRank[$id],
                'explanation' => [
                    sprintf(
                        '%d registros nas 13 semanas até %s (média de %s por semana) e %d nas 52 semanas (média de %s): o ritmo recente está %s média do ano.',
                        $s['count_13w'],
                        self::brDate((string) $s['history_through']),
                        self::fmt($r13),
                        $s['count_52w'],
                        self::fmt($r52),
                        $trend,
                    ),
                    $method === 'baseline'
                        ? sprintf('A referência repete a média das 52 semanas, com uma pseudocontagem de suavização: (%d + 1) ÷ 53 = %s registros em 7 dias.', $s['count_52w'], self::fmt($s['expected_baseline']))
                        : sprintf('O Poisson experimental combina setor, ritmo de 13 e de 52 semanas, época do ano e tendência desde 2020 e chega a %s (a referência daria %s). O modelo não decompõe a estimativa por variável.', self::fmt($s['expected_ml']), self::fmt($s['expected_baseline'])),
                ],
            ];
        }

        return $ordered;
    }

    /**
     * Ponto de referência e distância de um agente ao setor. Posição antiga ou ausente não vira "localização atual":
     * usa a base e diz isso.
     *
     * @param array<string, mixed> $agent
     * @param array<string, mixed> $sector
     * @return array<string, mixed>
     */
    private static function candidate(array $agent, array $sector, DateTimeImmutable $now): array
    {
        $where = (string) ($sector['area_label'] ?? 'setor');
        $freshness = Freshness::of(Time::fromDb($agent['pos_observed_at']), $now, true, Freshness::POSITION_CURRENT, Freshness::POSITION_STALE);
        $usable = $agent['pos_lat'] !== null && in_array($freshness, [Freshness::Atual, Freshness::Atrasada], true);
        $tier = $usable ? ($freshness === Freshness::Atual ? 0 : 1) : 2;
        [$lat, $lng] = $usable ? [(float) $agent['pos_lat'], (float) $agent['pos_lng']] : [(float) $agent['base_lat'], (float) $agent['base_lng']];
        $km = SectorForecast::distanceToBoxKm($lat, $lng, ['south' => (float) $sector['south'], 'north' => (float) $sector['north'], 'west' => (float) $sector['west'], 'east' => (float) $sector['east']]);

        return [
            'id' => (int) $agent['id'], 'code' => $agent['code'], 'call_sign' => $agent['call_sign'], 'status' => $agent['status'],
            'tier' => $tier, 'point' => self::POINT[$tier], 'observed_at' => $agent['pos_observed_at'], 'lat' => $lat, 'lng' => $lng,
            'distance_km' => round($km, 3),
            'distance_label' => $km < 0.05 ? "dentro do {$where}" : self::fmt($km) . " km em linha reta até o {$where}",
        ];
    }

    /**
     * Bairros de referência do quadrado (cujo centro cai dentro dele, até três, o mais central primeiro); se nenhum,
     * o mais próximo. São nomes para se localizar, não o limite do quadrado.
     *
     * @param array<string, mixed> $cell
     * @return array{inside: list<string>, near: ?string}
     */
    private function places(array $cell): array
    {
        $lat = ($cell['south'] + $cell['north']) / 2;
        $lng = ($cell['west'] + $cell['east']) / 2;
        $regions = $this->regions ??= $this->references->regions();
        usort($regions, static fn(array $a, array $b): int => hypot((float) $a['center_lat'] - $lat, (float) $a['center_lng'] - $lng)
            <=> hypot((float) $b['center_lat'] - $lat, (float) $b['center_lng'] - $lng));
        $inside = array_filter($regions, static fn(array $r): bool => (float) $r['center_lat'] >= $cell['south'] && (float) $r['center_lat'] < $cell['north']
            && (float) $r['center_lng'] >= $cell['west'] && (float) $r['center_lng'] < $cell['east']);

        return ['inside' => array_slice(array_column($inside, 'name'), 0, 3), 'near' => $inside === [] ? ($regions[0]['name'] ?? null) : null];
    }

    /** @param array{inside: list<string>, near: ?string} $places */
    public static function placeLabel(array $places): string
    {
        return $places['inside'] !== [] ? implode(', ', $places['inside']) : 'perto de ' . ($places['near'] ?? 'área sem bairro de referência');
    }

    /** @param array<string, mixed> $run */
    private static function method(?string $method, array $run): string
    {
        return isset(SectorForecast::METHODS[$method ?? '']) ? (string) $method : (string) $run['selected_method'];
    }

    /**
     * FUTURA antes do início, VIGENTE durante, ENCERRADA a partir do fim exclusivo.
     *
     * @param array<string, mixed> $run
     */
    private static function period(array $run, string $today): string
    {
        return match (true) {
            $today < (string) $run['as_of'] => 'FUTURA',
            $today < (string) $run['until_date'] => 'VIGENTE',
            default => 'ENCERRADA',
        };
    }

    private static function fmt(float $value): string
    {
        return number_format($value, 1, ',', '.');
    }

    private static function brDate(string $ymd): string
    {
        return implode('/', array_reverse(explode('-', $ymd)));
    }

    private static function addDays(string $date, int $days): string
    {
        return (new DateTimeImmutable($date, Time::utc()))->modify(sprintf('%+d days', $days))->format('Y-m-d');
    }
}
