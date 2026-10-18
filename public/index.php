<?php

declare(strict_types=1);

// Front controller: única porta HTTP da aplicação. Código, configuração e dados ficam fora de public/.

require dirname(__DIR__) . '/vendor/autoload.php';

// A web nunca usa a identidade de dono do esquema: as credenciais privilegiadas não chegam aos objetos da aplicação.
$config = Fiscaliza\Config::load(dirname(__DIR__))->without(Fiscaliza\Config::PRIVILEGED);
$services = new Fiscaliza\Services($config, dirname(__DIR__));
Fiscaliza\Http\WebApp::create($services)->run();
