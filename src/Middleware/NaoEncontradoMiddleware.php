<?php

namespace Controla\Middleware;

use Controla\Controllers\ErroController;
use Cubo\Exceptions\ControllerNotFoundException;
use Cubo\Exceptions\MethodNotAllowedException;
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
            return $this->pagina($request, 'index', ['caminho' => $this->telaPedida($request)], 404);
        } catch (MethodNotAllowedException $e) {
            # o Allow e exigencia da RFC 9110 para o 405, e sai da propria excecao
            return $this->pagina($request, 'metodo', [], 405)
                ->header('Allow', implode(', ', $e->permitidos()));
        }
    }

    /**
     * @param array<string,string> $params
     */
    private function pagina(Request $request, string $acao, array $params, int $status): Response
    {
        $controller = new ErroController(new Route('erro', $acao, $params), $request);

        $controller->initialize();

        $controller->{$acao}();

        ob_start();
        $controller->getModule()->display();

        return Response::html((string) ob_get_clean(), $status);
    }

    /** Primeiro segmento do caminho: e o que a tela chama de "tela pedida". */
    private function telaPedida(Request $request): string
    {
        $caminho = trim($request->path(), '/');

        return $caminho === '' ? '' : explode('/', $caminho)[0];
    }
}
