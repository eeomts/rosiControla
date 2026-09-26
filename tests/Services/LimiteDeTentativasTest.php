<?php

namespace Controla\Tests\Services;

use Controla\Models\TentativaLogin;
use Controla\Services\LimiteDeTentativas;
use Controla\Tests\Support\ControlaSchema;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(LimiteDeTentativas::class)]
final class LimiteDeTentativasTest extends TestCase
{
    private const IP = '203.0.113.7';

    private const EMAIL = 'rosi@exemplo.com';

    private LimiteDeTentativas $limite;

    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00'));

        $this->limite = new LimiteDeTentativas();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testQuatroFalhasAindaDeixamTentar(): void
    {
        $this->falhar(4);

        $this->assertSame(0, $this->limite->segundosBloqueado(self::IP));
    }

    public function testCadaDegrauBloqueiaMaisTempo(): void
    {
        foreach ([1, 5, 20, 60, 60] as $minutos) {
            $this->falhar(LimiteDeTentativas::FALHAS_POR_DEGRAU);

            $this->assertSame($minutos * 60, $this->limite->segundosBloqueado(self::IP));

            Carbon::setTestNow(Carbon::now()->addMinutes($minutos));

            $this->assertSame(0, $this->limite->segundosBloqueado(self::IP));
        }
    }

    public function testDepoisDoBloqueioAsProximasQuatroPassam(): void
    {
        $this->falhar(5);
        Carbon::setTestNow(Carbon::now()->addMinutes(1));

        $this->falhar(4);

        $this->assertSame(0, $this->limite->segundosBloqueado(self::IP));
    }

    public function testFalhaDeUmDiaAtrasNaoConta(): void
    {
        $this->falhar(4);
        Carbon::setTestNow(Carbon::now()->addHours(25));

        $this->falhar(1);

        $this->assertSame(0, $this->limite->segundosBloqueado(self::IP));
        $this->assertSame(1, TentativaLogin::withTrashed()->count());
    }

    public function testContaPedeCodigoComDezFalhasNaUltimaHora(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->limite->registrarFalha("198.51.100.{$i}", self::EMAIL);
        }

        $this->assertFalse($this->limite->contaExigeCodigo(self::EMAIL));

        $this->limite->registrarFalha('198.51.100.99', self::EMAIL);

        $this->assertTrue($this->limite->contaExigeCodigo(self::EMAIL));

        Carbon::setTestNow(Carbon::now()->addMinutes(LimiteDeTentativas::MINUTOS_DA_CONTA + 1));

        $this->assertFalse($this->limite->contaExigeCodigo(self::EMAIL));
    }

    public function testLiberarApagaDeVerdade(): void
    {
        $this->falhar(5);

        $this->limite->liberarIp(self::IP);

        $this->assertSame(0, $this->limite->segundosBloqueado(self::IP));
        $this->assertSame(0, TentativaLogin::withTrashed()->count());
    }

    private function falhar(int $vezes): void
    {
        for ($i = 0; $i < $vezes; $i++) {
            $this->limite->registrarFalha(self::IP, self::EMAIL);
        }
    }
}
