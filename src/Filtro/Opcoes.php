<?php

namespace Controla\Filtro;

use Closure;

/**

 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Opcoes implements FonteDeOpcoes
{
    /** @var array<int|string, string>|null lista ja resolvida */
    private ?array $resolvidas;

    /** @var Closure(): array<int|string, string>|null */
    private ?Closure $consulta;

    /** @param array<int|string, string>|null $resolvidas */
    private function __construct(?array $resolvidas, ?Closure $consulta)
    {
        $this->resolvidas = $resolvidas;
        $this->consulta = $consulta;
    }

    /** @param array<int|string, string> $lista valor => rotulo */
    public static function deLista(array $lista): self
    {
        return new self($lista, null);
    }

    /** @param Closure(): array<int|string, string> $consulta so roda quando alguem pedir */
    public static function deChamada(Closure $consulta): self
    {
        return new self(null, $consulta);
    }

    /** @return array<int|string, string> */
    public function opcoes(): array
    {
        if ($this->resolvidas === null) {
            $this->resolvidas = ($this->consulta)();
        }

        return $this->resolvidas;
    }
}
