<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure\Repository;

use Fiscaliza\Infrastructure\Database;

/** Contas de acesso ao sistema (distintas dos agentes operacionais) e registro de falhas de login. */
final class UserRepository
{
    public function __construct(private readonly Database $db) {}

    /** @return array<string, mixed>|null */
    public function findByUsername(string $username): ?array
    {
        return self::trim($this->db->one('SELECT * FROM APP_USER WHERE UPPER(USERNAME) = UPPER(?)', [$username]));
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return self::trim($this->db->one('SELECT * FROM APP_USER WHERE ID = ?', [$id]));
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return array_map(self::trim(...), $this->db->all('SELECT ID, USERNAME, DISPLAY_NAME, ROLE, ACTIVE, LAST_LOGIN_AT, CREATED_AT FROM APP_USER ORDER BY USERNAME'));
    }

    public function insert(string $username, string $displayName, string $hash, string $role, string $createdAt): int
    {
        return $this->db->insert(
            'INSERT INTO APP_USER (USERNAME, DISPLAY_NAME, PASSWORD_HASH, ROLE, ACTIVE, CREATED_AT) VALUES (?, ?, ?, ?, TRUE, ?)',
            [$username, $displayName, $hash, $role, $createdAt],
        );
    }

    public function updateHash(int $id, string $hash): void
    {
        $this->db->execute('UPDATE APP_USER SET PASSWORD_HASH = ? WHERE ID = ?', [$hash, $id]);
    }

    public function touchLogin(int $id, string $at): void
    {
        $this->db->execute('UPDATE APP_USER SET LAST_LOGIN_AT = ? WHERE ID = ?', [$at, $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->execute('UPDATE APP_USER SET ACTIVE = ? WHERE ID = ?', [$active, $id]);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM APP_USER WHERE ROLE = 'ADMIN' AND ACTIVE = TRUE");
    }

    public function recordFailure(string $keyHash, string $at): void
    {
        $this->db->execute('INSERT INTO LOGIN_ATTEMPT (KEY_HASH, ATTEMPTED_AT) VALUES (?, ?)', [$keyHash, $at]);
    }

    public function failuresSince(string $keyHash, string $since): int
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM LOGIN_ATTEMPT WHERE KEY_HASH = ? AND ATTEMPTED_AT >= ?', [$keyHash, $since]);
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private static function trim(?array $row): ?array
    {
        return $row === null ? null : array_map(static fn($v) => is_string($v) ? rtrim($v) : $v, $row);
    }
}
