<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Application\FineFilters;
use Fiscaliza\Application\Input;
use Fiscaliza\Domain\FineDocumentStatus;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Domain\ValidationFailed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Regras puras da aba Multas: identificadores, dinheiro, datas locais, completude, "A conferir" e seleção documental. */
final class FineRulesTest extends TestCase
{
    /** @return iterable<string, array{0: string, 1: bool}> */
    public static function plates(): iterable
    {
        yield 'antiga' => ['abc-1234', true];
        yield 'mercosul' => ['ABC 1D23', true];
        yield 'curta' => ['AB1234', false];
        yield 'letra no fim' => ['ABC123D', false];
        yield 'mercosul invertida' => ['ABC1234X', false];
    }

    #[DataProvider('plates')]
    public function testPlateFormatOnlyAfterRemovingPresentationPunctuation(string $raw, bool $valid): void
    {
        self::assertSame($valid, FineRules::isPlate(FineRules::plate($raw)));
    }

    public function testInternalCodeIsDerivedFromIdAndParsedBack(): void
    {
        self::assertSame('DEMO-MUL-000023', FineRules::code(23));
        self::assertSame(23, FineRules::idFromCode('demo-mul-000023'));
        self::assertSame(1234567, FineRules::idFromCode('DEMO-MUL-1234567'));
        self::assertNull(FineRules::idFromCode('DEMO-MUL-0'));
        self::assertNull(FineRules::idFromCode('DEMO-AIT-26-000101'));
        self::assertNull(FineRules::idFromCode('DEMO-MUL-99999999999'), 'fora da faixa de INTEGER');
    }

    public function testAitKeyKeepsPunctuationAndIgnoresCaseAndEdges(): void
    {
        self::assertSame('DEMO-AIT/26.01', FineRules::aitKey('  demo-ait/26.01 '));
        self::assertNull(FineRules::aitKey('   '));
    }

    /** @return iterable<string, array{0: string, 1: ?int}> */
    public static function money(): iterable
    {
        yield 'inteiro' => ['130', 13000];
        yield 'decimal' => ['293,47', 29347];
        yield 'milhar' => ['1.234,5', 123450];
        yield 'com R$' => ['R$ 88,38', 8838];
        yield 'três casas' => ['1,234', null];
        yield 'ponto decimal' => ['12.5', null];
        yield 'negativo' => ['-10,00', null];
        yield 'acima do teto' => ['1.000.000,01', null];
        yield 'texto' => ['cem reais', null];
        yield 'enorme' => ['99999999999999999999', null];
    }

    #[DataProvider('money')]
    public function testBrazilianMoneyBecomesCentsWithoutFloat(string $raw, ?int $cents): void
    {
        $input = new Input(['v' => $raw]);
        self::assertSame($cents, $input->cents('v', 1, 100_000_000));
        self::assertSame($cents === null, isset($input->errors()['v']));
    }

    public function testArrayWhereTextIsExpectedIsTreatedAsEmpty(): void
    {
        $input = new Input(['vehicle_plate' => ['ABC1234'], 'v' => ['1']]);
        self::assertNull($input->text('vehicle_plate', 10, false));
        self::assertNull($input->cents('v', 0, 10, true));
        self::assertSame('Campo obrigatório.', $input->errors()['v']);
    }

    public function testLocalDateTimeIsStrictAndConvertedToUtc(): void
    {
        $input = new Input(['a' => '2026-10-20T21:30', 'b' => '2026-02-30T10:00', 'c' => '2026-10-20 21:30', 'd' => '2026-10-20T21:30:15']);
        self::assertSame('2026-10-21 00:30:00', $input->localDateTime('a'), 'Marília é UTC−3: passa da meia-noite em UTC');
        self::assertNull($input->localDateTime('b'));
        self::assertNull($input->localDateTime('c'));
        self::assertSame('2026-10-21 00:30:15', $input->localDateTime('d'));
        self::assertSame(['b', 'c'], array_keys($input->errors()));
    }

