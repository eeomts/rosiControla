<?php

namespace Controla\Utils\Exceptions;

use RuntimeException;

/**
 * O registro existe, mas outra coisa depende dele -- excluir deixaria um
 * ponteiro para o vazio.
 *
 * Existe para o controller separar dois casos que hoje cairiam no mesmo catch:
 * "esse produto nao existe mais" e "esse produto tem unidades no estoque".
 *
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
