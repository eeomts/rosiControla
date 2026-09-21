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

    /** O caminho existe, mas nao sob o verbo pedido (405). */
    public function metodo(): void
    {
        $this->pagina('Essa pagina nao abre direto', 'erro/405.php');
    }
}
