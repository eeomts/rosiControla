<?php

namespace Controla\Tests\Services;

use Controla\Models\Usuario;
use Controla\Services\AutenticacaoService;
use Controla\Services\LimiteDeTentativas;
use Controla\Tests\Support\ControlaSchema;
use Controla\Tests\Support\RemetenteFake;
use Controla\Utils\Exceptions\CodigoNecessarioException;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\EmailNaoConfirmadoException;
use Controla\Utils\Exceptions\MuitasTentativasException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(AutenticacaoService::class)]
final class AutenticacaoServiceTest extends TestCase
{
    private const IP = '203.0.113.7';

    private AutenticacaoService $service;

    private RemetenteFake $remetente;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();

        $this->remetente = new RemetenteFake();
        $this->service = new AutenticacaoService($this->remetente);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    # --------------------------------------------------------------- CADASTRO

    public function testCadastroNasceSemEmailConfirmado(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->assertTrue($usuario->exists);
        $this->assertFalse($usuario->email_confirmado);
    }

    public function testGuardaASenhaComoHash(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->assertNotSame('senha-da-rosi', $usuario->senha);
        $this->assertTrue(password_verify('senha-da-rosi', (string) $usuario->senha));
    }

    public function testEmailViraMinusculoESemEspaco(): void
    {
        $usuario = $this->service->cadastrar($this->dados(['email' => '  Rosi@Exemplo.COM ']));

        $this->assertSame('rosi@exemplo.com', $usuario->email);
    }

    public function testCadastroFechaDepoisDaPrimeiraConta(): void
    {
        $this->assertTrue($this->service->cadastroAberto());

        $this->service->cadastrar($this->dados());

        $this->assertFalse($this->service->cadastroAberto());

        $this->expectException(RuntimeException::class);
        $this->service->cadastrar($this->dados(['email' => 'outra@exemplo.com']));
    }

    public function testRecusaSenhaCurta(): void
    {
        $erros = $this->errosDoCadastro(['senha' => 'curta', 'senha_confirmacao' => 'curta']);

        $this->assertArrayHasKey('senha', $erros);
    }

    public function testRecusaConfirmacaoDiferente(): void
    {
        $erros = $this->errosDoCadastro(['senha_confirmacao' => 'outra-senha-qualquer']);

        $this->assertArrayHasKey('senha_confirmacao', $erros);
    }

    public function testRecusaEmailInvalidoENomeVazio(): void
    {
        $erros = $this->errosDoCadastro(['nome' => '  ', 'email' => 'rosi-sem-arroba']);

        $this->assertArrayHasKey('nome', $erros);
        $this->assertArrayHasKey('email', $erros);
    }

    # ----------------------------------------------------------------- CODIGO

    public function testMandaOCodigoParaOEmailDaConta(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->service->enviarCodigo($usuario);

        $this->assertCount(1, $this->remetente->enviados);
        $this->assertSame('rosi@exemplo.com', $this->remetente->enviados[0]['para']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->remetente->ultimoCodigo());
    }

    public function testGuardaOCodigoComoHash(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->service->enviarCodigo($usuario);

        $this->assertNotSame($this->remetente->ultimoCodigo(), $usuario->fresh()->codigo);
    }

    public function testCodigoCertoConfirmaEApagaOCodigo(): void
    {
        $usuario = $this->cadastradaComCodigo();

        $confirmada = $this->service->confirmar((int) $usuario->id, $this->remetente->ultimoCodigo());

        $this->assertTrue($confirmada->email_confirmado);
        $this->assertNull($confirmada->fresh()->codigo);
    }

    public function testAceitaOCodigoComEspacos(): void
    {
        $usuario = $this->cadastradaComCodigo();

        $digitado = implode(' ', str_split($this->remetente->ultimoCodigo(), 3));

        $this->assertTrue($this->service->confirmar((int) $usuario->id, $digitado)->email_confirmado);
    }

    public function testCodigoErradoContaTentativa(): void
    {
        $usuario = $this->cadastradaComCodigo();

        $erro = $this->errosDaConfirmacao($usuario, $this->codigoErrado());

        $this->assertStringContainsString('Restam 4', $erro);
        $this->assertSame(1, $usuario->fresh()->num_tentativas);
    }

