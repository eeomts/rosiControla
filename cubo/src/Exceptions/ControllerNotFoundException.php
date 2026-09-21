<?php

namespace Cubo\Exceptions;

/**
 * Lançada quando a requisição aponta para um controlador que não existe.
 * @package Cubo
 * @author Mateus - github.com/eeomts
 */
final class ControllerNotFoundException extends CuboException
{
    
    public static function for(string $controller): self
    {
        return new self(
            "Controlador não encontrado: {$controller}",
            self::CODE_CONTROLLER_MISSING,
        );
    }

    
    public static function naoInstanciavel(string $controller): self
    {
        return new self(
            "Controlador não instanciável: {$controller} (classe abstrata ou construtor não público)",
            self::CODE_CONTROLLER_MISSING,
        );
    }
}
