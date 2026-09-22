<?php

namespace Controla\Filtro;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Valores
{
    /** @param array<string, string|list<string>|Faixa> $valores chave do campo => valor */
    private function __construct(private readonly array $valores) {}

    public static function vazios(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, mixed> $cru normalmente o $_GET
     */
    public static function deRequest(array $cru, Definicao $definicao): self
    {
        $valores = [];

        foreach ($definicao->campos() as $campo) {
            $valor = $campo->tipo->ehFaixa()
                ? self::faixa($cru, $campo)
                : self::simples($cru, $campo);

            if ($valor !== null) {
                $valores[$campo->chave] = $valor;
            }
        }

        return new self($valores);
    }

    /** @return string|list<string>|Faixa|null null quando a usuaria nao preencheu */
    public function para(Campo|string $campo): string|array|Faixa|null
    {
        return $this->valores[$campo instanceof Campo ? $campo->chave : $campo] ?? null;
    }

    public function preenchido(Campo|string $campo): bool
    {
        return $this->para($campo) !== null;
    }

    public function vazio(): bool
    {
        return $this->valores === [];
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function comoArray(): array
    {
        $saida = [];

        foreach ($this->valores as $chave => $valor) {
            if (!$valor instanceof Faixa) {
                $saida[$chave] = $valor;
                continue;
            }

            if ($valor->temDe()) {
                $saida[$chave . Campo::SUFIXO_DE] = $valor->de;
            }

            if ($valor->temAte()) {
                $saida[$chave . Campo::SUFIXO_ATE] = $valor->ate;
            }
        }

        return $saida;
    }

    /**
     * @param array<string, mixed> $cru
     * @return string|list<string>|null
     */
    private static function simples(array $cru, Campo $campo): string|array|null
    {
        $valor = $cru[$campo->chave] ?? null;

        if (is_array($valor)) {
            if (!$campo->multiplo) {
                return null;
            }

            $lista = array_values(array_filter(
                array_map(self::texto(...), array_filter($valor, 'is_scalar')),
                static fn (string $item): bool => $item !== ''
            ));

            return $lista === [] ? null : $lista;
        }

        if (!is_scalar($valor)) {
            return null;
        }

        $texto = self::texto($valor);

        if ($texto === '') {
            return null;
        }

        return $campo->multiplo ? [$texto] : $texto;
    }

    /** @param array<string, mixed> $cru */
    private static function faixa(array $cru, Campo $campo): ?Faixa
    {
        $de = $cru[$campo->chaveDe()] ?? '';
        $ate = $cru[$campo->chaveAte()] ?? '';

        $faixa = new Faixa(
            is_scalar($de) ? self::texto($de) : '',
            is_scalar($ate) ? self::texto($ate) : '',
        );

        return $faixa->vazia() ? null : $faixa;
    }

    private static function texto(mixed $valor): string
    {
        return trim((string) $valor);
    }
}
