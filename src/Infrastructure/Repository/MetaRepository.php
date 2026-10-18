<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/**
 * Metadados da instalação: marcador do conjunto sintético, último ciclo do simulador e contadores de ingestão.
 * O marcador "dataset" = "demo-sintetico" é a condição para comandos destrutivos de demonstração.
 */
final class MetaRepository
{
    public const DATASET_MARKER = 'demo-sintetico';

    public function __construct(private readonly Database $db) {}

    public function text(string $name): ?string
    {
        $value = $this->db->value('SELECT TEXT_VALUE FROM APP_META WHERE NAME = ?', [$name]);

        return is_string($value) ? rtrim($value) : null;
    }

    public function number(string $name): int
    {
        return (int) $this->db->value('SELECT NUM_VALUE FROM APP_META WHERE NAME = ?', [$name]);
    }

    public function set(string $name, ?string $text, ?int $number, string $at): void
    {
        $this->db->execute(
            'UPDATE OR INSERT INTO APP_META (NAME, TEXT_VALUE, NUM_VALUE, UPDATED_AT) VALUES (?, ?, ?, ?) MATCHING (NAME)',
            [$name, $text, $number, $at],
        );
    }

    /** Incremento atômico (o UPDATE bloqueia a linha até o commit). */
    public function increment(string $name, int $by, string $at): void
    {
        if ($this->db->execute('UPDATE APP_META SET NUM_VALUE = COALESCE(NUM_VALUE, 0) + ?, UPDATED_AT = ? WHERE NAME = ?', [$by, $at, $name]) === 0) {
            $this->set($name, null, $by, $at);
        }
    }

    /** @return array<string, array{text: ?string, number: ?int, updated_at: string}> */
    public function all(): array
    {
        $values = [];
        foreach ($this->db->all('SELECT NAME, TEXT_VALUE, NUM_VALUE, UPDATED_AT FROM APP_META') as $row) {
            $values[rtrim((string) $row['name'])] = [
                'text' => is_string($row['text_value']) ? rtrim($row['text_value']) : null,
                'number' => $row['num_value'] === null ? null : (int) $row['num_value'],
                'updated_at' => (string) $row['updated_at'],
            ];
        }

        return $values;
    }
}
