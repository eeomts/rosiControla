<?php

namespace Controla\Tests\Services;

use Controla\Models\Ciclo;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\RegistroEmUsoException;
use Controla\Models\Pedido;
use Controla\Models\VariacaoProduto;
use Controla\Services\PedidoService;
use Controla\Models\Produto;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class PedidoServiceTest extends TestCase
{
    private PedidoService $service;
    private Ciclo $ciclo;
    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->service = new PedidoService();

        $this->ciclo = Ciclo::create([
            'nome' => 'Ciclo 12',
            'num_ciclo' => 12,
            'num_ano' => 2026,
            'data_inicio' => '2026-08-01',
            'data_termino' => '2026-08-21',
        ]);

        $this->produto = Produto::create(['nome' => 'Batom Vermelho']);
    }

    # ------------------------------------------------------------- CABECALHO

    public function testSalvaUmPedidoNovo(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->assertTrue($pedido->exists);
        $this->assertSame('2026-08-05', $pedido->data_pedido->format('Y-m-d'));
    }

    public function testGeraONomeNoFormatoCicloMesSequencia(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->assertSame('C12-08-1', $pedido->nome);
    }

    public function testASequenciaContaOsPedidosDoMesmoCiclo(): void
    {
        $this->service->salvar(null, $this->dados());
        $segundo = $this->service->salvar(null, $this->dados());

        $this->assertSame('C12-08-2', $segundo->nome);
    }

    public function testNomeDigitadoEhRespeitado(): void
    {
        $pedido = $this->service->salvar(null, $this->dados(['nome' => 'Pedido da promocao']));

        $this->assertSame('Pedido da promocao', $pedido->nome);
    }

    public function testRecusaCicloInexistente(): void
    {
        $erros = $this->errosAoSalvar($this->dados(['fk_ciclo' => 999]));

        $this->assertArrayHasKey('fk_ciclo', $erros);
    }

    public function testRecusaPedidoSemData(): void
    {
        $erros = $this->errosAoSalvar($this->dados(['data_pedido' => '']));

        $this->assertArrayHasKey('data_pedido', $erros);
    }

    public function testReclamaDeIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->salvar(999, $this->dados());
    }

    # -------------------------------------------------------------- UNIDADES

    public function testCadaUnidadeVinraUmaLinha(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 3]));

        $this->assertCount(3, $unidades);
        $this->assertCount(3, VariacaoProduto::query()->doPedido($pedido->id)->get());
    }

    public function testAUnidadeHerdaOCicloDoPedido(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 1]));

        $this->assertSame($this->ciclo->id, $unidades[0]->fk_ciclo);
    }

    public function testAceitaPrecoEmFormatoBrasileiro(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $unidades = $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 1,
            'mon_custo' => 'R$ 1.234,56',
            'mon_venda' => '2.000,00',
        ]));

        $this->assertSame('1234.56', $unidades[0]->mon_custo);
        $this->assertSame('2000.00', $unidades[0]->mon_venda);
    }

    public function testRecusaQuantidadeZero(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $erros = $this->errosAoAdicionar($pedido, $this->itens(['quantidade' => 0]));

        $this->assertArrayHasKey('quantidade', $erros);
    }

    public function testRecusaProdutoInexistente(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $erros = $this->errosAoAdicionar($pedido, $this->itens(['fk_produto' => 999]));

        $this->assertArrayHasKey('fk_produto', $erros);
    }

    public function testRecusaPrecoVazio(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $erros = $this->errosAoAdicionar($pedido, $this->itens(['mon_custo' => '']));

        $this->assertArrayHasKey('mon_custo', $erros);
    }

    # ---------------------------------------------------------------- TOTAIS

    public function testTotalEhASomaDosCustosEOLucroEstimadoAMargem(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 3,
            'mon_custo' => '10,00',
            'mon_venda' => '25,00',
        ]));

        $this->assertSame('30.00', $pedido->mon_total);
        $this->assertSame('45.00', $pedido->mon_lucro_estimado);
    }

    public function testRemoverUnidadeRefazOsTotais(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $unidades = $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 2,
            'mon_custo' => '10,00',
            'mon_venda' => '25,00',
        ]));

        $this->service->removerUnidade($unidades[0]);

        $this->assertSame('10.00', $pedido->fresh()->mon_total);
        $this->assertSame('15.00', $pedido->fresh()->mon_lucro_estimado);
    }

    # -------------------------------------------------------------- ESTOQUE

    public function testDisponiveisAgrupadasContaAsUnidadesIdenticas(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 5,
            'mon_custo' => '10,00',
            'mon_venda' => '25,00',
        ]));

        $grupos = VariacaoProduto::disponiveisAgrupadas($this->produto->id);

        $this->assertCount(1, $grupos);
        $this->assertSame(5, (int) $grupos->first()->quantidade);
    }

    public function testValidadeDiferenteSeparaOsGrupos(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 3,
            'data_validade' => '01/03/2027',
        ]));
        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 2,
            'data_validade' => '01/08/2027',
        ]));

        $grupos = VariacaoProduto::disponiveisAgrupadas($this->produto->id);

        $this->assertCount(2, $grupos);
        $this->assertSame([3, 2], $grupos->map(fn($g) => (int) $g->quantidade)->all());
    }

    public function testUnidadeVendidaSaiDoAgrupamento(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 4]));

        $unidades[0]->vendido = 1;
        $unidades[0]->save();

        $grupos = VariacaoProduto::disponiveisAgrupadas($this->produto->id);

        $this->assertSame(3, (int) $grupos->first()->quantidade);
    }

    public function testUltimaDoProdutoServeParaRepetirOsPrecos(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 1,
            'mon_custo' => '10,00',
            'mon_venda' => '25,00',
        ]));
        $this->service->adicionarProduto($pedido, $this->itens([
            'quantidade' => 1,
            'mon_custo' => '12,00',
            'mon_venda' => '29,00',
        ]));

        $ultima = VariacaoProduto::ultimaDoProduto($this->produto->id);

        $this->assertSame('12.00', $ultima->mon_custo);
        $this->assertSame('29.00', $ultima->mon_venda);
    }

    # ---------------------------------------------------------------- APOIO

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function dados(array $troca = []): array
    {
        return $troca + [
            'fk_ciclo' => $this->ciclo->id,
            'data_pedido' => '05/08/2026',
        ];
    }

    # ------------------------------------------------- LISTAR E EXCLUIR

    public function testListaDoMaisRecenteParaOMaisAntigo(): void
    {
        $this->service->salvar(null, $this->dados(['data_pedido' => '05/08/2026']));
        $this->service->salvar(null, $this->dados(['data_pedido' => '15/08/2026']));

        $this->assertSame(
            ['2026-08-15', '2026-08-05'],
            $this->service->listar()->map(fn(Pedido $p) => $p->data_pedido->format('Y-m-d'))->all()
        );
    }

    public function testEncontraPeloId(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());

        $this->assertSame($pedido->id, $this->service->encontrar($pedido->id)->id);
    }

    public function testEncontrarSemIdReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->encontrar(null);
    }

    /** Pedido cadastrado errado sai inteiro, sem tirar unidade por unidade. */
    public function testExcluirLevaAsUnidadesJunto(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 3]));

        $excluido = $this->service->excluir($pedido->id);

        $this->assertTrue($excluido->trashed());
        $this->assertCount(0, VariacaoProduto::getRecords());
        $this->assertCount(3, VariacaoProduto::withTrashed()->get());
    }

    public function testNaoExcluiPedidoComUnidadeJaVendida(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 2]));

        $unidades[0]->vendido = 1;
        $unidades[0]->save();

        $this->expectException(RegistroEmUsoException::class);

        $this->service->excluir($pedido->id);
    }

    public function testPedidoComUnidadeVendidaContinuaInteiro(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 2]));

        $unidades[0]->vendido = 1;
        $unidades[0]->save();

        try {
            $this->service->excluir($pedido->id);
        } catch (RegistroEmUsoException) {
            // o que importa e nada ter sumido
        }

        $this->assertCount(1, $this->service->listar());
        $this->assertCount(2, VariacaoProduto::getRecords());
    }

    public function testNaoRemoveUnidadeJaVendida(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $unidades = $this->service->adicionarProduto($pedido, $this->itens());

        $unidades[0]->vendido = 1;
        $unidades[0]->save();

        $this->expectException(RegistroEmUsoException::class);

        $this->service->removerUnidade($unidades[0]->fresh());
    }

    public function testEncontraAUnidadePeloId(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $unidades = $this->service->adicionarProduto($pedido, $this->itens());

        $this->assertSame($unidades[0]->id, $this->service->encontrarUnidade($unidades[0]->id)->id);
    }

    public function testEncontrarUnidadeInexistenteReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->encontrarUnidade(999);
    }

    # ----------------------------------------------- UNIDADES AGRUPADAS

    public function testAgrupaAsUnidadesIdenticasDoPedido(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 3]));

        $grupos = $this->service->unidadesAgrupadas($pedido);

        $this->assertCount(1, $grupos);
        $this->assertCount(3, $grupos[0]['ids']);
        $this->assertSame('Batom Vermelho', $grupos[0]['produto']);
        $this->assertSame(0, $grupos[0]['vendidas']);
    }

    public function testSeparaOsGruposQuandoOPrecoMuda(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $this->service->adicionarProduto($pedido, $this->itens(['mon_venda' => '25,00']));
        $this->service->adicionarProduto($pedido, $this->itens(['mon_venda' => '30,00']));

        $this->assertCount(2, $this->service->unidadesAgrupadas($pedido));
    }

    public function testContaQuantasDoGrupoJaForamVendidas(): void
    {
        $pedido = $this->service->salvar(null, $this->dados());
        $unidades = $this->service->adicionarProduto($pedido, $this->itens(['quantidade' => 3]));

        $unidades[0]->vendido = 1;
        $unidades[0]->save();

        $grupos = $this->service->unidadesAgrupadas($pedido);

        $this->assertSame(1, $grupos[0]['vendidas']);
        $this->assertCount(3, $grupos[0]['ids']);
    }

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function itens(array $troca = []): array
    {
        return $troca + [
            'fk_produto' => $this->produto->id,
            'quantidade' => 1,
            'mon_custo' => '10,00',
            'mon_venda' => '25,00',
            'data_validade' => '01/03/2027',
        ];
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,string>
     */
    private function errosAoSalvar(array $dados): array
    {
        try {
            $this->service->salvar(null, $dados);
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('Esperava DadosInvalidosException.');
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,string>
     */
    private function errosAoAdicionar(Pedido $pedido, array $dados): array
    {
        try {
            $this->service->adicionarProduto($pedido, $dados);
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('Esperava DadosInvalidosException.');
    }
}
