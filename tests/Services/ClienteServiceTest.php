<?php

namespace Controla\Tests\Services;

use Controla\Models\Cliente;
use Controla\Services\ClienteService;
use Controla\Tests\Support\ControlaSchema;
use Controla\Utils\Exceptions\DadosInvalidosException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class ClienteServiceTest extends TestCase
{
    private ClienteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->service = new ClienteService();
    }

    public function testSalvaUmaClienteNova(): void
    {
        $cliente = $this->service->salvar(null, $this->dados());

        $this->assertTrue($cliente->exists);
        $this->assertSame('Maria Aparecida', $cliente->nome);
    }

    public function testGuardaSoOsDigitosDoTelefone(): void
    {
        $cliente = $this->service->salvar(null, $this->dados(['telefone' => '(11) 99999-8888']));

        $this->assertSame('11999998888', $cliente->telefone);
    }

    public function testTelefoneVazioViraNulo(): void
    {
        $cliente = $this->service->salvar(null, $this->dados(['telefone' => '']));

        $this->assertNull($cliente->telefone);
    }

    public function testTiraOsEspacosDoNome(): void
    {
        $cliente = $this->service->salvar(null, $this->dados(['nome' => '  Maria Aparecida  ']));

        $this->assertSame('Maria Aparecida', $cliente->nome);
    }

    public function testAtualizaAClienteExistente(): void
    {
        $cliente = $this->service->salvar(null, $this->dados());

        $atualizada = $this->service->salvar($cliente->id, $this->dados(['nome' => 'Maria A. Souza']));

        $this->assertSame($cliente->id, $atualizada->id);
        $this->assertSame('Maria A. Souza', $atualizada->nome);
        $this->assertCount(1, Cliente::getRecords());
    }

    public function testNomeRepetidoEhPermitido(): void
    {
        $this->service->salvar(null, $this->dados());
        $outra = $this->service->salvar(null, $this->dados());

        $this->assertTrue($outra->exists);
        $this->assertCount(2, Cliente::getRecords());
    }

    public function testTelefoneRepetidoEhPermitido(): void
    {
        $this->service->salvar(null, $this->dados(['nome' => 'Maria']));
        $outra = $this->service->salvar(null, $this->dados(['nome' => 'Filha da Maria']));

        $this->assertTrue($outra->exists);
        $this->assertCount(2, Cliente::getRecords());
    }

    public function testExigeONome(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['nome' => '   ']));

        $this->assertArrayHasKey('nome', $erros);
    }

    public function testRecusaTelefoneCurtoDemais(): void
    {
        $erros = $this->errosAoSalvar(null, $this->dados(['telefone' => '9999-8888']));

        $this->assertArrayHasKey('telefone', $erros);
    }

    public function testAceitaFixoComDdd(): void
    {
        $cliente = $this->service->salvar(null, $this->dados(['telefone' => '(11) 3333-2222']));

        $this->assertSame('1133332222', $cliente->telefone);
    }

    public function testAcendeNomeETelefoneDeUmaVez(): void
    {
        $erros = $this->errosAoSalvar(null, ['nome' => '', 'telefone' => '123']);

        $this->assertEqualsCanonicalizing(['nome', 'telefone'], array_keys($erros));
    }

    public function testNaoGravaNadaQuandoAValidacaoFalha(): void
    {
        $this->errosAoSalvar(null, $this->dados(['nome' => '']));

        $this->assertCount(0, Cliente::getRecords());
    }

    public function testCadastroRapidoSoComONome(): void
    {
        $cliente = $this->service->cadastroRapido('Dona Cida');

        $this->assertTrue($cliente->exists);
        $this->assertNull($cliente->telefone);
    }

    public function testReclamaDeIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->salvar(999, $this->dados());
    }

    public function testListaEmOrdemAlfabetica(): void
    {
        $this->service->salvar(null, $this->dados(['nome' => 'Zuleica']));
        $this->service->salvar(null, $this->dados(['nome' => 'Ana']));
        $this->service->salvar(null, $this->dados(['nome' => 'Marcia']));

        $lista = $this->service->listar();

        $this->assertSame(['Ana', 'Marcia', 'Zuleica'], $lista->pluck('nome')->all());
    }

    public function testFiltraPeloNome(): void
    {
        $this->service->salvar(null, $this->dados(['nome' => 'Ana Paula']));
        $this->service->salvar(null, $this->dados(['nome' => 'Zuleica']));

        $this->assertCount(1, $this->service->listar('ana'));
    }

    public function testFiltraPeloTelefoneMesmoComMascaraDigitada(): void
    {
        $this->service->salvar(null, $this->dados(['telefone' => '(11) 99999-8888']));
        $this->service->salvar(null, $this->dados(['nome' => 'Outra', 'telefone' => '(21) 3333-2222']));

        $achadas = $this->service->listar('(11) 99999-8888');

        $this->assertCount(1, $achadas);
        $this->assertSame('Maria Aparecida', $achadas->first()->nome);
    }

    public function testEncontraPeloId(): void
    {
        $cliente = $this->service->salvar(null, $this->dados());

        $this->assertSame($cliente->id, $this->service->encontrar($cliente->id)->id);
    }

    public function testEncontrarSemIdReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->encontrar(null);
    }

    public function testExcluiSemApagarALinha(): void
    {
        $cliente = $this->service->salvar(null, $this->dados());

        $excluida = $this->service->excluir($cliente->id);

        $this->assertTrue($excluida->trashed());
        $this->assertCount(0, $this->service->listar());
        $this->assertCount(1, Cliente::withTrashed()->get());
    }

    public function testExcluirIdInexistenteReclama(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->excluir(999);
    }

    public function testDevolveOTelefoneComMascara(): void
    {
        $celular = $this->service->salvar(null, $this->dados(['telefone' => '11999998888']));
        $fixo = $this->service->salvar(null, $this->dados(['telefone' => '1133332222']));

        $this->assertSame('(11) 99999-8888', $celular->telefoneFormatado());
        $this->assertSame('(11) 3333-2222', $fixo->telefoneFormatado());
    }

    public function testSemTelefoneAMascaraSaiVazia(): void
    {
        $cliente = $this->service->salvar(null, $this->dados(['telefone' => '']));

        $this->assertSame('', $cliente->telefoneFormatado());
    }

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function dados(array $troca = []): array
    {
        return $troca + [
            'nome' => 'Maria Aparecida',
            'telefone' => '11999998888',
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
