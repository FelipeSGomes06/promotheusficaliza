<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

/** Banco inacessível (conexão recusada, credencial inválida). A mensagem pública nunca inclui o DSN. */
final class DatabaseUnavailable extends \RuntimeException {}
