<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Situação documental informada manualmente. Não verificada é um estado legítimo (nunca equivale a regular), e uma
 * regularidade posterior não corrige a condição informada para a data da ocorrência.
 */
final class FineDocumentStatus
{
    public const NOT_CHECKED = 'NAO_VERIFICADA';
    public const REGULAR = 'REGULARIDADE_INFORMADA';
    public const PENDING = 'PENDENCIA_INFORMADA';

    public const LABELS = [self::NOT_CHECKED => 'Não verificada', self::REGULAR => 'Regularidade informada', self::PENDING => 'Pendência informada'];

    public const SCOPES = ['OCORRENCIA' => 'Na data da ocorrência', 'POSTERIOR' => 'Conferência posterior'];

    /** Fonte da evidência apresentada. Nenhuma faz consulta externa: é o que a pessoa informou ter visto. */
    public const SOURCE_TYPES = [
        'CRLV_APRESENTADO' => 'CRLV apresentado',
        'CONSULTA_OFICIAL_INFORMADA' => 'Consulta oficial feita fora do sistema',
        'RELATO_MANUAL' => 'Relato manual',
        'NAO_INFORMADA' => 'Fonte não informada',
    ];

    /**
     * Constatação que vale para o escopo: a mais recente pelo instante da conferência (CHECKED_AT, depois ID), entre as
     * da versão atual do sujeito. Um lançamento atrasado não substitui uma conferência mais recente só por ter chegado
     * depois; conferências de uma identidade anterior (antes da retificação) não valem para a nova.
     *
     * @param list<array<string, mixed>> $checks
     * @return array<string, mixed>|null
     */
    public static function select(array $checks, int $subjectVersion, string $scope): ?array
    {
        $best = null;
        foreach ($checks as $check) {
            if ((int) $check['subject_version'] !== $subjectVersion || $check['scope'] !== $scope) {
                continue;
            }
            if ($best === null || [(string) $check['checked_at'], (int) $check['id']] > [(string) $best['checked_at'], (int) $best['id']]) {
                $best = $check;
            }
        }

        return $best;
    }
}
