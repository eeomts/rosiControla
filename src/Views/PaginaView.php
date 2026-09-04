<?php

namespace Controla\Views;

use Cubo\View\View;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class PaginaView extends View
{
    public const PARAM_CONTEUDO = 'conteudo';

    public function __construct(string $template)
    {
        $this->setTemplate($template);
    }

    public function render(): void
    {
        ob_start();
        try {
            parent::render();
        } finally {
            $this->addParam(self::PARAM_CONTEUDO, (string) ob_get_clean());
        }
    }
}
