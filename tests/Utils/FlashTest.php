<?php

namespace Controla\Tests\Utils;

use Controla\Utils\Flash;
use Cubo\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Flash::class)]
final class FlashTest extends TestCase
{
    private Flash $flash;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];

        // o construtor da Session chama session_start(), que nao roda em CLI
        $sessao = (new ReflectionClass(Session::class))->newInstanceWithoutConstructor();

        $this->flash = new Flash($sessao);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testSemRecadoNaoDevolveNada(): void
    {
        $this->assertNull($this->flash->consumir());
    }

    public function testGuardaOSucessoParaAProximaTela(): void
    {
        $this->flash->sucesso('Ciclo 11 salvo.');

        $this->assertSame(
            ['tipo' => Flash::SUCESSO, 'mensagem' => 'Ciclo 11 salvo.'],
            $this->flash->consumir()
        );
    }

    public function testGuardaOErro(): void
    {
        $this->flash->erro('Esse ciclo nao existe mais.');

        $this->assertSame(Flash::ERRO, $this->flash->consumir()['tipo']);
    }

    public function testConsumirApagaORecado(): void
    {
        $this->flash->sucesso('Ciclo 11 salvo.');

        $this->flash->consumir();

        $this->assertNull($this->flash->consumir(), 'o recado nao pode sobreviver a leitura');
        $this->assertArrayNotHasKey('flash', $_SESSION);
    }

    public function testORecadoNovoSubstituiOAnterior(): void
    {
        $this->flash->sucesso('Ciclo 11 salvo.');
        $this->flash->erro('Esse ciclo nao existe mais.');

        $this->assertSame(
            ['tipo' => Flash::ERRO, 'mensagem' => 'Esse ciclo nao existe mais.'],
            $this->flash->consumir()
        );
    }

    public function testLixoNaSessaoNaoViraRecado(): void
    {
        $_SESSION['flash'] = 'string solta';

        $this->assertNull($this->flash->consumir());
        $this->assertArrayNotHasKey('flash', $_SESSION);
    }
}
