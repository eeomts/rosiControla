<?php

namespace Controla\Filtro;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 *
 * @template T of object
 */
interface Aplicador
{
    /**
     * @param T $consulta
     * @return T a mesma consulta, com as condicoes aplicadas
     */
    public function aplicar(Definicao $definicao, Valores $valores, object $consulta): object;
}
