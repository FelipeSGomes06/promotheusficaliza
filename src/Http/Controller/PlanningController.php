<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\NotFound;
use Fiscaliza\Application\PlanningService;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\SectorForecast;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Planejar reforço (previsão semanal por setor, sugestão de agente, missão fictícia) e o retrospecto do teste. */
final class PlanningController extends Controller
{
    /**
     * Planejar reforço em três passos: onde (quadrados de 2 km), quem (agente de trânsito livre mais adequado ao
     * quadrado escolhido) e confirmar (missão fictícia). Sem quadrados importados, o alvo é o setor de maior estimativa.
     */
    public function page(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $planning = $this->services->planning();
        // Só a média simples na tela (o Poisson experimental fica nos dados e no treino, não na interface).
        $week = $planning->week('baseline');
        $missions = $planning->missions();
        $selected = null;
        $cell = null;
        $assigned = [];
        $candidates = ['eligible' => [], 'unavailable' => []];
        $map = null;
        $cellCount = self::intParam($query, 'quadrados', PlanningService::MAX_CELLS) ?? PlanningService::MAX_CELLS;
        if ($week !== null) {
            $week['cells'] = array_slice($week['cells'], 0, $cellCount);
            $cell = $week['cells'][(self::intParam($query, 'quadrado', count($week['cells'])) ?? 1) - 1] ?? null;
            $selected = $cell !== null ? $week['sectors'][$cell['sector_id']] : array_values($week['sectors'])[0];
            $candidates = $planning->candidates($cell !== null ? $cell + ['area_label' => 'quadrado'] : $selected);
            foreach ($missions as $m) {
                if ($m['state'] === 'CONFIRMADA' && (int) $m['run_id'] === $week['run']['id'] && $m['cell_no'] !== null) {
                    $assigned[$m['cell_no']][] = $m['agent_call_sign'];
                }
            }
            $base = $this->services->config->basePath() . '/planejar?';
            $map = [
                'tiles' => $this->services->config->get('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
                'attribution' => $this->services->config->get('MAP_TILE_ATTRIBUTION', '&copy; colaboradores do OpenStreetMap'),
                // Grade inteira (simétrica, 8 × 8) e só os quadrados prioritários, numerados e clicáveis.
                'grid' => [
                    'south' => min(array_column($week['sectors'], 'south')), 'north' => max(array_column($week['sectors'], 'north')),
                    'west' => min(array_column($week['sectors'], 'west')), 'east' => max(array_column($week['sectors'], 'east')),
                    'size' => SectorForecast::GRID,
                    'cells' => array_map(static fn(array $c): array => ['rank' => $c['rank_no'], 'south' => $c['south'], 'north' => $c['north'],
                        'west' => $c['west'], 'east' => $c['east'], 'selected' => $c['cell_no'] === ($cell['cell_no'] ?? null),
                        'href' => $base . http_build_query(['quadrado' => $c['rank_no'], 'quadrados' => $cellCount]) . '#agente'], $week['cells']),
                ],
            ];
        }

        return $this->render($request, $response, 'planning/index.twig', [
            'week' => $week, 'selected' => $selected, 'cell' => $cell, 'assigned' => $assigned, 'candidates' => $candidates, 'map' => $map,
            'cell_count' => $cellCount, 'missions' => $missions, 'states' => PlanningService::STATES,
        ]);
    }

    public function replay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $replay = $this->services->planning()->replay(self::param($query, 'semana'), 'baseline', self::param($query, 'revelar') === '1');

        return $this->render($request, $response, 'planning/replay.twig', ['replay' => $replay, 'methods' => SectorForecast::METHODS]);
    }

    public function confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $back = '/planejar?' . http_build_query(['quadrado' => self::scalar($body, 'cell_rank'), 'quadrados' => self::scalar($body, 'cell_count')]) . '#agente';
        try {
            $id = $this->services->planning()->confirm($body, $this->userId($request));
            $this->session($request)->flash('success', "Missão fictícia #{$id} confirmada. Nenhuma mensagem foi enviada: é uma demonstração.");
            $back = '/planejar#missoes';
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict|NotFound $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, $back);
    }

    /** @param array<string, string> $args */
    public function complete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->close($request, $response, self::id($args), 'CONCLUIDA', 'concluída');
    }

    /** @param array<string, string> $args */
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->close($request, $response, self::id($args), 'CANCELADA', 'cancelada');
    }

    private function close(ServerRequestInterface $request, ResponseInterface $response, int $id, string $state, string $done): ResponseInterface
    {
        try {
            $this->services->planning()->close($id, $state, (string) self::scalar($this->body($request), 'reason'), $this->userId($request));
            $this->session($request)->flash('success', "Missão fictícia #{$id} {$done}; o agente volta a ficar disponível para sugestões.");
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/planejar#missoes');
    }

    /** @param array<string, mixed> $query */
    private static function param(array $query, string $key): ?string
    {
        return is_string($query[$key] ?? null) ? $query[$key] : null;
    }

    /** @param array<string, mixed> $query */
    private static function intParam(array $query, string $key, int $max): ?int
    {
        $value = filter_var(self::param($query, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => max(1, $max)]]);

        return $value === false ? null : $value;
    }

    /** @param array<string, mixed> $body */
    private static function scalar(array $body, string $key): ?string
    {
        return is_scalar($body[$key] ?? null) ? (string) $body[$key] : null;
    }
}
