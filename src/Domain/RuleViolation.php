<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/** Operação válida em formato, mas proibida pela regra de negócio (ex.: transição de situação não permitida). */
final class RuleViolation extends \RuntimeException {}