    public function testFilterPeriodIsInclusiveLocalAndHalfOpenInUtc(): void
    {
        $f = FineFilters::fromQuery(['de' => '2026-10-20', 'ate' => '2026-10-20']);
        self::assertSame(['2026-10-20 03:00:00', '2026-10-21 03:00:00'], $f->utcRange());
        self::assertSame([], $f->errors);

        $bad = FineFilters::fromQuery(['de' => '2026-10-21', 'ate' => '2026-10-20', 'origem' => 'DROP TABLE', 'cadastro' => 'x', 'q' => str_repeat('a', 80)]);
        self::assertArrayHasKey('ate', $bad->errors, 'intervalo invertido é erro legível');
        self::assertNull($bad->source, 'valor fora da lista fechada é ignorado');
        self::assertSame('ativo', $bad->record);
        self::assertSame(60, mb_strlen($bad->q));
        self::assertArrayHasKey('de', FineFilters::fromQuery(['de' => '2026-02-30'])->errors);
    }

    /** @return array<string, mixed> */
    private static function complete(array $override = []): array
    {
        return $override + [
            'record_state' => 'RASCUNHO', 'occurred_at' => '2026-10-20 12:10:00', 'infraction_kind' => 'VELOCIDADE', 'legal_reference' => 'Ref. fictícia',
            'agency_id' => 1, 'external_ait' => 'DEMO-1', 'ait_missing_reason' => null, 'street' => 'Avenida Sampaio Vidal', 'address_ref' => null,
            'source_kind' => 'RADAR', 'equipment_id' => 3, 'agent_id' => 7, 'speed_limit_kmh' => 50, 'speed_measured_kmh' => 71, 'speed_considered_kmh' => 64,
            'vehicle_plate' => 'ABC1D23', 'vehicle_uf' => 'SP', 'vehicle_manufacturer' => 'Movix', 'vehicle_model' => 'Urbano', 'vehicle_species' => 'Passageiro',
            'vehicle_renavam' => null, 'renavam_missing_reason' => 'Não apresentado', 'occ_doc_status' => 'REGULARIDADE_INFORMADA',
            'radar_details' => ['inmetro_record' => 'I', 'certificate' => 'C', 'verified_on' => '2026-03-01'], 'evidence_reference' => 'E',
        ];
    }

    public function testRegistrationNeedsMinimumFieldsButJustifiedAbsencesCount(): void
    {
        self::assertSame([], FineRules::missingForRegistration(self::complete()), 'RENAVAM ausente com justificativa e radar com operador associado bastam');
        $missing = FineRules::missingForRegistration(self::complete(['external_ait' => null, 'street' => null, 'speed_measured_kmh' => null, 'renavam_missing_reason' => null]));
        self::assertSame(['external_ait', 'street', 'speed_measured_kmh', 'vehicle_renavam'], array_keys($missing));
        self::assertArrayHasKey('agent_id', FineRules::missingForRegistration(self::complete(['source_kind' => 'AGENTE', 'equipment_id' => null, 'agent_id' => null, 'infraction_kind' => 'OUTRA'])));
    }

    public function testSpeedCoherenceIsCheckedWithoutInventingTolerance(): void
    {
        self::assertSame([], FineRules::registrationConflicts(self::complete()));
        self::assertArrayHasKey('speed_measured_kmh', FineRules::registrationConflicts(self::complete(['speed_considered_kmh' => 50])), 'considerada igual ao limite');
        self::assertArrayHasKey('source_kind', FineRules::registrationConflicts(self::complete(['source_kind' => 'AGENTE'])), 'velocidade só por radar');
        self::assertSame([], FineRules::registrationConflicts(self::complete(['infraction_kind' => 'AVANCO_SEMAFORO', 'speed_considered_kmh' => null])));
    }

