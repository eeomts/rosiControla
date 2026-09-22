<?php

namespace Controla\Views;

use Controla\Utils\Flash;
use Cubo\View\View;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class DefaultView extends View
{
    public function __construct()
    {
        $this->setTemplate('layout.php');
    }

    protected function _setDefaultParams(): void
    {
        $this->addParam('sistema', 'Controla');
        $this->addParam('titulo', $this->getParam('titulo', 'Controla'));
        $this->addParam('conteudo', $this->getParam('conteudo', ''));

        // lmebrar dessa merda pra alterar quando o login existir
        $this->addParam('usuario', 'Rosi');
        $this->addParam('usuario_papel', 'Consultora Natura');

        
        $recado = Flash::daGlobal()->consumir();

        $this->addParam('flash', $recado['mensagem'] ?? null);
        $this->addParam('flash_tipo', $recado['tipo'] ?? '');
    }
}
