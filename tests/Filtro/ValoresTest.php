<?php

namespace Controla\Tests\Filtro;

use Controla\Filtro\Campo;
use Controla\Filtro\Definicao;
use Controla\Filtro\Faixa;
use Controla\Filtro\Tipo;
use Controla\Filtro\Valores;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Valores::class)]
#[CoversClass(Campo::class)]
#[CoversClass(Definicao::class)]
#[CoversClass(Faixa::class)]
#[CoversClass(Tipo::class)]
final class ValoresTest extends TestCase
{
    private static function definicao(): Definicao
    {
        return new Definicao(
            Campo::de('venda.fk_cliente', 'Cliente'),
            Campo::de('fk_status_pagamento', 'Pagamento', multiplo: true),
            Campo::de('data_venda', 'Periodo'),
            Campo::de('mon_total', 'Valor'),
            Campo::de('nome', 'Nome'),
        );
    }

    public function testIgnoraOQueNaoFoiDeclarado(): void
    {
        $valores = Valores::deRequest(['telefone' => '9999', 'nome' => 'Rosi'], self::definicao());

        $this->assertNull($valores->para('telefone'));
        $this->assertSame('Rosi', $valores->para('nome'));
    }

    public function testChaveSaiDaColunaSemOPrefixoDaTabela(): void
    {
        $valores = Valores::deRequest(['fk_cliente' => '7'], self::definicao());

        $this->assertSame('7', $valores->para('fk_cliente'));
    }

    /** O zero e um valor: "nao pago" e 0, e nao pode sumir como no empty(). */
    public function testZeroNaoEVazio(): void
    {
        $valores = Valores::deRequest(['fk_status_pagamento' => '0'], self::definicao());

        $this->assertSame(['0'], $valores->para('fk_status_pagamento'));
    }

    public function testStringVaziaEEspacoSaoIgnorados(): void
    {
        $valores = Valores::deRequest(['nome' => '   ', 'fk_cliente' => ''], self::definicao());

        $this->assertTrue($valores->vazio());
    }

    public function testCampoMultiploAceitaVariosValores(): void
    {
        $valores = Valores::deRequest(['fk_status_pagamento' => ['1', '2']], self::definicao());

        $this->assertSame(['1', '2'], $valores->para('fk_status_pagamento'));
    }

    public function testCampoSimplesRecusaArray(): void
    {
        $valores = Valores::deRequest(['fk_cliente' => ['1', '2']], self::definicao());

        $this->assertNull($valores->para('fk_cliente'));
    }

    public function testFaixaLeOsDoisLadosPelosSufixos(): void
    {
        $valores = Valores::deRequest(
            ['data_venda_de' => '01/09/2026', 'mon_total_ate' => '1.234,56'],
            self::definicao()
        );

        $periodo = $valores->para('data_venda');
        $valor = $valores->para('mon_total');

        $this->assertInstanceOf(Faixa::class, $periodo);
        $this->assertSame('01/09/2026', $periodo->de);
        $this->assertFalse($periodo->temAte());

        $this->assertInstanceOf(Faixa::class, $valor);
        $this->assertSame('1.234,56', $valor->ate);
    }

    public function testFaixaSemNenhumLadoNaoEntra(): void
    {
        $valores = Valores::deRequest(['data_venda_de' => '', 'data_venda_ate' => ''], self::definicao());

        $this->assertNull($valores->para('data_venda'));
    }

    public function testComoArrayRemontaAQueryString(): void
    {
        $cru = ['nome' => 'Rosi', 'data_venda_de' => '01/09/2026', 'fk_status_pagamento' => ['1']];

        $valores = Valores::deRequest($cru, self::definicao());

        $this->assertSame(
            ['fk_status_pagamento' => ['1'], 'data_venda_de' => '01/09/2026', 'nome' => 'Rosi'],
            $valores->comoArray()
        );
    }

    public function testTipoSaiDoPrefixoDaColuna(): void
    {
        $this->assertSame(Tipo::Chave, Tipo::deColuna('venda.fk_cliente'));
        $this->assertSame(Tipo::Data, Tipo::deColuna('data_venda'));
        $this->assertSame(Tipo::Moeda, Tipo::deColuna('mon_total'));
        $this->assertSame(Tipo::Numero, Tipo::deColuna('num_ciclo'));
        $this->assertSame(Tipo::Texto, Tipo::deColuna('nome'));
    }

    public function testDefinicaoRecusaCampoRepetido(): void
    {
        $this->expectExceptionMessage("Campo repetido na definicao: 'nome'");

        new Definicao(Campo::de('nome', 'Nome'), Campo::de('cliente.nome', 'Cliente'));
    }

    public function testSoChaveAceitaVariosValores(): void
    {
        $this->expectExceptionMessage('so campo Chave aceita varios valores');

        Campo::de('nome', 'Nome', multiplo: true);
    }
}
