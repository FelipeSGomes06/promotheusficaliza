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

    public const LABELS = [self::NOT_CHECKED => 'Não conferidos', self::REGULAR => 'Em dia', self::PENDING => 'Com pendência'];

    public const SCOPES = ['OCORRENCIA' => 'No dia da multa', 'POSTERIOR' => 'Depois da multa'];

    /** Fonte da evidência apresentada. Nenhuma faz consulta externa: é o que a pessoa informou ter visto. */
    public const SOURCE_TYPES = [
        'CRLV_APRESENTADO' => 'O motorista mostrou o documento',
        'CONSULTA_OFICIAL_INFORMADA' => 'Consultado no site oficial (fora deste sistema)',
        'RELATO_MANUAL' => 'Contado por quem estava no local',
        'NAO_INFORMADA' => 'Não informado como se soube',
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
