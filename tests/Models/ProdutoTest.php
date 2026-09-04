<?php

namespace Controla\Tests\Models;

use Controla\Models\Genero;
use Controla\Models\Produto;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class ProdutoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();
    }

    public function testBuscaAchaPeloNome(): void
    {
        Produto::create(['nome' => 'Batom Vermelho Intenso']);
        Produto::create(['nome' => 'Shampoo Lumina']);

        $achados = Produto::query()->busca('batom')->get();

        $this->assertCount(1, $achados);
        $this->assertSame('Batom Vermelho Intenso', $achados->first()->nome);
    }

    public function testBuscaAchaPeloCodigo(): void
    {
        Produto::create(['nome' => 'Batom Vermelho', 'codigo_produto' => '82345']);
        Produto::create(['nome' => 'Shampoo Lumina', 'codigo_produto' => '90111']);

        $achados = Produto::query()->busca('823')->get();

        $this->assertCount(1, $achados);
        $this->assertSame('82345', $achados->first()->codigo_produto);
    }

    public function testBuscaComTermoVazioNaoFiltra(): void
    {
        Produto::create(['nome' => 'Batom Vermelho']);
        Produto::create(['nome' => 'Shampoo Lumina']);

        $this->assertCount(2, Produto::query()->busca('   ')->get());
    }

    public function testProdutoSabeOGeneroDele(): void
    {
        $genero = Genero::create(['nome' => 'Feminino']);
        $produto = Produto::create(['nome' => 'Batom Vermelho', 'fk_genero' => $genero->id]);

        $this->assertSame('Feminino', $produto->genero->nome);
    }

    public function testDoGeneroSeparaOCatalogo(): void
    {
        $feminino = Genero::create(['nome' => 'Feminino']);
        $masculino = Genero::create(['nome' => 'Masculino']);

        Produto::create(['nome' => 'Batom Vermelho', 'fk_genero' => $feminino->id]);
        Produto::create(['nome' => 'Perfume Essencial', 'fk_genero' => $masculino->id]);
        Produto::create(['nome' => 'Sabonete', 'fk_genero' => null]);

        $this->assertCount(1, Produto::query()->doGenero($feminino->id)->get());
        $this->assertCount(1, Produto::query()->doGenero($masculino->id)->get());
    }

    public function testAuxMontaOSelectEmOrdemAlfabetica(): void
    {
        Genero::create(['nome' => 'Unissex']);
        Genero::create(['nome' => 'Feminino']);
        Genero::create(['nome' => 'Masculino']);

        $this->assertSame(
            ['Feminino', 'Masculino', 'Unissex'],
            array_values(Genero::paraSelect())
        );
    }

    public function testProdutoExcluidoSaiDasConsultas(): void
    {
        $produto = Produto::create(['nome' => 'Batom Vermelho']);

        $produto->delete();

        $this->assertCount(0, Produto::getRecords());
        $this->assertCount(1, Produto::withTrashed()->get());
    }
}
