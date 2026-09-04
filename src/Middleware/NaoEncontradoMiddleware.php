<?php

namespace Controla\Middleware;

use Controla\Controllers\ErroController;
use Cubo\Exceptions\ControllerNotFoundException;
use Cubo\Http\Middleware;
use Cubo\Http\Request;
use Cubo\Http\Response;
use Cubo\Routing\Route;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class NaoEncontradoMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ControllerNotFoundException) {
            return $this->pagina($request);
        }
    }

    /**
     * Renderiza a tela de erro do jeito que o kernel renderiza qualquer outra:
     * initialize(), a action, e o display() dentro de um buffer.
     */
    private function pagina(Request $request): Response
    {
        $controller = new ErroController(
            new Route('erro', 'index', ['caminho' => $this->telaPedida($request)]),
            $request
        );

        $controller->initialize();
        $controller->index();

        ob_start();
        $controller->getModule()->display();

        return Response::html((string) ob_get_clean(), 404);
    }

    /** Primeiro segmento do caminho: e o que a tela chama de "tela pedida". */
    private function telaPedida(Request $request): string
    {
        $caminho = trim($request->path(), '/');

        return $caminho === '' ? '' : explode('/', $caminho)[0];
    }
}
