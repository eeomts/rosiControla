<?php

namespace Controla\Filtro;

use InvalidArgumentException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Campo
{
    public const SUFIXO_DE = '_de';
    public const SUFIXO_ATE = '_ate';

    private function __construct(
        public readonly string $chave,
        public readonly string $coluna,
        public readonly string $rotulo,
        public readonly Tipo $tipo,
        public readonly ?FonteDeOpcoes $opcoes,
        /** Chave: aceita mais de um valor de uma vez (vira IN). */
        public readonly bool $multiplo,
    ) {}

    /**
     * @param string|null $chave quando null, sai da coluna ('venda.fk_cliente' -> 'fk_cliente')
     * @param Tipo|null $tipo quando null, sai do prefixo da coluna
     */
    public static function de(
        string $coluna,
        string $rotulo,
        ?Tipo $tipo = null,
        ?FonteDeOpcoes $opcoes = null,
        ?string $chave = null,
        bool $multiplo = false,
    ): self {
        if (trim($coluna) === '') {
            throw new InvalidArgumentException('Campo sem coluna.');
        }

        $tipo ??= Tipo::deColuna($coluna);

        if ($tipo !== Tipo::Chave && $multiplo) {
            throw new InvalidArgumentException("Campo '{$coluna}': so campo Chave aceita varios valores.");
        }

        return new self(
            $chave ?? self::chaveDeColuna($coluna),
            $coluna,
            $rotulo,
            $tipo,
            $opcoes,
            $multiplo,
        );
    }

    /** A chave na URL do lado "de" de uma faixa. */
    public function chaveDe(): string
    {
        return $this->chave . self::SUFIXO_DE;
    }

    /** A chave na URL do lado "ate" de uma faixa. */
    public function chaveAte(): string
    {
        return $this->chave . self::SUFIXO_ATE;
    }

    /** @return array<int|string, string> vazio quando o campo nao tem lista */
    public function listaDeOpcoes(): array
    {
        return $this->opcoes?->opcoes() ?? [];
    }

    private static function chaveDeColuna(string $coluna): string
    {
        return str_contains($coluna, '.') ? explode('.', $coluna)[1] : $coluna;
    }
}
