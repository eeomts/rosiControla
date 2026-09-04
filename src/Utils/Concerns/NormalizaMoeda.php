<?php

namespace Controla\Utils\Concerns;

use Cubo\Tools\Number;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
trait NormalizaMoeda
{
    private const CASAS_DECIMAIS = 2;

    private function somaMoeda(float $valor): string
    {
        return number_format($valor, self::CASAS_DECIMAIS, '.', '');
    }

    /**
     * Traduz valores em BR ("1.234,56") ou com ponto decimal ("29.90") para string
     * decimal.
     * @param array<string,mixed> $dados
     * @param list<string> $campos
     * @return array<string,mixed>
     */
    private function normalizarMoeda(array $dados, array $campos): array
    {
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }

            $valor = preg_replace('/[^\d,.\-]/', '', (string) $dados[$campo]) ?? '';

            $dados[$campo] = $valor === ''
                ? null
                : number_format(Number::parseMoney($valor), self::CASAS_DECIMAIS, '.', '');
        }

        return $dados;
    }
}
