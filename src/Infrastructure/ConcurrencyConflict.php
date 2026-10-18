<?php

declare(strict_types=1);

namespace Fiscaliza\Infrastructure;

/** Timeout de lock, conflito de atualização concorrente ou versão otimista desatualizada. */
final class ConcurrencyConflict extends \RuntimeException {}
