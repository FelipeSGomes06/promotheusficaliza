<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Domain\AttentionModel;
use PHPUnit\Framework\TestCase;

final class AttentionModelTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function features(array $override = []): array
    {
        return $override + [
            'history_days' => 200.0, 'status_at' => 'OPERACIONAL', 'failures_30' => 0, 'failures_31_180' => 0, 'interval' => 90,
            'days_since_preventive' => 30.0, 'days_since_maintenance' => 30.0, 'overdue_preventive_since' => null,
        ];
    }

    public function testQuietEquipmentIsLowAttention(): void
    {
        $a = AttentionModel::assess(self::features(), 35);

        self::assertSame('BAIXA', $a['attention']);
        self::assertSame(0, $a['score']);
        self::assertSame([], $a['factors']);
    }

    public function testRecentFailuresAndOverduePreventiveRaiseAttentionWithExplanations(): void
    {
        $a = AttentionModel::assess(self::features(['failures_30' => 1, 'failures_31_180' => 2, 'days_since_preventive' => 140.0, 'overdue_preventive_since' => '2026-09-01']), 35);

        self::assertSame('ALTA', $a['attention']);
        self::assertSame(18 + 14 + 25, $a['score']);
        self::assertCount(3, $a['factors']);
        self::assertStringContainsString('1.6×', $a['factors'][2]['label']);
        self::assertSame(['Ordem preventiva vencida desde 2026-09-01 (sinal administrativo)'], $a['admin_flags']);
    }

    public function testShortHistoryIsInsufficientNotLowRisk(): void
    {
        $a = AttentionModel::assess(self::features(['history_days' => 40.0, 'failures_30' => 2]), 35);

        self::assertSame('INSUFICIENTE', $a['attention']);
        self::assertSame(36, $a['score'], 'a pontuação é mostrada, mas sem classificação');
    }

    public function testAlreadyFailingIsCorrectiveNotPrediction(): void
    {
        self::assertSame('CORRETIVA', AttentionModel::assess(self::features(['status_at' => 'FALHA']), 35)['attention']);
        $stolen = AttentionModel::assess(self::features(['status_at' => 'FURTADO']), 35);
        self::assertSame('CORRETIVA', $stolen['attention']);
        self::assertStringContainsString('repor', $stolen['recommendation']);
        $off = AttentionModel::assess(self::features(['status_at' => 'DESATIVADO']), 35);
        self::assertSame('NAO_AVALIADO', $off['attention'], 'fora de serviço não é "baixa atenção"');
        self::assertNull($off['score']);
    }

    /** OP-05: F1 sai das contagens; zero (erros sem acerto) é diferente de indefinido (sem falhas nem alertas). */
    public function testF1ComesFromCountsAndKeepsZeroDistinctFromUndefined(): void
    {
        $pair = static fn(bool $flag, bool $failed): array => ['flag' => $flag, 'failed' => $failed];

        $wrong = AttentionModel::metrics([$pair(true, false), $pair(false, true)]);
        self::assertSame([0.0, 0.0, 0.0], [$wrong['precision'], $wrong['recall'], $wrong['f1']]);

        $empty = AttentionModel::metrics([]);
        self::assertSame([null, null, null, null], [$empty['precision'], $empty['recall'], $empty['f1'], $empty['prevalence']]);

        $quiet = AttentionModel::metrics([$pair(false, false), $pair(false, false)]);
        self::assertSame([null, null, null, 0.0], [$quiet['precision'], $quiet['recall'], $quiet['f1'], $quiet['prevalence']], 'sem falhas e sem alertas: indefinido');

        $falseAlarms = AttentionModel::metrics([$pair(true, false)]);
        self::assertSame([0.0, null, 0.0], [$falseAlarms['precision'], $falseAlarms['recall'], $falseAlarms['f1']]);

        $misses = AttentionModel::metrics([$pair(false, true)]);
        self::assertSame([null, 0.0, 0.0], [$misses['precision'], $misses['recall'], $misses['f1']]);

        // TP=2, FP=1, FN=4: F1 = 4/9 exatamente (precisão e revocação não são arredondadas antes).
        $mixed = AttentionModel::metrics([$pair(true, true), $pair(true, true), $pair(true, false), ...array_fill(0, 4, $pair(false, true))]);
        self::assertSame(4 / 9, $mixed['f1']);
        self::assertSame(2 / 3, $mixed['precision']);
    }

    public function testMetricsHandleEmptyAndCountConfusionMatrix(): void
    {
        $m = AttentionModel::metrics([
            ['flag' => true, 'failed' => true], ['flag' => true, 'failed' => false], ['flag' => false, 'failed' => true], ['flag' => false, 'failed' => false],
        ]);

        self::assertSame([1, 1, 1], [$m['tp'], $m['fp'], $m['fn']]);
        self::assertSame(0.5, $m['precision']);
        self::assertSame(0.5, $m['recall']);
        self::assertSame(0.5, $m['prevalence']);
        self::assertNull(AttentionModel::metrics([])['precision']);
    }
}
