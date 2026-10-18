<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/** Corrida entre duas entregas do mesmo evento: a transação perdedora é desfeita e o evento conta como duplicata. */
final class DuplicateEvent extends \RuntimeException {}
