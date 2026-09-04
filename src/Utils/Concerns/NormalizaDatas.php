<?php

namespace Controla\Utils\Concerns;

use Cubo\Tools\Date;
use InvalidArgumentException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
trait NormalizaDatas
{
    /**
     * Traduz as datas do formulario (BR ou ISO) para Y-m-d.
     * @param array<string,mixed> $dados
     * @param list<string> $campos
     * @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    private function normalizarDatas(array $dados, array $campos): array
    {
        $erros = [];

        foreach ($campos as $campo) {
            $valor = trim((string) ($dados[$campo] ?? ''));

            if ($valor === '') {
                unset($dados[$campo]);
                continue;
            }

            try {
                $dados[$campo] = Date::convert($valor, 'eng');
            } catch (InvalidArgumentException) {
                unset($dados[$campo]);
                $erros[$campo] = 'Data invalida.';
            }
        }

        return [$dados, $erros];
    }
}
