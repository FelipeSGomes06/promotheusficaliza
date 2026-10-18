<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Urgência de uma ordem de manutenção em aberto. Compara datas locais (Y-m-d de Marília) como texto ISO,
 * sem converter fuso: a data de vencimento é um dia do calendário local.
 */
enum MaintenanceUrgency: string
{
    case Imediata = 'IMEDIATA';
    case Vencida = 'VENCIDA';
    case Proxima = 'PROXIMA';
    case Futura = 'FUTURA';

    public const DEFAULT_WINDOW_DAYS = 7;

    public static function classify(string $priority, ?string $dueDate, string $todayLocal, int $windowDays = self::DEFAULT_WINDOW_DAYS): self
    {
        if ($priority === 'IMEDIATA') {
            return self::Imediata;
        }
        if ($dueDate === null || $dueDate === '') {
            // Programada sem data é barrada pelo banco (CK_MAINT_DUE); tratada como imediata por segurança.
            return self::Imediata;
        }
        $limit = (new \DateTimeImmutable($todayLocal))->modify("+{$windowDays} days")->format('Y-m-d');

        return match (true) {
            $dueDate < $todayLocal => self::Vencida,
            $dueDate <= $limit => self::Proxima,
            default => self::Futura,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Imediata => 'Imediata',
            self::Vencida => 'Vencida',
            self::Proxima => 'Próxima',
            self::Futura => 'Programada',
        };
    }

    public function isUrgent(): bool
    {
        return $this === self::Imediata || $this === self::Vencida;
    }

    /** Ordem de prioridade para escolher a ordem mais urgente de um equipamento (menor = mais urgente). */
    public function rank(): int
    {
        return match ($this) {
            self::Imediata => 0,
            self::Vencida => 1,
            self::Proxima => 2,
            self::Futura => 3,
        };
    }
}
