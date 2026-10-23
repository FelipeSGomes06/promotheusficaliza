<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

/** Um provedor externo (CEP ou localização) não respondeu a tempo, falhou ou respondeu fora do formato. Mensagem só para o log. */
final class ProviderUnavailable extends \RuntimeException {}
