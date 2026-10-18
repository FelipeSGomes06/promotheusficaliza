<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Convenção de tempo: instantes são gravados em UTC (TIMESTAMP sem fuso) e exibidos em America/Sao_Paulo;
 * datas de manutenção (DATE) já são datas locais de Marília e nunca passam por conversão de fuso.
 */
final class Time
{
    public const LOCAL_TIMEZONE = 'America/Sao_Paulo';

    public static function utc(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }

    public static function local(): DateTimeZone
    {
        return new DateTimeZone(self::LOCAL_TIMEZONE);
    }

    public static function toDb(DateTimeImmutable $instant): string
    {
        return $instant->setTimezone(self::utc())->format('Y-m-d H:i:s');
    }

    public static function fromDb(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', substr($value, 0, 19), self::utc());

        return $parsed === false ? null : $parsed;
    }

    /** Data local (Y-m-d) em Marília para um instante. */
    public static function localDate(DateTimeImmutable $instant): string
    {
        return $instant->setTimezone(self::local())->format('Y-m-d');
    }

    /** ISO 8601 com deslocamento, para JSON. */
    public static function iso(?DateTimeImmutable $instant): ?string
    {
        return $instant?->setTimezone(self::local())->format(DATE_ATOM);
    }
}