    public function testTravaDepoisDeCincoErros(): void
    {
        $usuario = $this->cadastradaComCodigo();

        for ($i = 0; $i < AutenticacaoService::MAX_TENTATIVAS; $i++) {
            $this->errosDaConfirmacao($usuario, $this->codigoErrado());
        }

        // nem o codigo certo passa depois da trava
        $erro = $this->errosDaConfirmacao($usuario, $this->remetente->ultimoCodigo());

        $this->assertStringContainsString('muitas tentativas', $erro);
        $this->assertFalse($usuario->fresh()->email_confirmado);
    }

    public function testCodigoVenceDepoisDeQuinzeMinutos(): void
    {
        $usuario = $this->cadastradaComCodigo();

        Carbon::setTestNow(Carbon::now()->addMinutes(AutenticacaoService::MINUTOS_CODIGO + 1));

        $erro = $this->errosDaConfirmacao($usuario, $this->remetente->ultimoCodigo());

        $this->assertStringContainsString('venceu', $erro);
    }

    public function testCodigoNovoInvalidaOAnteriorEZeraTentativas(): void
    {
        $usuario = $this->cadastradaComCodigo();
        $antigo = $this->remetente->ultimoCodigo();

        $this->errosDaConfirmacao($usuario, $this->codigoErrado());

        // fresh(): a tentativa foi gravada por outra instancia, e o Eloquent so salva o que mudou
        do {
            // o reenvio tem espera: anda o relogio para fora dela
            Carbon::setTestNow(Carbon::now()->addHours(2));
            $this->service->enviarCodigo($usuario->fresh());
        } while ($this->remetente->ultimoCodigo() === $antigo);

        $this->assertSame(0, $usuario->fresh()->num_tentativas);
        $this->assertStringContainsString('errado', $this->errosDaConfirmacao($usuario, $antigo));
    }

    # ------------------------------------------------------------------ LOGIN

    public function testEntraComEmailESenha(): void
    {
        $this->confirmada();

        $usuario = $this->service->entrar('ROSI@exemplo.com ', 'senha-da-rosi', self::IP);

        $this->assertSame('Rosi', $usuario->nome);
    }

    public function testMesmaRecusaParaSenhaErradaEEmailQueNaoExiste(): void
    {
        $this->confirmada();

        $senhaErrada = $this->errosDoLogin('rosi@exemplo.com', 'senha-errada');
        $semConta = $this->errosDoLogin('ninguem@exemplo.com', 'senha-da-rosi');

        $this->assertSame($senhaErrada, $semConta);
    }

    public function testCamposVaziosPedemOQueFalta(): void
    {
        $erros = $this->errosDoLogin('', '');

        $this->assertArrayHasKey('email', $erros);
        $this->assertArrayHasKey('senha', $erros);
    }

