<?php

namespace Controla\Email;

use RuntimeException;
use Throwable;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class EmailNaoEnviadoException extends RuntimeException
{
    public static function configuracaoIncompleta(): self
    {
        return new self('A secao [email] do config.ini esta incompleta.');
    }

    public static function porCausaDe(Throwable $causa): self
    {
        return new self('O servidor de email recusou o envio: ' . $causa->getMessage(), 0, $causa);
    }
}
