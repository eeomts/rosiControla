<?php

namespace Controla\Middleware;

use Controla\Utils\Sessao;
use Cubo\Http\Middleware;
use Cubo\Http\Request;
use Cubo\Http\Response;

/**
 * Tudo exige login, menos as telas de entrar. Rota nova ja nasce protegida.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class AutenticacaoMiddleware implements Middleware
{
    public const URL_LOGIN = '/login';

    /** @var list<string> */
    private const PUBLICAS = [
        self::URL_LOGIN,
        '/cadastro',
        '/confirmacao',
        '/confirmacao/reenviar',
    ];

    /** @param Sessao|null $sessao null le a sessao do PHP; o teste injeta */
    public function __construct(private readonly ?Sessao $sessao = null) {}

    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->ehPublica($request) || $this->sessao()->logada()) {
            return $next($request);
        }

        if ($request->header('X-Requested-With') === 'fetch') {
            return Response::json(['ok' => false, 'erro' => 'Voce saiu do sistema. Entre de novo.'], 401);
        }

        return Response::redirect(self::URL_LOGIN, 303);
    }

    private function ehPublica(Request $request): bool
    {
        $caminho = '/' . trim($request->path(), '/');

        return in_array($caminho, self::PUBLICAS, true);
    }

    private function sessao(): Sessao
    {
        return $this->sessao ?? Sessao::daGlobal();
    }
}