    /** @return iterable<string, array{0: int, 1: int}> */
    public static function speeds(): iterable
    {
        yield 'abaixo de 107: menos 7' => [71, 64];
        yield 'limite da faixa' => [107, 100];
        yield 'acima: menos 7%, arredondado' => [108, 100];
        yield '150 × 0,93 = 139,5 → 140' => [150, 140];
        yield '194 → 180 (tabela do Anexo III)' => [194, 180];
        yield 'nunca negativa' => [5, 0];
    }

    #[DataProvider('speeds')]
    public function testConsideredSpeedFollowsTheContranMargin(int $measured, int $considered): void
    {
        self::assertSame($considered, FineRules::consideredSpeed($measured));
    }

    public function testExpiredMeterVerificationOnTheOccurrenceDayNeedsAttention(): void
    {
        $old = FineRules::attention(self::complete(['record_state' => 'REGISTRADO', 'radar_details' => ['inmetro_record' => 'I', 'certificate' => 'C', 'verified_on' => '2025-10-19']]));
        self::assertCount(1, $old);
        self::assertStringContainsString('mais de 12 meses', $old[0]);
        self::assertSame([], FineRules::attention(self::complete(['record_state' => 'REGISTRADO', 'radar_details' => ['inmetro_record' => 'I', 'certificate' => 'C', 'verified_on' => '2025-10-21']])), 'menos de 12 meses');
    }

    public function testAttentionReasonsComeFromTheSameRuleAndCancelledHasNone(): void
    {
        self::assertSame([], FineRules::attention(self::complete(['record_state' => 'REGISTRADO']), '2026-10-21'));
        self::assertCount(1, FineRules::attention(self::complete()), 'rascunho completo ainda precisa ser registrado');
        $pending = FineRules::attention(self::complete(['record_state' => 'REGISTRADO', 'occ_doc_status' => 'NAO_VERIFICADA', 'radar_details' => [], 'occ_doc_review_on' => null]));
        self::assertCount(2, $pending, 'não verificada e dados metrológicos a conferir');
        self::assertSame([], FineRules::attention(self::complete(['record_state' => 'CANCELADO', 'occ_doc_status' => 'NAO_VERIFICADA'])));

        $review = self::complete(['record_state' => 'REGISTRADO', 'occ_doc_review_on' => '2026-10-10']);
        self::assertSame([], FineRules::attention($review), 'sem a data de hoje, a revisão vencida não conta');
        self::assertSame(['Documentos: precisam ser conferidos de novo (lembrete marcado para 10/10/2026).'], FineRules::attention($review, '2026-10-21'), 'lembrete vencido');
        self::assertSame([], FineRules::attention($review, '2026-10-10'), 'no próprio dia do lembrete ainda não conta');
        $later = FineRules::attention(self::complete(['record_state' => 'REGISTRADO', 'radar_details' => ['inmetro_record' => 'I', 'certificate' => 'C', 'verified_on' => '2026-10-25']]));
        self::assertStringContainsString('posterior à ocorrência', $later[0]);
    }

    public function testPendingDocumentsStopNeedingAttentionOnlyWhenRegularityIsInformedLater(): void
    {
        $pending = self::complete(['record_state' => 'REGISTRADO', 'occ_doc_status' => 'PENDENCIA_INFORMADA']);
        self::assertCount(1, FineRules::attention($pending), 'pendência sem conferência posterior');
        self::assertCount(1, FineRules::attention($pending + ['later_doc_status' => 'PENDENCIA_INFORMADA']), 'ainda com pendência depois');
        self::assertCount(1, FineRules::attention($pending + ['later_doc_status' => 'NAO_VERIFICADA']));
        self::assertSame([], FineRules::attention($pending + ['later_doc_status' => 'REGULARIDADE_INFORMADA']), 'regularizada depois: nada a acompanhar');
        self::assertCount(
            1,
            FineRules::attention(self::complete(['record_state' => 'REGISTRADO', 'occ_doc_status' => 'NAO_VERIFICADA', 'later_doc_status' => 'REGULARIDADE_INFORMADA'])),
            'regularidade posterior não diz nada sobre o dia da multa',
        );
    }

