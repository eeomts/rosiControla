<?php

namespace Controla\Filtro;

/**
 * Os dois lados de um filtro de data ou de dinheiro; qualquer um pode faltar.
 *
 * Guarda o texto como a tela mandou ("1.234,56", "22/09/2026"): quem traduz
 * para o banco e o Aplicador, porque so ele sabe o que o banco espera.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Faixa
{
    public function __construct(
        public readonly string $de = '',
        public readonly string $ate = '',
    ) {}

    public function temDe(): bool
    {
        return $this->de !== '';
    }

    public function temAte(): bool
    {
        return $this->ate !== '';
    }

    public function vazia(): bool
    {
        return !$this->temDe() && !$this->temAte();
    }
}
