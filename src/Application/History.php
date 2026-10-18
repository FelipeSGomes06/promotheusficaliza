<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Repository\EventRepository;

/** Registro de alterações feitas por pessoas ou pelo sistema (autoria, instante, antes/depois). */
final class History
{
    public function __construct(private readonly EventRepository $events, private readonly Clock $clock) {}

    /** @param array<string, mixed> $payload */
    public function record(string $kind, int $resourceId, string $type, ?int $actor, string $summary, array $payload = [], string $source = 'MANUAL'): void
    {
        $now = Time::toDb($this->clock->now());
        $this->events->append([
            'uid' => strtolower($source) . '-' . bin2hex(random_bytes(12)),
            'kind' => $kind,
            'resource_id' => $resourceId,
            'type' => $type,
            'source' => $source,
            'actor' => $actor,
            'occurred_at' => $now,
            'received_at' => $now,
            'outcome' => 'APLICADO',
            'summary' => $summary,
            'payload' => $payload,
        ]);
    }

    /**
     * Diferença campo a campo entre duas versões (somente campos alterados).
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, array{antes: mixed, depois: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $key => $value) {
            $old = $before[$key] ?? null;
            $normalize = static fn($v) => is_float($v) ? number_format($v, 6, '.', '') : (is_array($v) ? json_encode($v) : (string) ($v ?? ''));
            if ($normalize($old) !== $normalize($value)) {
                $changes[$key] = ['antes' => $old, 'depois' => $value];
            }
        }

        return $changes;
    }
}
