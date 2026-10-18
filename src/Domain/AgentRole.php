<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

enum AgentRole: string
{
    case Transito = 'TRANSITO';
    case Guarda = 'GUARDA';
    case Policia = 'POLICIA';

    public function label(): string
    {
        return match ($this) {
            self::Transito => 'Agente de trânsito',
            self::Guarda => 'Guarda municipal',
            self::Policia => 'Policial',
        };
    }

    /** Código de categoria usado nos filtros da central, ao lado dos tipos de equipamento. */
    public function category(): string
    {
        return 'AG_' . $this->value;
    }

    public static function fromCategory(string $category): ?self
    {
        return str_starts_with($category, 'AG_') ? self::tryFrom(substr($category, 3)) : null;
    }
}
