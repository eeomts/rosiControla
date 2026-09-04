<?php

namespace Controla\Tests\Services;

use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\RegistroEmUsoException;
use Controla\Models\Genero;
use Controla\Models\Produto;
use Controla\Models\VariacaoProduto;
use Controla\Services\ProdutoService;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class ProdutoServiceTest extends TestCase
{
    private ProdutoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->service = new ProdutoService();
    }

    public function testSalvaUmProdutoNovo(): void
    {
        $genero = Genero::create(['nome' => 'Feminino']);

        $produto = $this->service->salvar(null, [
            'nome' => '  Batom Vermelho Intenso  ',
            'codigo_produto' => '82345',
            'fk_genero' => (string) $genero->id,
        ]);

        $this->assertTrue($produto->exists);
        $this->assertSame('Batom Vermelho Intenso', $produto->nome);
        $this->assertSame($genero->id, $produto->fk_genero);
    }

    public function testGeneroVazioViraNuloEmVezDeZero(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Sabonete', 'fk_genero' => '']);

        $this->assertNull($produto->fk_genero);
    }

    public function testCodigoVazioViraNulo(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Sabonete', 'codigo_produto' => '']);

        $this->assertNull($produto->codigo_produto);
    }

    public function testDoisProdutosSemCodigoConvivem(): void
    {
        $this->service->salvar(null, ['nome' => 'Sabonete']);
        $this->service->salvar(null, ['nome' => 'Hidratante']);

        $this->assertCount(2, Produto::getRecords());
    }

    public function testCadastroRapidoSoPrecisaDoNome(): void
    {
        $produto = $this->service->cadastroRapido('Perfume Essencial');

        $this->assertTrue($produto->exists);
        $this->assertSame('Perfume Essencial', $produto->nome);
    }

    public function testAtualizaOProdutoExistente(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom Vermelho']);

        $atualizado = $this->service->salvar($produto->id, ['nome' => 'Batom Vermelho Matte']);

        $this->assertSame($produto->id, $atualizado->id);
        $this->assertSame('Batom Vermelho Matte', $atualizado->nome);
        $this->assertCount(1, Produto::getRecords());
    }

    public function testRecusaNomeVazio(): void
    {
        $erros = $this->errosAoSalvar(null, ['nome' => '   ']);

        $this->assertArrayHasKey('nome', $erros);
    }

    public function testRecusaGeneroInexistente(): void
    {
        $erros = $this->errosAoSalvar(null, ['nome' => 'Batom', 'fk_genero' => 999]);

        $this->assertArrayHasKey('fk_genero', $erros);
    }

    public function testRecusaCodigoRepetido(): void
    {
        $this->service->salvar(null, ['nome' => 'Batom Vermelho', 'codigo_produto' => '82345']);

        $erros = $this->errosAoSalvar(null, ['nome' => 'Outro Batom', 'codigo_produto' => '82345']);

        $this->assertArrayHasKey('codigo_produto', $erros);
    }

    public function testManterOProprioCodigoNaEdicaoNaoAcusaRepeticao(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom', 'codigo_produto' => '82345']);

        $atualizado = $this->service->salvar($produto->id, [
            'nome' => 'Batom Matte',
            'codigo_produto' => '82345',
        ]);

        $this->assertSame($produto->id, $atualizado->id);
    }

    public function testProdutoExcluidoNaoBloqueiaOCodigo(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom', 'codigo_produto' => '82345']);
        $produto->delete();

        $novo = $this->service->salvar(null, ['nome' => 'Batom Novo', 'codigo_produto' => '82345']);

        $this->assertTrue($novo->exists);
    }

    public function testReclamaDeIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->salvar(999, ['nome' => 'Batom']);
    }

    # ------------------------------------------------- LISTAR E EXCLUIR

    public function testListaEmOrdemAlfabetica(): void
    {
        $this->service->salvar(null, ['nome' => 'Sabonete']);
        $this->service->salvar(null, ['nome' => 'Batom']);
        $this->service->salvar(null, ['nome' => 'Creme']);

        $this->assertSame(
            ['Batom', 'Creme', 'Sabonete'],
            $this->service->listar()->pluck('nome')->all()
        );
    }

    public function testFiltraPeloNomeOuPeloCodigo(): void
    {
        $this->service->salvar(null, ['nome' => 'Batom Una', 'codigo_produto' => 'B-100']);
        $this->service->salvar(null, ['nome' => 'Creme Tododia', 'codigo_produto' => 'C-200']);

        $this->assertCount(1, $this->service->listar('batom'));
        $this->assertCount(1, $this->service->listar('C-200'));
        $this->assertCount(2, $this->service->listar());
    }

    public function testEncontraPeloId(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom']);

        $this->assertSame($produto->id, $this->service->encontrar($produto->id)->id);
    }

    public function testEncontrarSemIdReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->encontrar(null);
    }

    public function testExcluiSemApagarALinha(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom']);

        $excluido = $this->service->excluir($produto->id);

        $this->assertTrue($excluido->trashed());
        $this->assertCount(0, $this->service->listar());
        $this->assertCount(1, Produto::withTrashed()->get());
    }

    /** Sem isto, a unidade ficaria apontando para um produto invisivel. */
    public function testNaoExcluiProdutoComUnidadeCadastrada(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom']);
        $this->criarUnidade($produto);

        $this->expectException(RegistroEmUsoException::class);

        $this->service->excluir($produto->id);
    }

    public function testProdutoComUnidadeContinuaNaLista(): void
    {
        $produto = $this->service->salvar(null, ['nome' => 'Batom']);
        $this->criarUnidade($produto);

        try {
            $this->service->excluir($produto->id);
        } catch (RegistroEmUsoException) {
            // o que importa e o produto ter sobrevivido
        }

        $this->assertCount(1, $this->service->listar());
    }

    public function testExcluirIdInexistenteReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->excluir(999);
    }

    private function criarUnidade(Produto $produto): VariacaoProduto
    {
        return VariacaoProduto::create([
            'fk_produto' => $produto->id,
            'fk_pedido' => 1,
            'fk_ciclo' => 1,
            'mon_custo' => '10.00',
            'mon_venda' => '30.00',
            'vendido' => 0,
        ]);
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,string>
     */
    private function errosAoSalvar(?int $id, array $dados): array
    {
        try {
            $this->service->salvar($id, $dados);
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('Esperava DadosInvalidosException.');
    }
}
