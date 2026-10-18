<?php

declare(strict_types=1);

// Somente CI: espera o Firebird de serviço e cria o usuário da aplicação (credenciais descartáveis do pipeline).
// Localmente isso é feito por docker/firebird/10-fiscaliza-init.sh.

$dsn = getenv('DB_TEST_DSN') ?: exit("DB_TEST_DSN ausente\n");
$user = getenv('DB_USER') ?: exit("DB_USER ausente\n");
$password = getenv('DB_PASSWORD') ?: exit("DB_PASSWORD ausente\n");
if (preg_match('/^[A-Za-z][A-Za-z0-9_]{0,30}$/', $user) !== 1) {
    exit("DB_USER inválido\n");
}

for ($attempt = 1; ; $attempt++) {
    try {
        $sysdba = new PDO($dsn, 'SYSDBA', (string) getenv('FIREBIRD_ROOT_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (PDOException $e) {
        if ($attempt >= 30) {
            exit("Firebird indisponível após 60 s\n");
        }
        sleep(2);
    }
}
$sysdba->exec(sprintf("CREATE OR ALTER USER %s PASSWORD '%s'", $user, str_replace("'", "''", $password)));
echo "Usuário da aplicação pronto no Firebird de CI.\n";
