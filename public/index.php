<?php

declare(strict_types=1);

// Front controller: única porta HTTP da aplicação. Código, configuração e dados ficam fora de public/.

require dirname(__DIR__) . '/vendor/autoload.php';

$services = new Fiscaliza\Services(Fiscaliza\Config::load(dirname(__DIR__)), dirname(__DIR__));
Fiscaliza\Http\WebApp::create($services)->run();
