<?php

namespace Controla\Filtro;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
enum Tipo
{
    /** fk_*: igualdade, ou IN quando vem mais de um. */
    case Chave;

    /** data_*: faixa, com "de" e "ate". */
    case Data;

    /** mon_*: faixa, com "de" e "ate". */
    case Moeda;

    /** num_*: igualdade. */
    case Numero;

    /** O resto: LIKE %valor%. */
    case Texto;

    public static function deColuna(string $coluna): self
    {
        $nome = str_contains($coluna, '.') ? explode('.', $coluna)[1] : $coluna;

        return match (true) {
            str_starts_with($nome, 'fk_') => self::Chave,
            str_starts_with($nome, 'data_') => self::Data,
            str_starts_with($nome, 'mon_') => self::Moeda,
            str_starts_with($nome, 'num_') => self::Numero,
            default => self::Texto,
        };
    }

    public function ehFaixa(): bool
    {
        return $this === self::Data || $this === self::Moeda;
    }
}
