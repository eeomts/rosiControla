<?php

namespace Controla\Utils\Exceptions;

use Controla\Models\Usuario;
use RuntimeException;

/**
 * Senha certa, mas a conta levou senha errada demais: so entra com o codigo do email.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class CodigoNecessarioException extends RuntimeException
{
    public function __construct(public readonly Usuario $usuario)
    {
        parent::__construct("A conta {$usuario->email} precisa do codigo por email para entrar.");
    }
}
