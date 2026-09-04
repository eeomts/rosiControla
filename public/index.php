<?php

/**
 * Front controller.
 *
 * Tudo que este arquivo fazia na mao -- timezone, charset, raiz de template,
 * display_errors, ErrorHandler, View padrao e conexao do banco -- e hoje a
 * secao [app] do config/config.ini, aplicada pelo Cubo\Bootstrapper. O que
 * sobra aqui e o que so a app sabe: qual middleware entra na pilha.
 */

declare(strict_types=1);

use Controla\Middleware\NaoEncontradoMiddleware;
use Cubo\Cubo;

$raiz = dirname(__DIR__);

require $raiz . '/vendor/autoload.php';

(new Cubo(appRoot: $raiz))
    ->middleware(NaoEncontradoMiddleware::class)
    ->run();
