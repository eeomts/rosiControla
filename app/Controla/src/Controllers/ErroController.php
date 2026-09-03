<?php

namespace Controla\Controllers;

/**
 * A tela que responde quando a URL nao aponta para feature nenhuma.
 *
 * Quem decide QUANDO ela aparece e o public/index.php, que captura a
 * ControllerNotFoundException do Cubo. O framework so sinaliza com a excecao
 * tipada; traduzir isso em 404 e em uma pagina e trabalho da app, que e quem
 * conhece o layout.
 *
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
}
