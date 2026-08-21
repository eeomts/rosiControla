<?php

namespace Controla\Utils;

use Cubo\Routing\Route;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Request
{
    private const METODO_PADRAO = 'GET';

    /**
     * @param array<string,mixed> $corpo Campos do POST
     * @param array<string,string> $rota  chave/valor URL
     */
    public function __construct(
        private readonly array $corpo = [],
        private readonly array $rota = [],
        private readonly string $metodo = self::METODO_PADRAO
    ) {}

    
    public static function daGlobal(?Route $rota = null): self
    {
        return new self(
            $_POST,
            $rota?->params ?? [],
            (string) ($_SERVER['REQUEST_METHOD'] ?? self::METODO_PADRAO)
        );
    }

    public function ehPost(): bool
    {
        return strtoupper($this->metodo) === 'POST';
    }

    /**
     * @return array<string,mixed>
     */
    public function corpo(): array
    {
        return $this->corpo;
    }

    public function texto(string $campo, string $default = ''): string
    {
        $valor = $this->corpo[$campo] ?? $this->rota[$campo] ?? $default;

        return is_scalar($valor) ? (string) $valor : $default;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function linhas(string $campo): array
    {
        $valor = $this->corpo[$campo] ?? null;

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_array'));
    }

    public function inteiroOuNulo(string $campo): ?int
    {
        $valor = $this->corpo[$campo] ?? $this->rota[$campo] ?? null;

        if (!is_scalar($valor) || !is_numeric($valor)) {
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0 ? $inteiro : null;
    }
}
