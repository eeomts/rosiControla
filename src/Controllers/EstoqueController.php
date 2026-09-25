<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Filtro\Valores;
use Controla\Services\EstoqueService;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class EstoqueController extends FeatureController
{
    private EstoqueService $service;

    protected function iniciar(): void
    {
        $this->service = new EstoqueService();
    }

    public function index(): void
    {
        $definicao = $this->service->filtros();
        $filtros = Valores::deRequest($this->request->query(), $definicao);
        $estoque = $this->service->estoque($filtros);

        $this->pagina('Estoque', 'estoque/lista.php', [
            'filtro_definicao' => $definicao,
            'filtro_valores' => $filtros,
            'resumo' => $estoque['resumo'],
            'produtos' => $estoque['produtos'],
            'abertos' => !$filtros->vazio(),
        ]);
    }
}
