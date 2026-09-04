<?php

namespace Controla\Tests\Models;

use Controla\Models\Ciclo;
use Controla\Tests\Support\ControlaSchema;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class CicloTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();
    }

    public function testDoAnoSoTrazOsCiclosDaqueleAno(): void
    {
        $this->criarCiclo(11, 2026);
        $this->criarCiclo(12, 2026);
        $this->criarCiclo(1, 2027);

        $this->assertCount(2, Ciclo::query()->doAno(2026)->get());
        $this->assertCount(1, Ciclo::query()->doAno(2027)->get());
    }

    public function testVigenteEmAchaOCicloQueContemADataInformada(): void
    {
        $this->criarCiclo(11, 2026, '2026-07-01', '2026-07-21');
        $this->criarCiclo(12, 2026, '2026-07-22', '2026-08-11');

        $vigente = Ciclo::query()->vigenteEm('2026-07-25')->first();

        $this->assertNotNull($vigente);
        $this->assertSame(12, $vigente->num_ciclo);
    }

    public function testVigenteEmNaoAchaNadaForaDasJanelas(): void
    {
        $this->criarCiclo(11, 2026, '2026-07-01', '2026-07-21');

        $this->assertNull(Ciclo::query()->vigenteEm('2026-09-01')->first());
    }

    public function testMaisRecenteOrdenaPeloAnoEDepoisPeloNumero(): void
    {
        $this->criarCiclo(3, 2026);
        $this->criarCiclo(1, 2027);
        $this->criarCiclo(15, 2026);

        $nomes = Ciclo::query()->maisRecente()->get()
            ->map(fn(Ciclo $ciclo) => "{$ciclo->num_ciclo}/{$ciclo->num_ano}")
            ->all();

        $this->assertSame(['1/2027', '15/2026', '3/2026'], $nomes);
    }

    public function testEstaVigenteComparaAsDatasDoProprioCiclo(): void
    {
        $ciclo = $this->criarCiclo(11, 2026, '2026-07-01', '2026-07-21');

        $this->assertTrue($ciclo->estaVigente('2026-07-01'));
        $this->assertTrue($ciclo->estaVigente('2026-07-21'));
        $this->assertFalse($ciclo->estaVigente('2026-06-30'));
        $this->assertFalse($ciclo->estaVigente('2026-07-22'));
    }

    public function testCicloExcluidoSaiDasConsultas(): void
    {
        $ciclo = $this->criarCiclo(11, 2026);

        $ciclo->delete();

        $this->assertCount(0, Ciclo::getRecords());
        $this->assertCount(1, Ciclo::withTrashed()->get());
        $this->assertTrue($ciclo->trashed());
    }

    private function criarCiclo(
        int $numero,
        int $ano,
        string $inicio = '2026-01-01',
        string $termino = '2026-01-21'
    ): Ciclo {
        return Ciclo::create([
            'nome' => "Ciclo {$numero}",
            'num_ciclo' => $numero,
            'num_ano' => $ano,
            'data_inicio' => $inicio,
            'data_termino' => $termino,
        ]);
    }
}
