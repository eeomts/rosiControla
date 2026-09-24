<?php

namespace Controla\Models;

use Controla\Models\Auxiliar;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Genero extends Auxiliar
{
    protected $table = 'genero_aux';

    /** Feminino -> F, Masculino -> M, Unissex -> U: a coluna Sexo da grade de escolha. */
    public function sigla(): string
    {
        return mb_strtoupper(mb_substr(trim((string) $this->nome), 0, 1));
    }
}
