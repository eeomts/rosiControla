<?php

namespace Controla\Tests\Services;

use Controla\Services\CicloService;
use Controla\Models\Ciclo;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class CicloServiceTest extends TestCase
{
    private CicloService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->service = new CicloService();
    }

    public function testSalvaUmCicloNovo(): void
    {
        $ciclo = $this->service->salvar(null, [
            'nome' => 'Ciclo 11',
            'num_ciclo' => 11,
            'num_ano' => 2026,
            'data_inicio' => '01/07/2026',
            'data_termino' => '21/07/2026',
        ]);

        $this->assertTrue($ciclo->exists);
        $this->assertSame('2026-07-01', $ciclo->data_inicio->format('Y-m-d'));
        $this->assertSame('2026-07-21', $ciclo->data_termino->format('Y-m-d'));
    }

    public function testAceitaDataNoFormatoIsoTambem(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados(['data_inicio' => '2026-07-01']));

        $this->assertSame('2026-07-01', $ciclo->data_inicio->format('Y-m-d'));
    }

    public function testSemNomeOCicloSeChamaPeloNumero(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados(['nome' => '']));

        $this->assertSame('Ciclo 11', $ciclo->nome);
    }

    public function testAtualizaOCicloExistente(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados());

        $atualizado = $this->service->salvar($ciclo->id, $this->dados(['nome' => 'Ciclo 11 revisado']));

        $this->assertSame($ciclo->id, $atualizado->id);
        $this->assertSame('Ciclo 11 revisado', $atualizado->nome);
        $this->assertCount(1, Ciclo::getRecords());
    }

    public function testRecusaCicloRepetidoNoMesmoAno(): void
    {
        $this->service->salvar(null, $this->dados());

        $erros = $this->errosAoSalvar(null, $this->dados());

        $this->assertArrayHasKey('num_ciclo', $erros);
    }

    public function testMesmoNumeroEmAnoDiferenteEhPermitido(): void
    {
        $this->service->salvar(null, $this->dados());
        $outro = $this->service->salvar(null, $this->dados(['num_ano' => 2027]));

        $this->assertTrue($outro->exists);
        $this->assertCount(2, Ciclo::getRecords());
    }

    public function testEditarOProprioCicloNaoAcusaDuplicidade(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados());

        $atualizado = $this->service->salvar($ciclo->id, $this->dados(['nome' => 'Outro nome']));

        $this->assertSame($ciclo->id, $atualizado->id);
    }

    public function testCicloExcluidoNaoBloqueiaUmNovoIgual(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados());
        $ciclo->delete();

        $novo = $this->service->salvar(null, $this->dados());

        $this->assertTrue($novo->exists);
        $this->assertNotSame($ciclo->id, $novo->id);
    }

    public function testRecusaDataInvalida(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['data_inicio' => '31/02/2026']));

        $this->assertArrayHasKey('data_inicio', $erros);
    }

    public function testRecusaTerminoAntesDoInicio(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados([
            'data_inicio' => '21/07/2026',
            'data_termino' => '01/07/2026',
        ]));

        $this->assertArrayHasKey('data_termino', $erros);
    }

    public function testRecusaNumeroEAnoForaDaFaixa(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['num_ciclo' => 0, 'num_ano' => 1500]));

        $this->assertArrayHasKey('num_ciclo', $erros);
        $this->assertArrayHasKey('num_ano', $erros);
    }

    public function testAcendeTodosOsCamposErradosDeUmaVez(): void
    {
        $erros = $this->errosAoSalvar(null, [
            'num_ciclo' => 0,
            'num_ano' => 1500,
            'data_inicio' => '99/99/9999',
            'data_termino' => '',
        ]);

        $this->assertEqualsCanonicalizing(
            ['num_ciclo', 'num_ano', 'data_inicio', 'data_termino'],
            array_keys($erros)
        );
    }

    public function testNaoGravaNadaQuandoAValidacaoFalha(): void
    {
        $this->errosAoSalvar(null, $this->dados(['num_ciclo' => 0]));

        $this->assertCount(0, Ciclo::getRecords());
    }

    public function testReclamaDeIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->salvar(999, $this->dados());
    }

    public function testListaDoMaisNovoParaOMaisAntigo(): void
    {
        $this->service->salvar(null, $this->dados(['num_ciclo' => 11, 'num_ano' => 2026]));
        $this->service->salvar(null, $this->dados(['num_ciclo' => 12, 'num_ano' => 2026]));
        $this->service->salvar(null, $this->dados(['num_ciclo' => 1, 'num_ano' => 2027]));

        $lista = $this->service->listar();

        $this->assertSame(
            [[2027, 1], [2026, 12], [2026, 11]],
            $lista->map(fn(Ciclo $c) => [$c->num_ano, $c->num_ciclo])->all()
        );
    }

    public function testEncontraPeloId(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados());

        $this->assertSame($ciclo->id, $this->service->encontrar($ciclo->id)->id);
    }

    public function testEncontrarSemIdReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->encontrar(null);
    }

    public function testExcluiSemApagarALinha(): void
    {
        $ciclo = $this->service->salvar(null, $this->dados());

        $excluido = $this->service->excluir($ciclo->id);

        $this->assertTrue($excluido->trashed());
        $this->assertCount(0, $this->service->listar());
        $this->assertCount(1, Ciclo::withTrashed()->get());
    }

    public function testExcluirIdInexistenteReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->excluir(999);
    }

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function dados(array $troca = []): array
    {
        return $troca + [
            'nome' => 'Ciclo 11',
            'num_ciclo' => 11,
            'num_ano' => 2026,
            'data_inicio' => '01/07/2026',
            'data_termino' => '21/07/2026',
        ];
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
