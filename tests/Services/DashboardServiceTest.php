<?php

namespace Controla\Tests\Services;

use Controla\Models\Ciclo;
use Controla\Models\Cliente;
use Controla\Models\Pedido;
use Controla\Models\Produto;
use Controla\Models\StatusEntrega;
use Controla\Models\StatusPagamento;
use Controla\Models\VariacaoProduto;
use Controla\Models\Venda;
use Controla\Services\DashboardService;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class DashboardServiceTest extends TestCase
{
    private const HOJE = '2026-09-21';

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        # o harness cria as tabelas _aux vazias; sem carga o idPorNome nao acha nada
        StatusPagamento::create(['nome' => 'Nao pago']);
        StatusPagamento::create(['nome' => 'Pago']);
        StatusEntrega::create(['nome' => 'Nao entregue']);
        StatusEntrega::create(['nome' => 'Entregue']);

        $this->service = new DashboardService();
    }

    # ------------------------------------------------------------- O CICLO

    public function testAchaOCicloQueValeHojeEContaOsDiasQueFaltam(): void
    {
        $this->criarCiclo('Ciclo 13', '2026-09-15', '2026-10-05');

        $resumo = $this->service->cicloVigente(self::HOJE);

        $this->assertTrue($resumo['vigente']);
        $this->assertSame('Ciclo 13', $resumo['ciclo']->nome);
        $this->assertSame(14, $resumo['dias']);
    }

    /** Regressao: ciclo que ainda NAO comecou era anunciado como terminado. */
    public function testCicloFuturoNaoEVigenteEInformaQuantoFaltaParaComecar(): void
    {
        $this->criarCiclo('Mateusteste', '2026-09-23', '2026-09-26');

        $resumo = $this->service->cicloVigente(self::HOJE);

        $this->assertFalse($resumo['vigente']);
        $this->assertSame(2, $resumo['comeca']);
        $this->assertGreaterThan(0, $resumo['dias']);
    }

    public function testCicloJaTerminadoDevolveDiasNegativos(): void
    {
        $this->criarCiclo('Ciclo 11', '2026-08-01', '2026-08-20');

        $resumo = $this->service->cicloVigente(self::HOJE);

        $this->assertFalse($resumo['vigente']);
        $this->assertLessThan(0, $resumo['dias']);
    }

    public function testSemNenhumCicloNaoQuebra(): void
    {
        $resumo = $this->service->cicloVigente(self::HOJE);

        $this->assertNull($resumo['ciclo']);
        $this->assertFalse($resumo['vigente']);
        $this->assertNull($resumo['dias']);
    }

    # ---------------------------------------------------------- A RECEBER

    public function testAReceberSomaSoOQueNaoFoiPago(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria Aparecida']);

        $this->criarVenda($cliente, '40.00', 'Nao pago');
        $this->criarVenda($cliente, '25.50', 'Nao pago');
        $this->criarVenda($cliente, '99.00', 'Pago');

        $receber = $this->service->aReceber();

        $this->assertSame('65.50', (string) $receber['total']);
        $this->assertSame(2, $receber['vendas']);
        $this->assertSame(1, $receber['clientes']);
    }

    public function testSemVendaEmAbertoOTotalEZero(): void
    {
        $receber = $this->service->aReceber();

        $this->assertSame(0, $receber['vendas']);
        $this->assertSame(0, $receber['clientes']);
    }

    # ------------------------------------------------------ ESTOQUE E LUCRO

    public function testEstoqueContaSoAUnidadeQueAindaNaoSaiu(): void
    {
        $ciclo = $this->criarCiclo('Ciclo 13', '2026-09-15', '2026-10-05');
        $pedido = $this->criarPedido($ciclo);

        $this->criarUnidade($pedido, '30.00', vendido: false);
        $this->criarUnidade($pedido, '30.00', vendido: false);
        $this->criarUnidade($pedido, '30.00', vendido: true);

        $estoque = $this->service->estoqueELucro($ciclo);

        $this->assertSame(2, $estoque['unidades']);
        $this->assertSame('60.00', (string) $estoque['valor']);
    }

    public function testLucroConsideraSoOsPedidosDoCicloInformado(): void
    {
        $doCiclo = $this->criarCiclo('Ciclo 13', '2026-09-15', '2026-10-05');
        $deOutro = $this->criarCiclo('Ciclo 12', '2026-08-01', '2026-08-20');

        $this->criarPedido($doCiclo, lucroReal: '19.90', lucroEstimado: '59.70');
        $this->criarPedido($deOutro, lucroReal: '500.00', lucroEstimado: '900.00');

        $estoque = $this->service->estoqueELucro($doCiclo);

        $this->assertSame('19.90', (string) $estoque['lucro_real']);
        $this->assertSame('59.70', (string) $estoque['lucro_estimado']);
    }

    public function testSemCicloOLucroSomaTodosOsPedidos(): void
    {
        $ciclo = $this->criarCiclo('Ciclo 13', '2026-09-15', '2026-10-05');

        $this->criarPedido($ciclo, lucroReal: '19.90', lucroEstimado: '59.70');
        $this->criarPedido($ciclo, lucroReal: '10.10', lucroEstimado: '40.30');

        $estoque = $this->service->estoqueELucro(null);

        $this->assertSame('30.00', (string) $estoque['lucro_real']);
        $this->assertSame('100.00', (string) $estoque['lucro_estimado']);
    }

    # -------------------------------------------------------------- ENTREGA

    public function testContaSoAsVendasQueAindaNaoForamEntregues(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria Aparecida']);

        $this->criarVenda($cliente, '40.00', 'Nao pago', 'Nao entregue');
        $this->criarVenda($cliente, '40.00', 'Pago', 'Entregue');

        $this->assertSame(1, $this->service->entregasPendentes());
    }

    public function testUltimasVendasTrazNoMaximoCinco(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria Aparecida']);

        for ($i = 0; $i < 7; $i++) {
            $this->criarVenda($cliente, '10.00', 'Pago');
        }

        $this->assertCount(5, $this->service->ultimasVendas());
    }

    # --------------------------------------------------------------- APOIO

    private function criarCiclo(string $nome, string $inicio, string $termino): Ciclo
    {
        return Ciclo::create([
            'nome' => $nome,
            'num_ciclo' => 13,
            'num_ano' => 2026,
            'data_inicio' => $inicio,
            'data_termino' => $termino,
        ]);
    }

    private function criarPedido(
        Ciclo $ciclo,
        string $lucroReal = '0.00',
        string $lucroEstimado = '0.00'
    ): Pedido {
        return Pedido::create([
            'fk_ciclo' => $ciclo->id,
            'nome' => 'C13-09-' . random_int(1, 9999),
            'data_pedido' => '2026-09-16',
            'mon_lucro_real' => $lucroReal,
            'mon_lucro_estimado' => $lucroEstimado,
        ]);
    }

    private function criarUnidade(Pedido $pedido, string $venda, bool $vendido): VariacaoProduto
    {
        $produto = Produto::create(['nome' => 'Batom Una']);

        return VariacaoProduto::create([
            'fk_produto' => $produto->id,
            'fk_pedido' => $pedido->id,
            'fk_ciclo' => $pedido->fk_ciclo,
            'mon_custo' => '10.00',
            'mon_venda' => $venda,
            'vendido' => $vendido ? 1 : 0,
        ]);
    }

    private function criarVenda(
        Cliente $cliente,
        string $total,
        string $pagamento,
        string $entrega = 'Entregue'
    ): Venda {
        return Venda::create([
            'fk_cliente' => $cliente->id,
            'fk_status_pagamento' => StatusPagamento::idPorNome($pagamento),
            'fk_status_entrega' => StatusEntrega::idPorNome($entrega),
            'data_venda' => '2026-09-20',
            'mon_total' => $total,
            'mon_desconto' => '0.00',
        ]);
    }
}
