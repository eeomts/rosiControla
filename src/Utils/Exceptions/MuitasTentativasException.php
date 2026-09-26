<?php

namespace Controla\Utils\Exceptions;

use RuntimeException;

/**
 * Tem que esperar antes de tentar de novo: senha errada demais ou codigo pedido cedo demais.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class MuitasTentativasException extends RuntimeException
{
    public function __construct(public readonly int $segundos)
    {
        parent::__construct("Aguarde {$segundos} segundo(s) para tentar de novo.");
    }

    /** 45 -> "45 segundos", 90 -> "2 minutos": arredonda para cima, nunca promete antes da hora. */
    public function espera(): string
    {
        if ($this->segundos < 60) {
            return $this->segundos === 1 ? '1 segundo' : "{$this->segundos} segundos";
        }

        $minutos = (int) ceil($this->segundos / 60);

        return $minutos === 1 ? '1 minuto' : "{$minutos} minutos";
    }
}
