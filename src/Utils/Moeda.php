<?php

namespace Controla\Utils;

/**
 * Valor monetario do jeito que ela le.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Moeda
{
    public static function brl(int|float|string|null $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}
