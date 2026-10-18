<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Índice heurístico de atenção preventiva para os próximos 7 dias (método "atencao-v1").
 *
 * NÃO é probabilidade calibrada de falha: é uma pontuação 0–100 que soma evidências do histórico.
 *
 *   falhas nos últimos 30 dias ............ 18 pontos cada (máx. 36) — recorrência recente
 *   falhas entre 31 e 180 dias ............ 7 pontos cada (máx. 35)  — propensão do próprio aparelho
 *   tempo desde a última preventiva / intervalo previsto:
 *       ≥ 0,8 → 6   ≥ 1,0 → 15   ≥ 1,5 → 25
 *   sem periodicidade definida e > 120 dias sem manutenção concluída → 10
 *
 * Classes: ALTA (≥ limiar ajustado), MODERADA (≥ metade do limiar), BAIXA (abaixo);
 * INSUFICIENTE com menos de 60 dias de histórico; CORRETIVA quando já está em falha/manutenção (fora da previsão).
 * Ordem preventiva vencida aparece como sinal administrativo, separado da tendência de falha.
 */
final class AttentionModel
{
    public const METHOD = 'atencao-v1';
    public const HORIZON_DAYS = 7;
    public const MIN_HISTORY_DAYS = 60;
    public const LONG_WINDOW_DAYS = 180;
    /** Limiares candidatos para ALTA, escolhidos somente na janela de ajuste. */
    public const THRESHOLD_GRID = [25, 35, 45, 55];
    public const WEIGHTS = ['falha_30d' => 18, 'falha_31_180d' => 7, 'preventiva_0_8x' => 6, 'preventiva_1x' => 15, 'preventiva_1_5x' => 25, 'sem_periodicidade_120d' => 10];

    /**
     * @param array{history_days: float, status_at: string, failures_30: int, failures_31_180: int, interval: ?int,
     *     days_since_preventive: ?float, days_since_maintenance: ?float, overdue_preventive_since: ?string} $f
     * @return array{attention: string, score: ?int, factors: list<array{label: string, points: int}>, admin_flags: list<string>, recommendation: string}
     */
    public static function assess(array $f, int $highThreshold): array
    {
        $w = self::WEIGHTS;
        $adminFlags = $f['overdue_preventive_since'] !== null ? ["Ordem preventiva vencida desde {$f['overdue_preventive_since']} (sinal administrativo)"] : [];
        $status = EquipmentStatus::from($f['status_at']);
        if (!$status->isInService()) {
            return ['attention' => 'BAIXA', 'score' => null, 'factors' => [['label' => "Fora de serviço ({$status->label()}): não avaliado", 'points' => 0]],
                'admin_flags' => [], 'recommendation' => 'Sem previsão: equipamento fora de serviço.'];
        }
        if ($status !== EquipmentStatus::Operacional) {
            return ['attention' => 'CORRETIVA', 'score' => null, 'factors' => [['label' => "Situação atual: {$status->label()}", 'points' => 0]],
                'admin_flags' => $adminFlags, 'recommendation' => 'Já em falha ou manutenção: tratar como corretiva (não é antecipação).'];
        }

        $factors = [];
        if ($f['failures_30'] > 0) {
            $factors[] = ['label' => "{$f['failures_30']} falha(s) nos últimos 30 dias", 'points' => min(2 * $w['falha_30d'], $w['falha_30d'] * $f['failures_30'])];
        }
        if ($f['failures_31_180'] > 0) {
            $factors[] = ['label' => "{$f['failures_31_180']} falha(s) entre 31 e 180 dias atrás", 'points' => min(5 * $w['falha_31_180d'], $w['falha_31_180d'] * $f['failures_31_180'])];
        }
        if ($f['interval'] !== null && $f['days_since_preventive'] !== null) {
            $ratio = $f['days_since_preventive'] / $f['interval'];
            $points = match (true) {
                $ratio >= 1.5 => $w['preventiva_1_5x'],
                $ratio >= 1.0 => $w['preventiva_1x'],
                $ratio >= 0.8 => $w['preventiva_0_8x'],
                default => 0,
            };
            if ($points > 0) {
                $factors[] = ['label' => sprintf('Última preventiva há %d dias (%.1f× o intervalo de %d)', (int) $f['days_since_preventive'], $ratio, $f['interval']), 'points' => $points];
            }
        } elseif ($f['interval'] === null && ($f['days_since_maintenance'] ?? 0) > 120) {
            $factors[] = ['label' => sprintf('Sem periodicidade definida e %d dias sem manutenção concluída', (int) $f['days_since_maintenance']), 'points' => $w['sem_periodicidade_120d']];
        }
        $score = min(100, array_sum(array_column($factors, 'points')));

        if ($f['history_days'] < self::MIN_HISTORY_DAYS) {
            return ['attention' => 'INSUFICIENTE', 'score' => $score, 'factors' => $factors, 'admin_flags' => $adminFlags,
                'recommendation' => sprintf('Histórico de %d dias (mínimo %d): acompanhar sem classificar.', (int) $f['history_days'], self::MIN_HISTORY_DAYS)];
        }
        $attention = match (true) {
            $score >= $highThreshold => 'ALTA',
            $score >= intdiv($highThreshold, 2) => 'MODERADA',
            default => 'BAIXA',
        };
        $recommendation = match ($attention) {
            'ALTA' => 'Agendar inspeção preventiva nos próximos 7 dias.',
            'MODERADA' => 'Incluir na próxima rota de inspeção e acompanhar os sinais.',
            default => 'Manter o plano atual.',
        };

        return ['attention' => $attention, 'score' => $score, 'factors' => $factors, 'admin_flags' => $adminFlags, 'recommendation' => $recommendation];
    }

    /**
     * Métricas de uma regra binária sobre pares (equipamento, data de corte).
     *
     * @param list<array{flag: bool, failed: bool}> $pairs
     * @return array{pairs: int, positives: int, flagged: int, tp: int, fp: int, fn: int, precision: ?float, recall: ?float, f1: ?float, prevalence: ?float}
     */
    public static function metrics(array $pairs): array
    {
        $tp = $fp = $fn = $positives = 0;
        foreach ($pairs as $p) {
            $positives += (int) $p['failed'];
            $tp += (int) ($p['flag'] && $p['failed']);
            $fp += (int) ($p['flag'] && !$p['failed']);
            $fn += (int) (!$p['flag'] && $p['failed']);
        }
        $precision = $tp + $fp === 0 ? null : round($tp / ($tp + $fp), 3);
        $recall = $tp + $fn === 0 ? null : round($tp / ($tp + $fn), 3);
        $f1 = ($precision === null || $recall === null || $precision + $recall == 0.0) ? null : round(2 * $precision * $recall / ($precision + $recall), 3);

        return ['pairs' => count($pairs), 'positives' => $positives, 'flagged' => $tp + $fp, 'tp' => $tp, 'fp' => $fp, 'fn' => $fn,
            'precision' => $precision, 'recall' => $recall, 'f1' => $f1, 'prevalence' => $pairs === [] ? null : round($positives / count($pairs), 3)];
    }
}
