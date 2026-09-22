<?php

namespace Controla\Filtro;

use InvalidArgumentException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Definicao
{
    /** @var array<string, Campo> chave => campo */
    private readonly array $campos;

    public function __construct(Campo ...$campos)
    {
        $porChave = [];

        foreach ($campos as $campo) {
            if (isset($porChave[$campo->chave])) {
                throw new InvalidArgumentException("Campo repetido na definicao: '{$campo->chave}'.");
            }

            $porChave[$campo->chave] = $campo;
        }

        $this->campos = $porChave;
    }

    /** @return list<Campo> na ordem em que foram declarados */
    public function campos(): array
    {
        return array_values($this->campos);
    }

    public function campo(string $chave): ?Campo
    {
        return $this->campos[$chave] ?? null;
    }

    public function vazia(): bool
    {
        return $this->campos === [];
    }
}
