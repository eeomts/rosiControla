<?php

namespace Controla\Utils\Exceptions;

use Controla\Models\Usuario;
use RuntimeException;

/**
 * Email e senha conferem, mas o codigo de confirmacao nunca foi digitado.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class EmailNaoConfirmadoException extends RuntimeException
{
    public function __construct(public readonly Usuario $usuario)
    {
        parent::__construct("O email {$usuario->email} ainda nao foi confirmado.");
    }
}
