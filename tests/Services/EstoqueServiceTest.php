<?php

namespace Controla\Tests\Services;

use Controla\Filtro\Valores;
use Controla\Models\Categoria;
use Controla\Models\Pedido;
use Controla\Models\Produto;
use Controla\Models\VariacaoProduto;
use Controla\Services\EstoqueService;
use Controla\Tests\Support\ControlaSchema;
use Controla\Types\Validade;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(EstoqueService::class)]
final class EstoqueServiceTest extends TestCase
{
    private EstoqueService $service;

    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();
        Carbon::setTestNow(Carbon::parse('2026-09-24 10:00:00'));

        $this->service = new EstoqueService();
        $this->pedido = Pedido::create(['fk_ciclo' => 1, 'nome' => 'C12-09-1']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testJuntaUnidadesIguaisNumLote(): void
    {
        $kaiak = $this->produto('Kaiak 100ml', 'Perfumaria');
        $this->unidades($kaiak, 3, '2027-03-01', '90.00', '130.00');

        $produto = $this->estoque()['produtos'][0];

        $this->assertSame(3, $produto['quantidade']);
        $this->assertCount(1, $produto['lotes']);
        $this->assertSame('C12-09-1', $produto['lotes'][0]['pedido']);
    }

    public function testValidadeOuPrecoDiferenteSeparaOLote(): void
    {
        $kaiak = $this->produto('Kaiak 100ml', 'Perfumaria');
        $this->unidades($kaiak, 2, '2027-03-01', '90.00', '130.00');
        $this->unidades($kaiak, 1, '2027-06-01', '90.00', '130.00');
        $this->unidades($kaiak, 1, '2027-03-01', '90.00', '140.00');

        $produto = $this->estoque()['produtos'][0];

        $this->assertSame(4, $produto['quantidade']);
        $this->assertCount(3, $produto['lotes']);
    }

    public function testVendidaNaoConta(): void
    {
        $kaiak = $this->produto('Kaiak 100ml', 'Perfumaria');
        $this->unidades($kaiak, 2, '2027-03-01', '90.00', '130.00');
        $this->unidades($kaiak, 1, '2027-03-01', '90.00', '130.00', vendido: true);

        $this->assertSame(2, $this->estoque()['produtos'][0]['quantidade']);
    }

    public function testProdutoSemEstoqueNaoAparece(): void
    {
        $this->produto('Batom sem unidade', 'Rosto e maquiagem');

        $this->assertSame([], $this->estoque()['produtos']);
    }

    public function testCustoVendaELucroSomamPorProduto(): void
    {
        $kaiak = $this->produto('Kaiak 100ml', 'Perfumaria');
        $this->unidades($kaiak, 2, '2027-03-01', '90.00', '130.00');

        $produto = $this->estoque()['produtos'][0];

        $this->assertEqualsWithDelta(180.0, $produto['custo'], 0.001);
        $this->assertEqualsWithDelta(260.0, $produto['venda'], 0.001);
        $this->assertEqualsWithDelta(80.0, $produto['lucro'], 0.001);
    }

    public function testMaisPertoDeVencerVemPrimeiro(): void
    {
        $this->unidades($this->produto('Sem validade', 'Cabelos'), 1, null, '10.00', '20.00');
        $this->unidades($this->produto('Longe', 'Cabelos'), 1, '2028-01-01', '10.00', '20.00');
        $this->unidades($this->produto('Vencido', 'Cabelos'), 1, '2026-09-01', '10.00', '20.00');
        $this->unidades($this->produto('Perto', 'Cabelos'), 1, '2026-10-10', '10.00', '20.00');

        $this->assertSame(
            ['Vencido', 'Perto', 'Longe', 'Sem validade'],
            array_column($this->estoque()['produtos'], 'nome')
        );
    }

    public function testProdutoMostraASituacaoDoLoteMaisUrgente(): void
    {
        $kaiak = $this->produto('Kaiak 100ml', 'Perfumaria');
        $this->unidades($kaiak, 1, '2028-01-01', '90.00', '130.00');
        $this->unidades($kaiak, 1, '2026-09-20', '90.00', '130.00');

        $produto = $this->estoque()['produtos'][0];

        $this->assertSame(Validade::Vencido, $produto['situacao']);
        $this->assertSame(-4, $produto['dias']);
    }

    public function testResumoContaVencidasEBreve(): void
    {
        $this->unidades($this->produto('Vencido', 'Cabelos'), 2, '2026-09-01', '10.00', '20.00');
        $this->unidades($this->produto('Perto', 'Cabelos'), 3, '2026-10-10', '10.00', '20.00');
        $this->unidades($this->produto('Longe', 'Cabelos'), 4, '2028-01-01', '10.00', '20.00');

        $resumo = $this->estoque()['resumo'];

        $this->assertSame(9, $resumo['unidades']);
        $this->assertSame(2, $resumo['vencidas']);
        $this->assertSame(3, $resumo['breve']);
        $this->assertEqualsWithDelta(90.0, $resumo['lucro'], 0.001);
    }

    public function testFiltraPelaCategoriaDoProduto(): void
    {
        $this->unidades($this->produto('Kaiak', 'Perfumaria'), 1, '2027-01-01', '10.00', '20.00');
        $this->unidades($this->produto('Shampoo', 'Cabelos'), 1, '2027-01-01', '10.00', '20.00');

        $produtos = $this->estoque(['fk_categoria' => (string) Categoria::idPorNome('Cabelos')])['produtos'];

        $this->assertSame(['Shampoo'], array_column($produtos, 'nome'));
    }

    public function testFiltroDeSituacaoMantemOResumoInteiro(): void
    {
        $kaiak = $this->produto('Kaiak', 'Perfumaria');
        $this->unidades($kaiak, 2, '2026-09-01', '10.00', '20.00');
        $this->unidades($kaiak, 5, '2028-01-01', '10.00', '20.00');

        $estoque = $this->estoque(['situacao' => Validade::Vencido->value]);

        $this->assertSame(7, $estoque['resumo']['unidades']);
        $this->assertSame(2, $estoque['produtos'][0]['quantidade']);
        $this->assertCount(1, $estoque['produtos'][0]['lotes']);
    }

    /**
     * @param array<string,string> $filtros
     * @return array{resumo: array<string,int|float>, produtos: list<array<string,mixed>>}
     */
    private function estoque(array $filtros = []): array
    {
        return $this->service->estoque(Valores::deRequest($filtros, $this->service->filtros()));
    }

    private function produto(string $nome, string $categoria): Produto
    {
        return Produto::create(['nome' => $nome, 'fk_categoria' => Categoria::idPorNome($categoria)]);
    }

    private function unidades(
        Produto $produto,
        int $quantidade,
        ?string $validade,
        string $custo,
        string $venda,
        bool $vendido = false,
    ): void {
        for ($i = 0; $i < $quantidade; $i++) {
            VariacaoProduto::create([
                'fk_produto' => $produto->id,
                'fk_pedido' => $this->pedido->id,
                'fk_ciclo' => 1,
                'data_validade' => $validade,
                'mon_custo' => $custo,
                'mon_venda' => $venda,
                'vendido' => $vendido ? 1 : 0,
            ]);
        }
    }
}
