<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Previsão semanal por setor experimental de Marília (ADR-0016, contrato marilia-setores-v1).
 *
 * O valor é a contagem esperada de sinistros registrados (elegíveis na área de estudo) em 7 dias — não é probabilidade
 * de acidente. A posição na ordem é prioridade relativa de planejamento: o 1º não é emergência nem incidente em curso.
 */
final class SectorForecast
{
    public const PROTOCOL = 'marilia-setores-v1';
    /** Ordem de config.sectors no treino (também a dos indicadores de setor do modelo). */
    public const SECTORS = ['NO', 'NE', 'SO', 'SE'];
    public const METHODS = ['baseline' => 'Referência histórica', 'poisson' => 'Poisson experimental'];
    /** Coluna da estimativa de cada método nas linhas de previsão e de retrospecto. */
    public const COLUMN = ['baseline' => 'expected_baseline', 'poisson' => 'expected_ml'];

    /**
     * Ordem decrescente pela estimativa; empate pelo código do setor em ordem lexical (contrato v1).
     *
     * @param array<string, float> $values setor => estimativa
     * @return array<string, int> setor => posição (1 = maior estimativa)
     */
    public static function rank(array $values): array
    {
        $ids = array_map(strval(...), array_keys($values));
        usort($ids, static fn(string $a, string $b): int => [$values[$b], $a] <=> [$values[$a], $b]);
        $ranks = [];
        foreach ($ids as $i => $id) {
            $ranks[$id] = $i + 1;
        }

        return $ranks;
    }

    /**
     * Teste com orçamento de um setor por semana: soma dos registros observados no setor que o método pôs em 1º, total
     * observado e erro absoluto médio por setor/semana. A fração é sobre registros elegíveis na área de estudo.
     *
     * @param list<array<string, mixed>> $rows linhas do retrospecto (week_from, sector_id, expected_*, observed_count)
     * O desvio é o desvio médio de Poisson do treino (sklearn mean_poisson_deviance, previsão limitada a 1e-12).
     *
     * @return array{captured: int, observed: int, weeks: int, rows: int, mae: float, predicted: float, deviance: float}
     */
    public static function replayMetrics(array $rows, string $method): array
    {
        $column = self::COLUMN[$method] ?? throw new \InvalidArgumentException("Método desconhecido: {$method}");
        $weeks = [];
        $error = 0.0;
        $predicted = 0.0;
        $deviance = 0.0;
        foreach ($rows as $row) {
            $weeks[(string) $row['week_from']][(string) $row['sector_id']] = $row;
            $mu = max((float) $row[$column], 1e-12);
            $y = (int) $row['observed_count'];
            $error += abs((float) $row[$column] - $y);
            $predicted += (float) $row[$column];
            $deviance += 2 * (($y > 0 ? $y * log($y / $mu) : 0.0) - $y + $mu);
        }
        $captured = 0;
        foreach ($weeks as $sectors) {
            $first = array_search(1, self::rank(array_map(static fn(array $r): float => (float) $r[$column], $sectors)), true);
            $captured += (int) $sectors[$first]['observed_count'];
        }

        return [
            'captured' => $captured,
            'observed' => array_sum(array_map(static fn(array $r): int => (int) $r['observed_count'], $rows)),
            'weeks' => count($weeks),
            'rows' => count($rows),
            'mae' => $rows === [] ? 0.0 : $error / count($rows),
            'predicted' => $predicted,
            'deviance' => $rows === [] ? 0.0 : $deviance / count($rows),
        ];
    }

    /**
     * Distância aproximada em linha reta (km) de um ponto até o retângulo do setor; 0 quando o ponto está dentro.
     * Projeção equirretangular local (erro desprezível nesta escala). Não é rota nem tempo de chegada.
     *
     * @param array{south: float, north: float, west: float, east: float} $box
     */
    public static function distanceToBoxKm(float $lat, float $lng, array $box): float
    {
        $dLat = max($box['south'] - $lat, 0.0, $lat - $box['north']);
        $dLng = max($box['west'] - $lng, 0.0, $lng - $box['east']) * cos(deg2rad($lat));

        return 6371.0 * deg2rad(hypot($dLat, $dLng));
    }

    /** Lado da grade de quadrados (8 × 8 ≈ 2 km): cada setor tem 4 × 4 quadrados. */
    public const GRID = 8;

