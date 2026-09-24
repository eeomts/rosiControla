<?php

namespace Controla\Middleware;

use Controla\Controllers\ErroController;
use Controla\Utils\Csrf;
use Cubo\Http\Middleware;
use Cubo\Http\Request;
use Cubo\Http\Response;
use Cubo\Routing\Route;

/**
 * Barra o que chega sem o token do Csrf.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class CsrfMiddleware implements Middleware
{
    private const SEGUROS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, \Closure $next): Response
    {
        if (in_array($request->method(), self::SEGUROS, true)) {
            return $next($request);
        }

        if (Csrf::daGlobal()->valido($this->enviado($request))) {
            return $next($request);
        }

        return $this->recusar($request);
    }

    private function enviado(Request $request): ?string
    {
        $doCorpo = $request->post(Csrf::CAMPO);

        return is_string($doCorpo) && $doCorpo !== ''
            ? $doCorpo
            : $request->header(Csrf::CABECALHO);
    }

    
    private function recusar(Request $request): Response
    {
        $recado = 'A pagina ficou aberta tempo demais. Recarregue e tente de novo.';

        if ($request->header('X-Requested-With') === 'fetch') {
            return Response::json(['ok' => false, 'erro' => $recado], 419);
        }

        $controller = new ErroController(new Route('erro', 'sessao', []), $request);

        $controller->initialize();
        $controller->sessao();

        ob_start();
        $controller->getModule()->display();

        return Response::html((string) ob_get_clean(), 419);
    }
}
