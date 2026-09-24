<?php

namespace Controla\Utils\Exceptions;

use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class RegistroEmUsoException extends RuntimeException
{
    public static function porque(string $motivo): self
    {
        return new self($motivo);
    }
}
