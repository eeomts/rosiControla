<?php

namespace Controla\Tests\Services;

use Controla\Models\Cliente;
use Controla\Models\Pedido;
use Controla\Models\Produto;
use Controla\Models\VariacaoProduto;
use Controla\Models\Venda;
use Controla\Models\VendaVariacaoRel;
use Controla\Services\VendaService;
use Controla\Tests\Support\ControlaSchema;
use Controla\Utils\Exceptions\DadosInvalidosException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class VendaServiceTest extends TestCase
{
    private const NAO_PAGO = 1;
    private const PAGO = 2;
    private const NAO_ENTREGUE = 1;

    private VendaService $service;
    private Cliente $cliente;
    private Produto $produto;
    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->service = new VendaService();
        $this->cliente = Cliente::create(['nome' => 'Maria Aparecida']);
        $this->produto = Produto::create(['nome' => 'Batom Una']);
        $this->pedido = $this->criarPedido();
    }

    # ----------------------------------------------------------- O BASICO

    public function testVendeUmaUnidade(): void
    {
        $unidade = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id],
        ]);

        $this->assertTrue($venda->exists);
        $this->assertSame('30.00', $venda->mon_total);
        $this->assertCount(1, $venda->itens);
    }

    public function testVenderTiraAUnidadeDoEstoque(): void
    {
        $unidade = $this->criarUnidade();

        $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $this->assertTrue($unidade->fresh()->vendido);
        $this->assertCount(0, VariacaoProduto::query()->disponivel()->get());
    }

    public function testOPrecoSugeridoEhODaVariacao(): void
    {
        $unidade = $this->criarUnidade(['mon_venda' => '44.90']);

        $venda = $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $this->assertSame('44.90', $venda->itens->first()->mon_venda);
    }

    public function testElaPodeCobrarOutroPreco(): void
    {
        $unidade = $this->criarUnidade(['mon_venda' => '44.90']);

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id, 'mon_venda' => '40,00'],
        ]);

        $this->assertSame('40.00', $venda->itens->first()->mon_venda);
        $this->assertSame('40.00', $venda->mon_total);
    }

    public function testVendeUnidadesDeCiclosDiferentesNaMesmaVenda(): void
    {
        $outro = $this->criarPedido(['nome' => 'C13-09-1', 'fk_ciclo' => 2]);

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
            ['fk_variacao_produto' => $this->criarUnidade(['fk_pedido' => $outro->id, 'fk_ciclo' => 2])->id],
        ]);

        $this->assertSame('60.00', $venda->mon_total);
    }

    # ----------------------------------------------------------- DESCONTO

    public function testDescontoNoItem(): void
    {
        $unidade = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id, 'mon_desconto' => '5,00'],
        ]);

        $this->assertSame('25.00', $venda->mon_total);
    }

    public function testBrindeEhItemComCemPorCentoDeDesconto(): void
    {
        $unidade = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id, 'mon_desconto' => '30,00'],
        ]);

        $this->assertSame('0.00', $venda->mon_total);
        $this->assertTrue($unidade->fresh()->vendido);
    }

    public function testDescontoDaVendaEhRateadoProporcionalmenteEGravado(): void
    {
        $caro = $this->criarUnidade(['mon_venda' => '30.00']);
        $barato = $this->criarUnidade(['mon_venda' => '10.00']);

        $venda = $this->service->salvar(null, $this->dados(['mon_desconto' => '8,00']), [
            ['fk_variacao_produto' => $caro->id],
            ['fk_variacao_produto' => $barato->id],
        ]);

        $descontos = $venda->itens->pluck('mon_desconto', 'fk_variacao_produto');

        $this->assertSame('6.00', $descontos[$caro->id]);
        $this->assertSame('2.00', $descontos[$barato->id]);
        $this->assertSame('32.00', $venda->mon_total);
    }

    public function testASobraDeCentavosCaiNoUltimoItem(): void
    {
        $itens = [];

        for ($i = 0; $i < 3; $i++) {
            $itens[] = ['fk_variacao_produto' => $this->criarUnidade(['mon_venda' => '10.00'])->id];
        }

        $venda = $this->service->salvar(null, $this->dados(['mon_desconto' => '10,00']), $itens);

        $this->assertSame(
            ['3.33', '3.33', '3.34'],
            $venda->itens->pluck('mon_desconto')->all()
        );
        $this->assertSame('20.00', $venda->mon_total);
    }

    public function testODescontoDaVendaSomaAoDoItem(): void
    {
        $unidade = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(['mon_desconto' => '5,00']), [
            ['fk_variacao_produto' => $unidade->id, 'mon_desconto' => '10,00'],
        ]);

        $this->assertSame('15.00', $venda->itens->first()->mon_desconto);
        $this->assertSame('15.00', $venda->mon_total);
    }

    public function testVendaSoDeBrindeNaoQuebraORateio(): void
    {
        $unidade = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id, 'mon_desconto' => '30,00'],
        ]);

        $this->assertSame('0.00', $venda->mon_total);
    }

    # --------------------------------------------------------- LUCRO REAL

    public function testAVendaAlimentaOLucroRealDoPedido(): void
    {
        $unidade = $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);

        $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $this->assertSame('20.00', $this->pedido->fresh()->mon_lucro_real);
    }

    public function testOLucroRealDescontaOQueElaAbateu(): void
    {
        $unidade = $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);

        $this->service->salvar(null, $this->dados(['mon_desconto' => '5,00']), [
            ['fk_variacao_produto' => $unidade->id],
        ]);

        $this->assertSame('15.00', $this->pedido->fresh()->mon_lucro_real);
    }

    public function testCadaPedidoRecebeSoOLucroDasUnidadesDele(): void
    {
        $outro = $this->criarPedido(['nome' => 'C13-09-1', 'fk_ciclo' => 2]);
        $daqui = $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);
        $dali = $this->criarUnidade([
            'fk_pedido' => $outro->id, 'fk_ciclo' => 2, 'mon_custo' => '5.00', 'mon_venda' => '25.00',
        ]);

        $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $daqui->id],
            ['fk_variacao_produto' => $dali->id],
        ]);

        $this->assertSame('20.00', $this->pedido->fresh()->mon_lucro_real);
        $this->assertSame('20.00', $outro->fresh()->mon_lucro_real);
    }

    public function testUnidadeNaoVendidaNaoContaNoLucroReal(): void
    {
        $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);

        $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00'])->id],
        ]);

        $this->assertSame('20.00', $this->pedido->fresh()->mon_lucro_real);
    }

    # ------------------------------------------------------------ EDICAO

    public function testAsUnidadesDaPropriaVendaContinuamValendoNaEdicao(): void
    {
        $unidade = $this->criarUnidade();
        $venda = $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $editada = $this->service->salvar($venda->id, $this->dados(['fk_status_pagamento' => self::PAGO]), [
            ['fk_variacao_produto' => $unidade->id],
        ]);

        $this->assertSame($venda->id, $editada->id);
        $this->assertSame(self::PAGO, $editada->fk_status_pagamento);
        $this->assertCount(1, $editada->itens()->get());
    }

    public function testItemRetiradoNaEdicaoVoltaAoEstoque(): void
    {
        $fica = $this->criarUnidade();
        $sai = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $fica->id],
            ['fk_variacao_produto' => $sai->id],
        ]);

        $this->service->salvar($venda->id, $this->dados(), [['fk_variacao_produto' => $fica->id]]);

        $this->assertFalse($sai->fresh()->vendido);
        $this->assertTrue($fica->fresh()->vendido);
    }

    public function testAEdicaoRefazOLucroRealDoPedido(): void
    {
        $uma = $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);
        $outra = $this->criarUnidade(['mon_custo' => '10.00', 'mon_venda' => '30.00']);

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $uma->id],
            ['fk_variacao_produto' => $outra->id],
        ]);

        $this->assertSame('40.00', $this->pedido->fresh()->mon_lucro_real);

        $this->service->salvar($venda->id, $this->dados(), [['fk_variacao_produto' => $uma->id]]);

        $this->assertSame('20.00', $this->pedido->fresh()->mon_lucro_real);
    }

    public function testAEdicaoNaoDeixaItemOrfaoParaTras(): void
    {
        $uma = $this->criarUnidade();
        $outra = $this->criarUnidade();

        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $uma->id],
            ['fk_variacao_produto' => $outra->id],
        ]);

        $this->service->salvar($venda->id, $this->dados(), [['fk_variacao_produto' => $uma->id]]);

        $this->assertCount(1, VendaVariacaoRel::query()->daVenda($venda->id)->get());
    }

    # ----------------------------------------------------------- EXCLUSAO

    public function testExcluirDevolveTudoAoEstoque(): void
    {
        $unidade = $this->criarUnidade();
        $venda = $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $excluida = $this->service->excluir($venda->id);

        $this->assertTrue($excluida->trashed());
        $this->assertFalse($unidade->fresh()->vendido);
        $this->assertSame('0.00', $this->pedido->fresh()->mon_lucro_real);
    }

    public function testExcluirSomeDaListaSemApagarALinha(): void
    {
        $venda = $this->service->salvar(null, $this->dados(), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);

        $this->service->excluir($venda->id);

        $this->assertCount(0, $this->service->listar());
        $this->assertCount(1, Venda::withTrashed()->get());
    }

    public function testExcluirIdInexistenteReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->excluir(999);
    }

    # ---------------------------------------------------------- VALIDACAO

    public function testRecusaVendaSemItem(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(), []);

        $this->assertArrayHasKey('itens', $erros);
    }

    public function testRecusaUnidadeJaVendida(): void
    {
        $unidade = $this->criarUnidade();
        $this->service->salvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $erros = $this->errosAoSalvar(null, $this->dados(), [['fk_variacao_produto' => $unidade->id]]);

        $this->assertArrayHasKey('itens.0', $erros);
    }

    public function testRecusaAMesmaUnidadeDuasVezesNaVenda(): void
    {
        $unidade = $this->criarUnidade();

        $erros = $this->errosAoSalvar(null, $this->dados(), [
            ['fk_variacao_produto' => $unidade->id],
            ['fk_variacao_produto' => $unidade->id],
        ]);

        $this->assertArrayHasKey('itens.1', $erros);
    }

    public function testRecusaUnidadeInexistente(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(), [['fk_variacao_produto' => 999]]);

        $this->assertArrayHasKey('itens.0', $erros);
    }

    public function testRecusaClienteInexistente(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['fk_cliente' => 999]), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);

        $this->assertArrayHasKey('fk_cliente', $erros);
    }

    public function testRecusaStatusInexistente(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['fk_status_pagamento' => 99]), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);

        $this->assertArrayHasKey('fk_status_pagamento', $erros);
    }

    public function testRecusaDataInvalida(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['data_venda' => '31/02/2026']), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);

        $this->assertArrayHasKey('data_venda', $erros);
    }

    public function testRecusaDescontoMaiorQueAVenda(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['mon_desconto' => '999,00']), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);

        $this->assertArrayHasKey('mon_desconto', $erros);
    }

    public function testRecusaDescontoDeItemMaiorQueOItem(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(), [
            ['fk_variacao_produto' => $this->criarUnidade()->id, 'mon_desconto' => '999,00'],
        ]);

        $this->assertArrayHasKey('itens.0', $erros);
    }

    public function testNaoGravaNadaQuandoAValidacaoFalha(): void
    {
        $unidade = $this->criarUnidade();

        $this->errosAoSalvar(null, $this->dados(['fk_cliente' => 999]), [
            ['fk_variacao_produto' => $unidade->id],
        ]);

        $this->assertCount(0, Venda::getRecords());
        $this->assertFalse($unidade->fresh()->vendido);
    }

    public function testReclamaDeIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->salvar(999, $this->dados(), [
            ['fk_variacao_produto' => $this->criarUnidade()->id],
        ]);
    }

    # ------------------------------------------------------------ APOIO

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function dados(array $troca = []): array
    {
        return $troca + [
            'fk_cliente' => $this->cliente->id,
            'fk_status_pagamento' => self::NAO_PAGO,
            'fk_status_entrega' => self::NAO_ENTREGUE,
            'data_venda' => '15/08/2026',
        ];
    }

    /**
     * @param array<string,mixed> $troca
     */
    private function criarPedido(array $troca = []): Pedido
    {
        return Pedido::create($troca + [
            'fk_ciclo' => 1,
            'nome' => 'C12-08-1',
            'data_pedido' => '2026-08-01',
        ]);
    }

    /**
     * @param array<string,mixed> $troca
     */
    private function criarUnidade(array $troca = []): VariacaoProduto
    {
        return VariacaoProduto::create($troca + [
            'fk_produto' => $this->produto->id,
            'fk_pedido' => $this->pedido->id,
            'fk_ciclo' => 1,
            'mon_custo' => '10.00',
            'mon_venda' => '30.00',
            'vendido' => 0,
        ]);
    }

    /**
     * @param array<string,mixed> $dados
     * @param list<array<string,mixed>> $itens
     * @return array<string,string>
     */
    private function errosAoSalvar(?int $id, array $dados, array $itens): array
    {
        try {
            $this->service->salvar($id, $dados, $itens);
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('Esperava DadosInvalidosException.');
    }
}