    /**
     * Retângulo de um quadrado; linha 0 = sul, coluna 0 = oeste (mesma numeração de ml/marilia-grade-2km).
     *
     * @param array{south: float, north: float, west: float, east: float} $bounds retângulo de estudo
     * @return array{south: float, north: float, west: float, east: float}
     */
    public static function cellBox(array $bounds, int $cell): array
    {
        [$row, $col] = [intdiv($cell, self::GRID), $cell % self::GRID];
        $dLat = ($bounds['north'] - $bounds['south']) / self::GRID;
        $dLng = ($bounds['east'] - $bounds['west']) / self::GRID;

        return ['south' => $bounds['south'] + $row * $dLat, 'north' => $bounds['south'] + ($row + 1) * $dLat,
            'west' => $bounds['west'] + $col * $dLng, 'east' => $bounds['west'] + ($col + 1) * $dLng];
    }

    public static function cellSector(int $cell): string
    {
        return (intdiv($cell, self::GRID) >= self::GRID / 2 ? 'N' : 'S') . ($cell % self::GRID < self::GRID / 2 ? 'O' : 'E');
    }

    /**
     * Ordem dos quadrados: maior estimativa primeiro, empate pelo número do quadrado.
     *
     * @param array<int, float> $values quadrado => estimativa
     * @return array<int, int> quadrado => posição
     */
    public static function rankCells(array $values): array
    {
        $cells = array_keys($values);
        usort($cells, static fn(int $a, int $b): int => [$values[$b], $a] <=> [$values[$a], $b]);

        $ranks = [];
        foreach ($cells as $i => $cell) {
            $ranks[$cell] = $i + 1;
        }

        return $ranks;
    }

    /**
     * Quadrado de um ponto, com a regra de borda dos setores (linha divisória vai para norte/leste; bordas externas
     * incluídas); null fora do retângulo de estudo. Mesma conta de ml/marilia-grade-2km/experimento.py.
     *
     * @param array{south: float, north: float, west: float, east: float} $bounds
     */
    public static function cellOf(float $lat, float $lng, array $bounds): ?int
    {
        if ($lat < $bounds['south'] || $lat > $bounds['north'] || $lng < $bounds['west'] || $lng > $bounds['east']) {
            return null;
        }
        $row = min((int) (($lat - $bounds['south']) / (($bounds['north'] - $bounds['south']) / self::GRID) + 1e-9), self::GRID - 1);
        $col = min((int) (($lng - $bounds['west']) / (($bounds['east'] - $bounds['west']) / self::GRID) + 1e-9), self::GRID - 1);

        return $row * self::GRID + $col;
    }

    /** Corte de informação (exclusivo): 1º dia do mês, dois meses antes do mês da emissão (atraso de publicação assumido). */
    public static function cutoff(string $asOf): string
    {
        $date = new \DateTimeImmutable($asOf, Time::utc());

        return $date->modify('first day of this month')->modify('-2 months')->format('Y-m-d');
    }

    /**
     * Variáveis do Poisson na ordem do treino (treino.py, Counts.features): setor, ritmos de 13 e 52 semanas suavizados,
     * época do ano e anos desde 2020.
     *
     * @return list<float>
     */
    public static function poissonFeatures(string $sectorId, int $count13, int $count52, string $asOf): array
    {
        $date = new \DateTimeImmutable($asOf, Time::utc());
        $dayOfYear = (int) $date->format('z');
        $phase = 2 * M_PI * $dayOfYear / ($date->format('L') === '1' ? 366 : 365);
        $years = (int) (new \DateTimeImmutable('2020-01-01', Time::utc()))->diff($date)->format('%r%a') / 365.25;

        return [...array_map(static fn(string $s): float => $s === $sectorId ? 1.0 : 0.0, self::SECTORS),
            log1p(($count13 + 1) / 14), log1p(($count52 + 1) / 53), sin($phase), cos($phase), $years];
    }

    /**
     * Previsão do Poisson exportado em JSON: exp(intercepto + Σ coef × (x − média) ÷ escala).
     *
     * @param array{intercept: float, coefficients: list<float>, mean: list<float>, scale: list<float>} $model
     * @param list<float> $x
     */
    public static function poisson(array $model, array $x): float
    {
        $linear = (float) $model['intercept'];
        foreach ($model['coefficients'] as $i => $coefficient) {
            $linear += $coefficient * ($x[$i] - $model['mean'][$i]) / $model['scale'][$i];
        }

        return exp($linear);
    }
}
