<?php

namespace Controla\Views;

use Controla\Utils\Flash;
use Controla\Utils\Sessao;
use Cubo\View\View;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class DefaultView extends View
{
    public const LAYOUT_SISTEMA = 'layout.php';

    /** Login, cadastro e confirmacao: sem menu lateral e sem cabecalho. */
    public const LAYOUT_ACESSO = 'acesso.php';

    public function __construct(string $layout = self::LAYOUT_SISTEMA)
    {
        $this->setTemplate($layout);
    }

    protected function _setDefaultParams(): void
    {
        $this->addParam('sistema', 'Controla');
        $this->addParam('titulo', $this->getParam('titulo', 'Controla'));
        $this->addParam('conteudo', $this->getParam('conteudo', ''));

        // lmebrar dessa merda pra alterar quando o login existir
        // $this->addParam('usuario', 'Rosi');
        $this->addParam('usuario', Sessao::daGlobal()->nome());
        // PROVISORIO: a tabela usuario nao tem papel
        $this->addParam('usuario_papel', 'Consultora Natura');


        $recado = Flash::daGlobal()->consumir();

        $this->addParam('flash', $recado['mensagem'] ?? null);
        $this->addParam('flash_tipo', $recado['tipo'] ?? '');
    }
}
