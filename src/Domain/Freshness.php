<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

use DateTimeImmutable;

/**
 * Atualidade da última observação (sinal de equipamento ou posição de recurso móvel), derivada do tempo decorrido.
 * Silêncio do dispositivo não é falha confirmada: é informação ausente, exibida como tal.
 */
enum Freshness: string
{
    case Atual = 'ATUAL';
    case Atrasada = 'ATRASADA';
    case SemInformacao = 'SEM_INFORMACAO';
    case NaoSeAplica = 'NAO_SE_APLICA';

    /** Limites padrão para sinal de equipamento (segundos). */
    public const SIGNAL_CURRENT = 600;
    public const SIGNAL_STALE = 3600;
    /** Limites para posição de recursos móveis (segundos). */
    public const POSITION_CURRENT = 300;
    public const POSITION_STALE = 1800;

    public static function of(
        ?DateTimeImmutable $observedAt,
        DateTimeImmutable $now,
        bool $applicable = true,
        int $currentSeconds = self::SIGNAL_CURRENT,
        int $staleSeconds = self::SIGNAL_STALE,
    ): self {
        if (!$applicable) {
            return self::NaoSeAplica;
        }
        if ($observedAt === null) {
            return self::SemInformacao;
        }
        $age = $now->getTimestamp() - $observedAt->getTimestamp();

        return match (true) {
            $age <= $currentSeconds => self::Atual,
            $age <= $staleSeconds => self::Atrasada,
            default => self::SemInformacao,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Atual => 'Atual',
            self::Atrasada => 'Atrasada',
            self::SemInformacao => 'Sem informação recente',
            self::NaoSeAplica => 'Não se aplica',
        };
    }
}
