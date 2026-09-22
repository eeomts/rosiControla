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
    /** Verbos que so leem; o resto passa pelo token. */
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

    /** Formulario manda no corpo; o fetch, no cabecalho. */
    private function enviado(Request $request): ?string
    {
        $doCorpo = $request->post(Csrf::CAMPO);

        return is_string($doCorpo) && $doCorpo !== ''
            ? $doCorpo
            : $request->header(Csrf::CABECALHO);
    }

    /**
     * 419 e o status que o ecossistema PHP usa para "a sessao expirou"; nao esta
     * na RFC, mas e o que Laravel e afins devolvem, e o 403 diria "voce nao pode"
     * quando a verdade e "tente de novo".
     */
    private function recusar(Request $request): Response
    {
        $recado = 'A pagina ficou aberta tempo demais. Recarregue e tente de novo.';

        # o fetch nao sabe ler uma pagina inteira: devolve o recado cru
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
