<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

use DateTimeImmutable;

/**
 * Relógio injetável. Produção usa o relógio do sistema; testes e geração de histórico usam um relógio fixo,
 * o que torna o cenário sintético reproduzível.
 */
class Clock
{
    private ?DateTimeImmutable $fixed;

    public function __construct(?DateTimeImmutable $fixed = null)
    {
        $this->fixed = $fixed?->setTimezone(Time::utc());
    }

    public function now(): DateTimeImmutable
    {
        return $this->fixed ?? new DateTimeImmutable('now', Time::utc());
    }

    public function set(DateTimeImmutable $instant): void
    {
        $this->fixed = $instant->setTimezone(Time::utc());
    }

    public function advance(string $interval): void
    {
        $this->fixed = $this->now()->modify($interval);
    }
}
