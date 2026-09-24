<?php

namespace Controla\Tests\Utils;

use Controla\Models\Usuario;
use Controla\Utils\Csrf;
use Controla\Utils\Sessao;
use Cubo\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Sessao::class)]
final class SessaoTest extends TestCase
{
    private Session $cubo;

    private Sessao $sessao;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];

        // o construtor da Session chama session_start(), que nao roda em CLI
        $this->cubo = (new ReflectionClass(Session::class))->newInstanceWithoutConstructor();
        $this->sessao = new Sessao($this->cubo);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testComecaDeslogada(): void
    {
        $this->assertFalse($this->sessao->logada());
        $this->assertNull($this->sessao->usuarioId());
    }

    public function testEntrarGuardaIdENome(): void
    {
        $this->sessao->entrar($this->usuario());

        $this->assertTrue($this->sessao->logada());
        $this->assertSame(7, $this->sessao->usuarioId());
        $this->assertSame('Rosi', $this->sessao->nome());
    }

    public function testEntrarTrocaOTokenDoCsrf(): void
    {
        $csrf = new Csrf($this->cubo);
        $antes = $csrf->token();

        $this->sessao->entrar($this->usuario());

        $this->assertFalse($csrf->valido($antes));
    }

    public function testEntrarEncerraAConfirmacaoPendente(): void
    {
        $this->sessao->aguardarConfirmacao($this->usuario());
        $this->assertSame(7, $this->sessao->pendente());

        $this->sessao->entrar($this->usuario());

        $this->assertNull($this->sessao->pendente());
    }

    public function testSairEsqueceQuemEstavaLogada(): void
    {
        $this->sessao->entrar($this->usuario());

        $this->sessao->sair();

        $this->assertFalse($this->sessao->logada());
        $this->assertSame('', $this->sessao->nome());
    }

    private function usuario(): Usuario
    {
        $usuario = new Usuario(['nome' => 'Rosi', 'email' => 'rosi@exemplo.com']);
        $usuario->id = 7;

        return $usuario;
    }
}
