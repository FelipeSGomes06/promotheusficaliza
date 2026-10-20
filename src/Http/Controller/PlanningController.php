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
    public function page(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $planning = $this->services->planning();
        // Só a média simples na tela (o Poisson experimental fica nos dados e no treino, não na interface).
        $week = $planning->week('baseline');
        $selected = null;
        $candidates = ['eligible' => [], 'unavailable' => []];
        $map = null;
        $cellCount = filter_var(self::param($query, 'quadrados'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => PlanningService::MAX_CELLS]]) ?: PlanningService::MAX_CELLS;
        if ($week !== null) {
            $week['cells'] = array_slice($week['cells'], 0, $cellCount);
            // Sem setor pedido, abre o 1º setor.
            $selected = $week['sectors'][strtoupper((string) self::param($query, 'setor'))] ?? array_values($week['sectors'])[0];
            $candidates = $planning->candidates($selected);
            $map = [
                'tiles' => $this->services->config->get('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
                'attribution' => $this->services->config->get('MAP_TILE_ATTRIBUTION', '&copy; colaboradores do OpenStreetMap'),
                // Mapa próprio dos quadrados: a grade inteira (simétrica, 8 × 8) e só os prioritários numerados.
                'grid' => [
                    'south' => min(array_column($week['sectors'], 'south')), 'north' => max(array_column($week['sectors'], 'north')),
                    'west' => min(array_column($week['sectors'], 'west')), 'east' => max(array_column($week['sectors'], 'east')),
                    'size' => SectorForecast::GRID,
                    'cells' => array_map(static fn(array $c): array => ['rank' => $c['rank_no'], 'south' => $c['south'], 'north' => $c['north'],
                        'west' => $c['west'], 'east' => $c['east']], $week['cells']),
                ],
            ];
        }

        return $this->render($request, $response, 'planning/index.twig', [
            'week' => $week, 'selected' => $selected, 'candidates' => $candidates, 'map' => $map, 'cell_count' => $cellCount,
            'missions' => $planning->missions(), 'methods' => SectorForecast::METHODS, 'states' => PlanningService::STATES,
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
        $back = '/planejar?' . http_build_query(['setor' => self::scalar($body, 'sector_id'), 'metodo' => self::scalar($body, 'method')]) . '#setor';
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

    /** @param array<string, mixed> $body */
    private static function scalar(array $body, string $key): ?string
    {
        return is_scalar($body[$key] ?? null) ? (string) $body[$key] : null;
    }
}
