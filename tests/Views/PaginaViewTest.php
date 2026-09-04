<?php

namespace Controla\Tests\Views;

use Controla\Tests\Support\Views\LayoutFake;
use Controla\Views\PaginaView;
use Cubo\Config;
use Cubo\Exceptions\TemplateNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

#[CoversClass(PaginaView::class)]
final class PaginaViewTest extends TestCase
{
    protected function setUp(): void
    {
        // o resolveTemplate() consulta o Config (singleton); troca-se por uma
        // instancia limpa apontando para os templates fixture
        $config = (new ReflectionClass(Config::class))->newInstanceWithoutConstructor();
        // no Cubo 2.1 a raiz virou LISTA ordenada ([app] templates aceita varias)
        $config->setConfig(\Cubo\View\View::TEMPLATE_ROOTS, [__DIR__ . '/../Support/templates/']);

        $this->setConfigInstance($config);
    }

    protected function tearDown(): void
    {
        $this->setConfigInstance(null);
    }

    private function setConfigInstance(?Config $config): void
    {
        (new ReflectionProperty(Config::class, '_instance'))->setValue(null, $config);
    }

    public function testRenderNaoEscreveNaSaidaEGuardaOHtmlNoParam(): void
    {
        $pagina = new PaginaView('pagina_fake.php');
        $pagina->addParam('nome', 'Ciclos');

        ob_start();
        $pagina->render();
        $saidaDireta = ob_get_clean();

        $this->assertSame('', $saidaDireta, 'a pagina nao pode cuspir html antes do layout');
        $this->assertSame('<p>tela: Ciclos</p>', $pagina->getParam(PaginaView::PARAM_CONTEUDO));
    }

    public function testTemplateQueNaoExisteNaoDeixaBufferAberto(): void
    {
        $pagina = new PaginaView('nao_existe.php');
        $nivelAntes = ob_get_level();

        try {
            $pagina->render();
            $this->fail('esperava TemplateNotFoundException');
        } catch (TemplateNotFoundException) {
            $this->assertSame($nivelAntes, ob_get_level());
        }
    }

    public function testLayoutImprimeOConteudoDaPaginaFilha(): void
    {
        $layout = new LayoutFake();
        $pagina = new PaginaView('pagina_fake.php');

        $layout->addParam('nome', 'Ciclos');
        $layout->addChild($pagina);

        ob_start();
        $layout->render();
        $saida = ob_get_clean();

        $this->assertSame('<main><p>tela: Ciclos</p></main>', $saida);
    }

    public function testPaginaSemConteudoDeixaOParamVazio(): void
    {
        $pagina = new PaginaView('vazio_fake.php');

        $pagina->render();

        $this->assertSame('', $pagina->getParam(PaginaView::PARAM_CONTEUDO));
    }
}
