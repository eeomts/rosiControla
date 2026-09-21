<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Services\DashboardService;
use Cubo\Tools\Date;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class DashboardController extends FeatureController
{
    private DashboardService $service;

    protected function iniciar(): void
    {
        $this->service = new DashboardService();
    }

    public function index(): void
    {
        $hoje = Date::now('Y-m-d');
        $ciclo = $this->service->cicloVigente($hoje);

        $this->pagina('Inicio', 'dashboard/index.php', [
            'ciclo' => $ciclo['ciclo'],
            'vigente' => $ciclo['vigente'],
            'dias' => $ciclo['dias'],
            'comeca' => $ciclo['comeca'],
            'receber' => $this->service->aReceber(),
            'estoque' => $this->service->estoqueELucro($ciclo['vigente'] ? $ciclo['ciclo'] : null),
            'entregas' => $this->service->entregasPendentes(),
            'vendas' => $this->service->ultimasVendas(),
        ]);
    }
}
