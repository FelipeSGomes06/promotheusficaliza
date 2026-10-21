<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/** Dados de referência: órgãos e bairros (com a zona de agrupamento de demonstração). */
final class ReferenceRepository
{
    public function __construct(private readonly Database $db) {}

    /** @return list<array<string, mixed>> */
    public function agencies(): array
    {
        return array_map(self::trim(...), $this->db->all('SELECT ID, CODE, NAME, KIND FROM AGENCY ORDER BY NAME'));
    }

    /** @return list<array<string, mixed>> */
    public function regions(): array
    {
        return array_map(self::trim(...), $this->db->all('SELECT ID, NAME, ZONE, CENTER_LAT, CENTER_LNG FROM REGION ORDER BY NAME'));
    }

    /** @return array<string, mixed>|null */
    public function agency(int $id): ?array
    {
        $row = $this->db->one('SELECT ID, CODE, NAME, KIND FROM AGENCY WHERE ID = ?', [$id]);

        return $row === null ? null : self::trim($row);
    }

    public function agencyExists(int $id): bool
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM AGENCY WHERE ID = ?', [$id]) === 1;
    }

    public function regionExists(int $id): bool
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM REGION WHERE ID = ?', [$id]) === 1;
    }

    public function insertAgency(string $code, string $name, string $kind): int
    {
        return $this->db->insert('INSERT INTO AGENCY (CODE, NAME, KIND) VALUES (?, ?, ?)', [$code, $name, $kind]);
    }

    public function insertRegion(string $name, string $zone, string $lat, string $lng): int
    {
        return $this->db->insert('INSERT INTO REGION (NAME, ZONE, CENTER_LAT, CENTER_LNG) VALUES (?, ?, ?, ?)', [$name, $zone, $lat, $lng]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function trim(array $row): array
    {
        return array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
    }
}
