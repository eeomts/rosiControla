<?php

declare(strict_types=1);

use Controla\Controllers\CoreController;
use Controla\Views\DefaultView;
use Cubo\Config;
use Cubo\Controller;
use Cubo\Cubo;
use Cubo\Database\Db;
use Cubo\Exceptions\ControllerNotFoundException;
use Cubo\Logging\FileLogger;
use Cubo\ErrorHandler;
use Cubo\Routing\Route;
use Cubo\Routing\Router;

$raiz = dirname(__DIR__);

require $raiz . '/vendor/autoload.php';

ini_set('default_charset', 'utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Sao_Paulo');

$router = new Router();
$cubo = new Cubo('Controla', CoreController::class, router: $router);

// bootstrap e dispatch entram separados de proposito: o Config precisa estar
// carregado antes de o Db ler as credenciais do ini.
$cubo->bootstrap();

$config = Config::getInstance();
$appRoot = $raiz . '/app/Controla/';

$config->setConfig('core_template_root', $appRoot . 'views/');

if ($config->getConfig('ini.cubo.envi') === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    (new ErrorHandler(
        new FileLogger($appRoot . 'logs/erro.log'),
        (string) $config->getConfig('ini.cubo.host')
    ))->register();
}

Controller::setDefaultViewFactory(fn() => DefaultView::getInstance());

Db::getInstance()->connectFromConfig();

$rota = $router->parseUrl();

try {
    $cubo->dispatch($rota);
} catch (ControllerNotFoundException) {
    // A URL nao aponta para feature nenhuma. O Cubo faz a parte dele -- sinaliza
    // com uma excecao tipada -- e para por ai de proposito: traduzir isso em um
    // status HTTP e em uma pagina exige conhecer o layout da app, que o
    // framework nao pode nomear. Por isso o 404 mora aqui, e nao no Cubo.
    http_response_code(404);

    $cubo->dispatch(new Route('erro', 'index', ['caminho' => $rota->controller]));
}
