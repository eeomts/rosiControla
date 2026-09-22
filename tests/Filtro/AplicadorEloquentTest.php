<?php

namespace Controla\Tests\Filtro;

use Controla\Filtro\Campo;
use Controla\Filtro\Definicao;
use Controla\Filtro\Eloquent\AplicadorEloquent;
use Controla\Filtro\Valores;
use Controla\Models\Venda;
use Controla\Tests\Support\ControlaSchema;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AplicadorEloquent::class)]
final class AplicadorEloquentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ControlaSchema::preparar();
    }

    private static function definicao(): Definicao
    {
        return new Definicao(
            Campo::de('fk_cliente', 'Cliente'),
            Campo::de('fk_status_pagamento', 'Pagamento', multiplo: true),
            Campo::de('data_venda', 'Periodo'),
            Campo::de('mon_total', 'Valor'),
        );
    }

    /** @param array<string, mixed> $cru */
    private static function consulta(array $cru): Builder
    {
        return (new AplicadorEloquent())->aplicar(
            self::definicao(),
            Valores::deRequest($cru, self::definicao()),
            Venda::query()
        );
    }

    public function testSemFiltroNaoMexeNaConsulta(): void
    {
        $sql = self::consulta([])->toSql();

        $this->assertStringNotContainsString('fk_cliente', $sql);
    }

    public function testChaveViraIgualdade(): void
    {
        $consulta = self::consulta(['fk_cliente' => '7']);

        $this->assertStringContainsString('"fk_cliente" = ?', $consulta->toSql());
        $this->assertContains('7', $consulta->getBindings());
    }

    public function testChaveComVariosValoresViraIn(): void
    {
        $consulta = self::consulta(['fk_status_pagamento' => ['1', '2']]);

        $this->assertStringContainsString('"fk_status_pagamento" in (?, ?)', $consulta->toSql());
        # o escopo global de nao-excluido do Cubo poe um parametro proprio no fim
        $this->assertSame(['1', '2'], array_slice($consulta->getBindings(), 0, 2));
    }

    public function testDataViraFaixaComODiaInteiroNoFim(): void
    {
        $consulta = self::consulta(['data_venda_de' => '01/09/2026', 'data_venda_ate' => '22/09/2026']);

        $this->assertStringContainsString('"data_venda" >= ?', $consulta->toSql());
        $this->assertStringContainsString('"data_venda" <= ?', $consulta->toSql());
        $this->assertSame(['2026-09-01', '2026-09-22 23:59:59'], array_slice($consulta->getBindings(), 0, 2));
    }

    public function testMoedaBrasileiraViraDecimal(): void
    {
        $consulta = self::consulta(['mon_total_de' => '1.234,56']);

        $this->assertSame([1234.56], array_slice($consulta->getBindings(), 0, 1));
    }

    public function testSoUmLadoDaFaixaGeraSoUmaCondicao(): void
    {
        $consulta = self::consulta(['mon_total_ate' => '50,00']);

        $this->assertStringNotContainsString('>=', $consulta->toSql());
        $this->assertStringContainsString('"mon_total" <= ?', $consulta->toSql());
    }

    /** A Definicao e a lista de permissao: o que ela nao declara nao vira condicao. */
    public function testColunaNaoDeclaradaNaoFiltra(): void
    {
        $sql = self::consulta(['mon_desconto' => '10', 'fk_status_entrega' => '1'])->toSql();

        $this->assertStringNotContainsString('mon_desconto', $sql);
        $this->assertStringNotContainsString('fk_status_entrega', $sql);
    }

    public function testFiltraDeVerdadeNoBanco(): void
    {
        Venda::create(['fk_cliente' => 1, 'fk_status_pagamento' => 1, 'fk_status_entrega' => 1, 'data_venda' => '2026-09-10', 'mon_total' => '100.00', 'mon_desconto' => '0.00']);
        Venda::create(['fk_cliente' => 2, 'fk_status_pagamento' => 1, 'fk_status_entrega' => 1, 'data_venda' => '2026-09-20', 'mon_total' => '300.00', 'mon_desconto' => '0.00']);

        $this->assertSame(1, self::consulta(['fk_cliente' => '2'])->count());
        $this->assertSame(2, self::consulta(['mon_total_de' => '50,00'])->count());
        $this->assertSame(1, self::consulta(['data_venda_ate' => '15/09/2026'])->count());
    }
}
