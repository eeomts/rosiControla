<?php

namespace Controla\Filtro;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
interface FonteDeOpcoes
{
    /** @return array<int|string, string> valor => rotulo, na ordem de exibicao */
    public function opcoes(): array;
}
