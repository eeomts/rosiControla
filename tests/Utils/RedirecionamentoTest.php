<?php

namespace Controla\Tests\Utils;

use Controla\Utils\Redirecionamento;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * O enviar() nao entra aqui: ele toca header() e exit, e por isso esta sozinho.
 *
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Redirecionamento::class)]
final class RedirecionamentoTest extends TestCase
{
    public function testMontaOCabecalhoLocation(): void
    {
        $redirecionamento = Redirecionamento::para('/ciclo');

        $this->assertSame('Location: /ciclo', $redirecionamento->cabecalho());
        $this->assertSame('/ciclo', $redirecionamento->url());
    }

    public function testOPadraoEhOStatusDoPostRedirectGet(): void
    {
        $this->assertSame(303, Redirecionamento::para('/ciclo')->status());
        $this->assertSame(Redirecionamento::POS_POST, Redirecionamento::para('/ciclo')->status());
    }

    public function testAceitaOutroStatus(): void
    {
        $this->assertSame(301, Redirecionamento::para('/ciclo', 301)->status());
    }
}
