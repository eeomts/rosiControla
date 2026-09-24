<?php

namespace Controla\Tests\Email;

use Controla\Email\Modelo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Modelo::class)]
final class ModeloTest extends TestCase
{
    private Modelo $modelo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelo = new Modelo();
    }

    public function testHtmlTrazOCodigoEOPrazo(): void
    {
        $html = $this->modelo->html('codigo', $this->dados());

        $this->assertStringContainsString('042917', $html);
        $this->assertStringContainsString('Vale por 15 minutos', $html);
    }

    public function testNomeSaiEscapadoNoHtml(): void
    {
        $html = $this->modelo->html('codigo', $this->dados(['nome' => '<b>Rosi</b>']));

        $this->assertStringNotContainsString('<b>Rosi</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Rosi&lt;/b&gt;', $html);
    }

    public function testTextoNaoTemHtml(): void
    {
        $texto = $this->modelo->texto('codigo', $this->dados());

        $this->assertStringContainsString('042917', $texto);
        $this->assertStringNotContainsString('<', $texto);
    }

    public function testTemplateQueNaoExisteAvisa(): void
    {
        $this->expectException(RuntimeException::class);

        $this->modelo->html('nao-existe', []);
    }

    public function testErroNoTemplateNaoDeixaBufferAberto(): void
    {
        $nivel = ob_get_level();

        try {
            // codigo que nao e string quebra o escape no meio do template
            $this->modelo->html('codigo', ['codigo' => new \stdClass()]);
        } catch (\Throwable) {
        }

        $this->assertSame($nivel, ob_get_level());
    }

    /**
     * @param array<string,mixed> $troca
     * @return array<string,mixed>
     */
    private function dados(array $troca = []): array
    {
        return array_merge(['nome' => 'Rosi', 'codigo' => '042917', 'minutos' => 15], $troca);
    }
}
