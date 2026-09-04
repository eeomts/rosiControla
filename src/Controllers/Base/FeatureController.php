<?php

namespace Controla\Controllers\Base;

use Controla\Utils\Entrada;
use Controla\Utils\Flash;
use Controla\Views\PaginaView;
use Cubo\Controller;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
abstract class FeatureController extends Controller
{
    /** @var list<string> Campos que voltam para o form quando a validacao falha. */
    protected const CAMPOS = [];

    protected Entrada $request;
    protected Flash $flash;

    final public function initialize(): void
    {
        $this->request = new Entrada($this->_request, $this->_route);
        $this->flash = Flash::daGlobal();

        $this->iniciar();
    }

    /** Onde a feature cria o seu service. */
    protected function iniciar(): void
    {
    }

    /**
     * @param array<string,mixed> $params
     */
    protected function pagina(string $titulo, string $template, array $params = []): void
    {
        $this->_view->addParam('titulo', $titulo);

        foreach ($params as $chave => $valor) {
            $this->_view->addParam($chave, $valor);
        }

        $this->_view->addChild(new PaginaView($template));
    }

    /** @return array<string,string> */
    protected function valoresDigitados(): array
    {
        $valores = [];

        foreach (static::CAMPOS as $campo) {
            $valores[$campo] = $this->request->texto($campo);
        }

        return $valores;
    }

    /** @return array<string,string> */
    protected function valoresVazios(): array
    {
        return array_fill_keys(static::CAMPOS, '');
    }
}
