<?php

declare(strict_types=1);
// ini_set("display_errors", 1);

use Controla\Middleware\AutenticacaoMiddleware;
use Controla\Middleware\CsrfMiddleware;
use Controla\Middleware\NaoEncontradoMiddleware;
use Cubo\Cubo;

$raiz = dirname(__DIR__);

require $raiz . '/vendor/autoload.php';

(new Cubo(appRoot: $raiz))
    ->middleware(NaoEncontradoMiddleware::class)
    ->middleware(AutenticacaoMiddleware::class)
    ->middleware(CsrfMiddleware::class)
    ->run();