    public function testReviewReminderAnswerBecomesADateCountedFromToday(): void
    {
        self::assertSame('2026-11-17', FineDocumentStatus::reviewOn('30', null, '2026-10-18'));
        self::assertSame('2027-01-16', FineDocumentStatus::reviewOn('90', null, '2026-10-18'));
        self::assertSame('2026-11-01', FineDocumentStatus::reviewOn('DATA', '2026-11-01', '2026-10-18'));
        self::assertNull(FineDocumentStatus::reviewOn('NAO', null, '2026-10-18'), 'não precisa: sem lembrete');
        self::assertSame('2026-11-01', FineDocumentStatus::reviewOn(null, '2026-11-01', '2026-10-18'), 'sem resposta (sem JavaScript) vale a data digitada');
        self::assertNull(FineDocumentStatus::reviewOn(null, null, '2026-10-18'));
        foreach ([['DATA', null], ['NAO', '2026-11-01'], ['30', '2026-11-01']] as [$choice, $date]) {
            try {
                FineDocumentStatus::reviewOn($choice, $date, '2026-10-18');
                self::fail("{$choice} com data " . var_export($date, true));
            } catch (ValidationFailed $e) {
                self::assertSame(['review_on'], array_keys($e->errors));
            }
        }
    }

    public function testUnusualSequencesAreOnlyWarnings(): void
    {
        self::assertSame([], FineRules::progressWarnings(['process_stage' => 'PENALIDADE_NOTIFICADA', 'payment_status' => 'PAGAMENTO_INFORMADO', 'penalty_issued_on' => '2026-10-01',
            'paid_amount_cents' => 100, 'nominal_amount_cents' => 100]));
        self::assertCount(3, FineRules::progressWarnings(['process_stage' => 'NAO_INFORMADA', 'payment_status' => 'PAGAMENTO_INFORMADO', 'penalty_issued_on' => '2026-10-01',
            'paid_amount_cents' => 80, 'nominal_amount_cents' => 100]));
    }

    public function testDocumentSelectionUsesCheckInstantSubjectVersionAndScope(): void
    {
        $checks = [
            ['id' => 1, 'subject_version' => 1, 'scope' => 'OCORRENCIA', 'checked_at' => '2026-10-20 10:00:00', 'document_status' => 'PENDENCIA_INFORMADA'],
            ['id' => 2, 'subject_version' => 1, 'scope' => 'POSTERIOR', 'checked_at' => '2026-10-21 10:00:00', 'document_status' => 'REGULARIDADE_INFORMADA'],
            // Lançado depois (ID maior), mas conferido antes: não substitui a conferência mais recente.
            ['id' => 3, 'subject_version' => 1, 'scope' => 'OCORRENCIA', 'checked_at' => '2026-10-19 09:00:00', 'document_status' => 'REGULARIDADE_INFORMADA'],
            ['id' => 4, 'subject_version' => 2, 'scope' => 'POSTERIOR', 'checked_at' => '2026-10-22 09:00:00', 'document_status' => 'REGULARIDADE_INFORMADA'],
        ];
        self::assertSame(1, FineDocumentStatus::select($checks, 1, 'OCORRENCIA')['id'], 'regularidade posterior não corrige a ocorrência');
        self::assertSame(2, FineDocumentStatus::select($checks, 1, 'POSTERIOR')['id']);
        self::assertNull(FineDocumentStatus::select($checks, 2, 'OCORRENCIA'), 'identidade corrigida volta a "Não verificada"');
        self::assertSame(4, FineDocumentStatus::select($checks, 2, 'POSTERIOR')['id']);
    }
}