    public function testSemConfirmarOEmailNaoEntra(): void
    {
        $this->service->cadastrar($this->dados());

        try {
            $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP);
            $this->fail('Entrou sem confirmar o email.');
        } catch (EmailNaoConfirmadoException $e) {
            $this->assertSame('rosi@exemplo.com', $e->usuario->email);
        }
    }

    public function testSenhaErradaNaoRevelaQueFaltaConfirmar(): void
    {
        $this->service->cadastrar($this->dados());

        $this->assertArrayHasKey('email', $this->errosDoLogin('rosi@exemplo.com', 'senha-errada'));
    }

    # ------------------------------------------------------ LIMITE DO LOGIN

    public function testCincoSenhasErradasBloqueiamOIpAteComASenhaCerta(): void
    {
        $this->confirmada();
        $this->errarSenha(5);

        try {
            $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP);
            $this->fail('Entrou com o ip bloqueado.');
        } catch (MuitasTentativasException $e) {
            $this->assertSame(60, $e->segundos);
        }
    }

    public function testBloqueioNaoPegaOutroIp(): void
    {
        $this->confirmada();
        $this->errarSenha(5);

        $usuario = $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', '198.51.100.20');

        $this->assertSame('Rosi', $usuario->nome);
    }

    public function testLoginCertoZeraAsFalhasDoIp(): void
    {
        $this->confirmada();
        $this->errarSenha(4);

        $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP);
        $this->errarSenha(4);

        $this->assertSame('Rosi', $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP)->nome);
    }

    public function testDezFalhasNaContaPedemOCodigoMesmoComASenhaCerta(): void
    {
        $this->confirmada();

        for ($i = 0; $i < LimiteDeTentativas::FALHAS_DA_CONTA; $i++) {
            $this->errosDoLogin('rosi@exemplo.com', 'senha-errada', "198.51.100.{$i}");
        }

        $this->expectException(CodigoNecessarioException::class);

        $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP);
    }

    public function testConfirmarOCodigoLiberaAConta(): void
    {
        $usuario = $this->confirmada();

        for ($i = 0; $i < LimiteDeTentativas::FALHAS_DA_CONTA; $i++) {
            $this->errosDoLogin('rosi@exemplo.com', 'senha-errada', "198.51.100.{$i}");
        }

        $this->service->enviarCodigo($usuario->fresh());
        $this->service->confirmar((int) $usuario->id, $this->remetente->ultimoCodigo());

        $this->assertSame('Rosi', $this->service->entrar('rosi@exemplo.com', 'senha-da-rosi', self::IP)->nome);
    }

    public function testContaJaConfirmadaNaoPulaOCodigo(): void
    {
        $usuario = $this->confirmada();

        $this->service->enviarCodigo($usuario->fresh());

        $this->assertStringContainsString('errado', $this->errosDaConfirmacao($usuario, $this->codigoErrado()));
    }

    public function testReenvioEsperaCadaVezMais(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        foreach (AutenticacaoService::ESPERAS_DO_CODIGO as $minutos) {
            $this->service->enviarCodigo($usuario->fresh());

            $this->assertSame($minutos * 60, $this->segundosParaReenviar($usuario));

            Carbon::setTestNow(Carbon::now()->addMinutes($minutos));
        }

        $this->service->enviarCodigo($usuario->fresh());

        $this->assertSame(60 * 60, $this->segundosParaReenviar($usuario));
    }

    public function testReenvioRecomecaDepoisDeUmDia(): void
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->service->enviarCodigo($usuario->fresh());
        Carbon::setTestNow(Carbon::now()->addMinutes(1));
        $this->service->enviarCodigo($usuario->fresh());

        Carbon::setTestNow(Carbon::now()->addHours(25));
        $this->service->enviarCodigo($usuario->fresh());

        $this->assertSame(60, $this->segundosParaReenviar($usuario));
    }

    # ---------------------------------------------------------------- APOIO

    private function errarSenha(int $vezes): void
    {
        for ($i = 0; $i < $vezes; $i++) {
            $this->errosDoLogin('rosi@exemplo.com', 'senha-errada');
        }
    }

    private function segundosParaReenviar(Usuario $usuario): int
    {
        try {
            $this->service->enviarCodigo($usuario->fresh());
        } catch (MuitasTentativasException $e) {
            return $e->segundos;
        }

        $this->fail('O reenvio saiu sem espera.');
    }

    /**
     * @param array<string,string> $troca
     * @return array<string,string>
     */
    private function dados(array $troca = []): array
    {
        return array_merge([
            'nome' => 'Rosi',
            'email' => 'rosi@exemplo.com',
            'senha' => 'senha-da-rosi',
            'senha_confirmacao' => 'senha-da-rosi',
        ], $troca);
    }

    private function cadastradaComCodigo(): Usuario
    {
        $usuario = $this->service->cadastrar($this->dados());

        $this->service->enviarCodigo($usuario);

        return $usuario;
    }

    private function confirmada(): Usuario
    {
        $usuario = $this->cadastradaComCodigo();

        return $this->service->confirmar((int) $usuario->id, $this->remetente->ultimoCodigo());
    }

    private function codigoErrado(): string
    {
        return str_pad((string) (((int) $this->remetente->ultimoCodigo() + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string,string> $troca
     * @return array<string,string>
     */
    private function errosDoCadastro(array $troca): array
    {
        try {
            $this->service->cadastrar($this->dados($troca));
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('O cadastro passou.');
    }

    private function errosDaConfirmacao(Usuario $usuario, string $codigo): string
    {
        try {
            $this->service->confirmar((int) $usuario->id, $codigo);
        } catch (DadosInvalidosException $e) {
            return $e->erros()['codigo'] ?? '';
        }

        $this->fail('A confirmacao passou.');
    }

    /** @return array<string,string> */
    private function errosDoLogin(string $email, string $senha, string $ip = self::IP): array
    {
        try {
            $this->service->entrar($email, $senha, $ip);
        } catch (DadosInvalidosException $e) {
            return $e->erros();
        }

        $this->fail('O login passou.');
    }
}
