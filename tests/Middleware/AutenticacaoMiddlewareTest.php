<?php

namespace Controla\Tests\Middleware;

use Controla\Middleware\AutenticacaoMiddleware;
use Controla\Models\Usuario;
use Controla\Utils\Sessao;
use Cubo\Http\Request;
use Cubo\Http\Response;
use Cubo\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(AutenticacaoMiddleware::class)]
final class AutenticacaoMiddlewareTest extends TestCase
{
    private Sessao $sessao;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];

        // o construtor da Session chama session_start(), que nao roda em CLI
        $this->sessao = new Sessao((new ReflectionClass(Session::class))->newInstanceWithoutConstructor());
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testDeslogadaVaiParaOLogin(): void
    {
        $resposta = $this->passar('GET', '/pedido');

        $this->assertSame(303, $resposta->getStatus());
        $this->assertSame('/login', $resposta->getHeaders()['Location']);
    }

    public function testFetchDeslogadoRecebeJson401(): void
    {
        $resposta = $this->passar('POST', '/cliente/rapido', ['HTTP_X_REQUESTED_WITH' => 'fetch']);

        $this->assertSame(401, $resposta->getStatus());
        $this->assertStringContainsString('"ok":false', $resposta->getBody());
    }

    #[DataProvider('publicas')]
    public function testTelasDeEntrarNaoPedemLogin(string $caminho): void
    {
        $this->assertSame(200, $this->passar('GET', $caminho)->getStatus());
    }

    /** @return array<string,array{string}> */
    public static function publicas(): array
    {
        return [
            'login' => ['/login'],
            'login com barra no fim' => ['/login/'],
            'cadastro' => ['/cadastro'],
            'confirmacao' => ['/confirmacao'],
            'reenviar' => ['/confirmacao/reenviar'],
        ];
    }

    public function testLogadaPassaDireto(): void
    {
        $usuario = new Usuario(['nome' => 'Rosi']);
        $usuario->id = 1;
        $this->sessao->entrar($usuario);

        $this->assertSame(200, $this->passar('GET', '/pedido')->getStatus());
    }

    public function testQueryStringNaoAbreTelaPublica(): void
    {
        $this->assertSame(303, $this->passar('GET', '/pedido?x=/login')->getStatus());
    }

    /**
     * @param array<string,string> $servidor
     */
    private function passar(string $metodo, string $uri, array $servidor = []): Response
    {
        $request = new Request(['REQUEST_METHOD' => $metodo, 'REQUEST_URI' => $uri, ...$servidor], [], [], []);

        return (new AutenticacaoMiddleware($this->sessao))->handle($request, static fn (): Response => Response::text('ok'));
    }
}
