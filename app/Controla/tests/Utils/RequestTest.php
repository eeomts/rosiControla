<?php

namespace Controla\Tests\Utils;

use Controla\Utils\Request;
use Cubo\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    // --- metodo ---

    public function testEhPostIgnoraCaixa(): void
    {
        $this->assertTrue((new Request(metodo: 'post'))->ehPost());
        $this->assertTrue((new Request(metodo: 'POST'))->ehPost());
    }

    public function testGetNaoEhPost(): void
    {
        $this->assertFalse((new Request(metodo: 'GET'))->ehPost());
        $this->assertFalse((new Request())->ehPost());
    }

    // --- corpo ---

    public function testCorpoDevolveOsCamposSemTocar(): void
    {
        // o trim e a normalizacao sao dos services; aqui nada e mexido
        $campos = ['nome' => '  Ciclo 12  ', 'num_ciclo' => '12'];

        $this->assertSame($campos, (new Request($campos))->corpo());
    }

    public function testTextoCaiNoDefaultQuandoOCampoNaoVeio(): void
    {
        $request = new Request(['nome' => 'Ciclo 12']);

        $this->assertSame('Ciclo 12', $request->texto('nome'));
        $this->assertSame('', $request->texto('ausente'));
        $this->assertSame('padrao', $request->texto('ausente', 'padrao'));
    }

    public function testTextoIgnoraValorQueNaoEEscalar(): void
    {
        // input[name="nome[]"] chega como array e quebraria o (string)
        $request = new Request(['nome' => ['a', 'b']]);

        $this->assertSame('', $request->texto('nome'));
    }

    // --- id ---

    /** @return list<array{0: mixed, 1: int|null}> */
    public static function valoresDeId(): array
    {
        return [
            'numero' => ['5', 5],
            'inteiro' => [5, 5],
            'vazio' => ['', null],
            'texto' => ['abc', null],
            'zero' => ['0', null],
            'negativo' => ['-3', null],
            'array' => [['5'], null],
        ];
    }

    #[DataProvider('valoresDeId')]
    public function testInteiroOuNulo(mixed $valor, ?int $esperado): void
    {
        $this->assertSame($esperado, (new Request(['id' => $valor]))->inteiroOuNulo('id'));
    }

    public function testInteiroOuNuloSemOCampoDevolveNull(): void
    {
        $this->assertNull((new Request())->inteiroOuNulo('id'));
    }

    // --- rota ---

    public function testLeOIdDaUrlQuandoOCorpoNaoTem(): void
    {
        $request = new Request([], ['id' => '7']);

        $this->assertSame(7, $request->inteiroOuNulo('id'));
        $this->assertSame('7', $request->texto('id'));
    }

    public function testOCorpoTemPrecedenciaSobreAUrl(): void
    {
        $request = new Request(['id' => '3'], ['id' => '9']);

        $this->assertSame(3, $request->inteiroOuNulo('id'));
    }

    // --- superglobais ---

    public function testDaGlobalLeOPostEOMetodo(): void
    {
        $postAnterior = $_POST;
        $serverAnterior = $_SERVER;

        $_POST = ['nome' => 'Ciclo 12'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $request = Request::daGlobal(new Route('ciclo', 'salvar', ['id' => '4']));

            $this->assertTrue($request->ehPost());
            $this->assertSame(['nome' => 'Ciclo 12'], $request->corpo());
            $this->assertSame(4, $request->inteiroOuNulo('id'));
        } finally {
            $_POST = $postAnterior;
            $_SERVER = $serverAnterior;
        }
    }

    public function testDaGlobalSemRotaESemMetodoNaoQuebra(): void
    {
        $postAnterior = $_POST;
        $serverAnterior = $_SERVER;

        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);

        try {
            $request = Request::daGlobal();

            $this->assertFalse($request->ehPost());
            $this->assertSame([], $request->corpo());
        } finally {
            $_POST = $postAnterior;
            $_SERVER = $serverAnterior;
        }
    }

    // --- linhas ---

    public function testLinhasDevolveOArrayDeItens(): void
    {
        $request = new Request(corpo: ['itens' => [
            ['fk_variacao_produto' => '7', 'mon_venda' => '30,00'],
            ['fk_variacao_produto' => '8'],
        ]]);

        $this->assertCount(2, $request->linhas('itens'));
        $this->assertSame('7', $request->linhas('itens')[0]['fk_variacao_produto']);
    }

    public function testLinhasDeCampoAusenteEhListaVazia(): void
    {
        $this->assertSame([], (new Request())->linhas('itens'));
    }

    public function testLinhasIgnoraCampoEscalar(): void
    {
        $this->assertSame([], (new Request(corpo: ['itens' => '7']))->linhas('itens'));
    }

    public function testLinhasDescartaOQueNaoEhLinhaEReindexa(): void
    {
        $request = new Request(corpo: ['itens' => [0 => ['id' => 1], 1 => 'lixo', 2 => ['id' => 3]]]);

        $this->assertSame([['id' => 1], ['id' => 3]], $request->linhas('itens'));
    }
}
