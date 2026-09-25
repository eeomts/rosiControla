<?php

namespace Controla\Tests\Types;

use Controla\Types\Validade;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Validade::class)]
final class ValidadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-24 15:30:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('datas')]
    public function testClassificaPelaDistanciaAteHoje(?string $data, Validade $esperada, ?int $dias): void
    {
        $validade = $data === null ? null : Carbon::parse($data);

        $this->assertSame($esperada, Validade::da($validade));
        $this->assertSame($dias, Validade::diasAte($validade));
    }

    /** @return array<string,array{?string, Validade, ?int}> */
    public static function datas(): array
    {
        return [
            'sem data' => [null, Validade::Sem, null],
            'venceu ontem' => ['2026-09-23', Validade::Vencido, -1],
            'vence hoje' => ['2026-09-24', Validade::Breve, 0],
            'ultimo dia da janela' => ['2026-11-08', Validade::Breve, 45],
            'primeiro dia fora da janela' => ['2026-11-09', Validade::Ok, 46],
        ];
    }

    public function testTextoDoSelo(): void
    {
        $this->assertSame('venceu ontem', Validade::Vencido->texto(-1));
        $this->assertSame('vencido ha 10 dias', Validade::Vencido->texto(-10));
        $this->assertSame('vence hoje', Validade::Breve->texto(0));
        $this->assertSame('vence amanha', Validade::Breve->texto(1));
        $this->assertSame('vence em 12 dias', Validade::Breve->texto(12));
        $this->assertSame('sem validade', Validade::Sem->texto(null));
    }

    public function testOpcoesDoFiltro(): void
    {
        $this->assertSame(['vencido', 'breve', 'ok', 'sem'], array_keys(Validade::paraSelect()));
    }
}
