<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

/**
 * Aplica database/migrations/NNNN_nome.sql em ordem, uma única vez, com a identidade de migração (dono do banco).
 * DDL roda em autocommit: no Firebird um objeto criado só pode receber DML depois do commit da sua criação.
 * Ao final, sincroniza os privilégios mínimos do usuário da aplicação (nunca SYSDBA, nunca DDL).
 */
final class Migrator
{
    /**
     * Tabelas em que o usuário da aplicação só lê e acrescenta: histórico não é alterado nem apagado, e uma execução
     * importada do planejamento por setores (URBAN_RUN e filhas) não muda depois de gravada.
     */
    private const APPEND_ONLY = ['RESOURCE_EVENT', 'LOGIN_ATTEMPT', 'URBAN_RUN', 'URBAN_SECTOR', 'URBAN_FORECAST', 'URBAN_REPLAY', 'URBAN_CELL', 'URBAN_SNAPSHOT', 'URBAN_DAILY', 'URBAN_MISSION_LOG'];

    public function __construct(private readonly Database $db, private readonly string $directory) {}

    /** @return list<string> migrações aplicadas nesta execução */
    public function migrate(): array
    {
        $this->ensureLedger();
        $applied = array_column($this->db->all('SELECT NAME FROM SCHEMA_MIGRATION'), 'name');
        $done = [];
        foreach ($this->pending($applied) as $name => $file) {
            foreach (self::statements((string) file_get_contents($file)) as $sql) {
                $this->db->pdo()->exec($sql);
            }
            $this->db->execute('INSERT INTO SCHEMA_MIGRATION (NAME, APPLIED_AT) VALUES (?, ?)', [$name, gmdate('Y-m-d H:i:s')]);
            $done[] = $name;
        }

        return $done;
    }

    /**
     * Concede ao usuário da aplicação apenas DML nas tabelas do esquema (somente leitura+inserção nas append-only).
     * O nome do usuário vem da configuração e é validado como identificador simples antes de entrar no SQL.
     *
     * @return list<string> tabelas tratadas
     */
    public function grantApplicationAccess(string $appUser): array
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]{0,62}$/', $appUser) !== 1) {
            throw new \InvalidArgumentException('Nome de usuário da aplicação inválido');
        }
        $user = strtoupper($appUser);
        $tables = array_map(trim(...), array_column($this->db->all(
            'SELECT RDB$RELATION_NAME AS NAME FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0 AND RDB$VIEW_BLR IS NULL ORDER BY 1',
        ), 'name'));
        foreach ($tables as $table) {
            if ($table === 'SCHEMA_MIGRATION') {
                continue;
            }
            $privileges = in_array($table, self::APPEND_ONLY, true) ? 'SELECT, INSERT' : 'SELECT, INSERT, UPDATE, DELETE';
            $this->db->pdo()->exec("GRANT {$privileges} ON {$table} TO USER {$user}");
        }

        return $tables;
    }

    /**
     * Remove todas as tabelas do esquema. Usado apenas pelos testes de integração no banco de testes dedicado,
     * para provar a criação a partir de banco vazio a cada execução.
     */
    public function dropEverything(): void
    {
        $tables = array_map(trim(...), array_column($this->db->all(
            'SELECT RDB$RELATION_NAME AS NAME FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0 AND RDB$VIEW_BLR IS NULL',
        ), 'name'));
        // Primeiro as chaves estrangeiras, depois as tabelas (ordem livre).
        foreach ($this->db->all("SELECT RDB\$RELATION_NAME AS T, RDB\$CONSTRAINT_NAME AS C FROM RDB\$RELATION_CONSTRAINTS WHERE RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'") as $fk) {
            $this->db->pdo()->exec('ALTER TABLE ' . trim((string) $fk['t']) . ' DROP CONSTRAINT ' . trim((string) $fk['c']));
        }
        foreach ($tables as $table) {
            $this->db->pdo()->exec("DROP TABLE {$table}");
        }
    }

    /**
     * Divide um arquivo de migração em instruções: remove comentários de linha e corta em ";" no fim da linha.
     *
     * @return list<string>
     */
    public static function statements(string $sql): array
    {
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        $parts = preg_split('/;\s*$/m', $sql) ?: [];

        return array_values(array_filter(array_map(trim(...), $parts), static fn(string $s): bool => $s !== ''));
    }

    /**
     * @param list<string> $applied
     * @return array<string, string> nome => caminho
     */
    private function pending(array $applied): array
    {
        $files = glob($this->directory . '/[0-9][0-9][0-9][0-9]_*.sql') ?: [];
        sort($files);
        $pending = [];
        foreach ($files as $file) {
            $name = basename($file, '.sql');
            if (!in_array($name, $applied, true)) {
                $pending[$name] = $file;
            }
        }

        return $pending;
    }

    private function ensureLedger(): void
    {
        $exists = (int) $this->db->value("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'SCHEMA_MIGRATION'");
        if ($exists === 0) {
            $this->db->pdo()->exec('CREATE TABLE SCHEMA_MIGRATION (NAME VARCHAR(80) NOT NULL, APPLIED_AT TIMESTAMP NOT NULL, CONSTRAINT PK_SCHEMA_MIGRATION PRIMARY KEY (NAME))');
        }
    }
}
