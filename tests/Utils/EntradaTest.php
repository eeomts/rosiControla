<?php

namespace Controla\Tests\Utils;

use Controla\Utils\Entrada;
use Cubo\Http\Request;
use Cubo\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Entrada::class)]
final class EntradaTest extends TestCase
{
    /**
     * @param array<string,mixed> $corpo
     * @param array<string,string> $rota
     */
    private static function entrada(array $corpo = [], array $rota = [], string $metodo = 'GET'): Entrada
    {
        return new Entrada(
            new Request(server: ['REQUEST_METHOD' => $metodo], get: [], post: $corpo, files: []),
            $rota === [] ? null : new Route('ciclo', 'form', $rota)
        );
    }

    // --- metodo ---

    public function testEhPostIgnoraCaixa(): void
    {
        $this->assertTrue(self::entrada(metodo: 'post')->ehPost());
        $this->assertTrue(self::entrada(metodo: 'POST')->ehPost());
    }

    public function testGetNaoEhPost(): void
    {
        $this->assertFalse(self::entrada(metodo: 'GET')->ehPost());
    }

    // --- corpo ---

    public function testCorpoDevolveOsCamposSemTocar(): void
    {
        // o trim e a normalizacao sao dos services; aqui nada e mexido
        $campos = ['nome' => '  Ciclo 12  ', 'num_ciclo' => '12'];

        $this->assertSame($campos, self::entrada($campos)->corpo());
    }

    public function testTextoCaiNoDefaultQuandoOCampoNaoVeio(): void
    {
        $entrada = self::entrada(['nome' => 'Ciclo 12']);

        $this->assertSame('Ciclo 12', $entrada->texto('nome'));
        $this->assertSame('', $entrada->texto('ausente'));
        $this->assertSame('padrao', $entrada->texto('ausente', 'padrao'));
    }

    public function testTextoIgnoraValorQueNaoEEscalar(): void
    {
        // input[name="nome[]"] chega como array e quebraria o (string)
        $this->assertSame('', self::entrada(['nome' => ['a', 'b']])->texto('nome'));
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
        $this->assertSame($esperado, self::entrada(['id' => $valor])->inteiroOuNulo('id'));
    }

    public function testInteiroOuNuloSemOCampoDevolveNull(): void
    {
        $this->assertNull(self::entrada()->inteiroOuNulo('id'));
    }

    // --- parametro do caminho ---

    public function testLeOIdDaUrlQuandoOCorpoNaoTem(): void
    {
        $entrada = self::entrada([], ['id' => '7']);

        $this->assertSame(7, $entrada->inteiroOuNulo('id'));
        $this->assertSame('7', $entrada->texto('id'));
    }

    public function testOCorpoTemPrecedenciaSobreOCaminho(): void
    {
        $this->assertSame(3, self::entrada(['id' => '3'], ['id' => '9'])->inteiroOuNulo('id'));
    }

    public function testSemRotaNaoQuebra(): void
    {
        $this->assertNull(self::entrada()->inteiroOuNulo('id'));
        $this->assertSame('', self::entrada()->texto('id'));
    }

    // --- linhas ---

    public function testLinhasDevolveOArrayDeItens(): void
    {
        $entrada = self::entrada(['itens' => [
            ['fk_variacao_produto' => '7', 'mon_venda' => '30,00'],
            ['fk_variacao_produto' => '8'],
        ]]);

        $this->assertCount(2, $entrada->linhas('itens'));
        $this->assertSame('7', $entrada->linhas('itens')[0]['fk_variacao_produto']);
    }

    public function testLinhasDeCampoAusenteEhListaVazia(): void
    {
        $this->assertSame([], self::entrada()->linhas('itens'));
    }

    public function testLinhasIgnoraCampoEscalar(): void
    {
        $this->assertSame([], self::entrada(['itens' => '7'])->linhas('itens'));
    }

    public function testLinhasDescartaOQueNaoEhLinhaEReindexa(): void
    {
        $entrada = self::entrada(['itens' => [0 => ['id' => 1], 1 => 'lixo', 2 => ['id' => 3]]]);

        $this->assertSame([['id' => 1], ['id' => 3]], $entrada->linhas('itens'));
    }
}
