<?php

namespace Controla\Views;

use Cubo\View\View;

/**
 * Um template sozinho, sem o layout em volta: o JS troca esse pedaco na tela.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class FragmentoView extends View
{
    public function __construct(
        string $template,
        private readonly int $status = 200
    ) {
        $this->setTemplate($template);
    }

    public function render(): void
    {
        http_response_code($this->status);

        parent::render();
    }
}
