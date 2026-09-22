<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class ErroController extends FeatureController
{
    public function index(): void
    {
        $this->pagina('Pagina nao encontrada', 'erro/404.php', [
            'caminho' => $this->request->texto('caminho'),
        ]);
    }

    public function sessao(): void
    {
        $this->pagina('A pagina expirou', 'erro/419.php');
    }

    public function metodo(): void
    {
        $this->pagina('Essa pagina nao abre direto', 'erro/405.php');
    }
}
