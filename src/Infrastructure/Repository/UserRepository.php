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
        return array_map(self::trim(...), $this->db->all('SELECT u.ID, u.USERNAME, u.DISPLAY_NAME, u.ROLE, u.ACTIVE, u.LAST_LOGIN_AT, u.CREATED_AT, u.PASSWORD_RESET_AT,
            r.DISPLAY_NAME AS PASSWORD_RESET_BY_NAME FROM APP_USER u LEFT JOIN APP_USER r ON r.ID = u.PASSWORD_RESET_BY ORDER BY u.USERNAME'));
    }

    /**
     * Contas que podem acompanhar um alerta: as ativas cadastradas em Contas (operador ou administrador, que operam o
     * sistema). Lidas a cada tela, então uma conta nova aparece sem mudar o código.
     *
     * @return list<array<string, mixed>>
     */
    public function assignable(): array
    {
        return array_map(self::trim(...), $this->db->all('SELECT ID, USERNAME, DISPLAY_NAME, ROLE FROM APP_USER WHERE ACTIVE = TRUE ORDER BY DISPLAY_NAME, USERNAME'));
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

    /** Senha nova da mesma conta: avança CREDENTIAL_NO (encerra as sessões abertas) e grava autoria e instante. */
    public function resetPassword(int $id, string $hash, int $by, string $at): void
    {
        $this->db->execute('UPDATE APP_USER SET PASSWORD_HASH = ?, CREDENTIAL_NO = CREDENTIAL_NO + 1, PASSWORD_RESET_AT = ?, PASSWORD_RESET_BY = ? WHERE ID = ?', [$hash, $at, $by, $id]);
    }

    public function touchLogin(int $id, string $at): void
    {
        $this->db->execute('UPDATE APP_USER SET LAST_LOGIN_AT = ? WHERE ID = ?', [$at, $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->execute('UPDATE APP_USER SET ACTIVE = ? WHERE ID = ?', [$active, $id]);
    }

    /** Trava (até o fim da transação) e conta as contas de administrador ativas. WITH LOCK não aceita agregação. */
    public function lockActiveAdmins(): int
    {
        return count($this->db->all("SELECT ID FROM APP_USER WHERE ROLE = 'ADMIN' AND ACTIVE = TRUE WITH LOCK"));
    }

    /**
     * Reserva a próxima vaga numerada da chave. Duas conexões que calculem a mesma vaga colidem no índice único
     * UX_LOGIN_ATTEMPT_SLOT: a segunda espera a primeira terminar e recebe ConstraintViolation (deve recontar).
     *
     * @return int id da reserva, que conta como falha até ser quitada
     */
    public function reserveAttempt(string $keyHash, string $at): int
    {
        return $this->db->insert(
            'INSERT INTO LOGIN_ATTEMPT (KEY_HASH, ATTEMPTED_AT, SLOT_NO) VALUES (?, ?, (SELECT COALESCE(MAX(SLOT_NO), 0) + 1 FROM LOGIN_ATTEMPT WHERE KEY_HASH = ?))',
            [$keyHash, $at, $keyHash],
        );
    }

    /** Quita uma reserva (login correto) acrescentando uma linha: o histórico de tentativas nunca é alterado. */
    public function settleAttempt(int $reservationId, string $at): void
    {
        $this->db->execute('INSERT INTO LOGIN_ATTEMPT (KEY_HASH, ATTEMPTED_AT, SETTLES_ID) SELECT KEY_HASH, ?, ID FROM LOGIN_ATTEMPT WHERE ID = ?', [$at, $reservationId]);
    }

    /** Falhas e reservas ainda não quitadas (verificações em andamento contam: é isso que torna a cota atômica). */
    public function failuresSince(string $keyHash, string $since): int
    {
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM LOGIN_ATTEMPT a WHERE a.KEY_HASH = ? AND a.ATTEMPTED_AT >= ? AND a.SETTLES_ID IS NULL
             AND NOT EXISTS (SELECT 1 FROM LOGIN_ATTEMPT s WHERE s.SETTLES_ID = a.ID)',
            [$keyHash, $since],
        );
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
