<?php

namespace Controla\Filtro\Eloquent;

use Controla\Filtro\Aplicador;
use Controla\Filtro\Campo;
use Controla\Filtro\Definicao;
use Controla\Filtro\Faixa;
use Controla\Filtro\Tipo;
use Controla\Filtro\Valores;
use Cubo\Tools\Date;
use Cubo\Tools\Number;
use Illuminate\Database\Eloquent\Builder;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 *
 * @implements Aplicador<Builder>
 */
final class AplicadorEloquent implements Aplicador
{
    private const FIM_DO_DIA = ' 23:59:59';

    /**
     * @param Builder $consulta
     * @return Builder
     */
    public function aplicar(Definicao $definicao, Valores $valores, object $consulta): object
    {
        foreach ($definicao->campos() as $campo) {
            $valor = $valores->para($campo);

            if ($valor === null) {
                continue;
            }

            match ($campo->tipo) {
                Tipo::Chave => $this->chave($consulta, $campo, $valor),
                Tipo::Data => $this->data($consulta, $campo, $valor),
                Tipo::Moeda => $this->moeda($consulta, $campo, $valor),
                Tipo::Numero => $consulta->where($campo->coluna, '=', $valor),
                Tipo::Texto => $consulta->where($campo->coluna, 'LIKE', '%' . $valor . '%'),
            };
        }

        return $consulta;
    }

    /** @param string|list<string>|Faixa $valor */
    private function chave(Builder $consulta, Campo $campo, string|array|Faixa $valor): void
    {
        if (is_array($valor)) {
            $consulta->whereIn($campo->coluna, $valor);

            return;
        }

        $consulta->where($campo->coluna, '=', $valor);
    }

    /** @param string|list<string>|Faixa $valor */
    private function data(Builder $consulta, Campo $campo, string|array|Faixa $valor): void
    {
        if (!$valor instanceof Faixa) {
            return;
        }

        if ($valor->temDe()) {
            $consulta->where($campo->coluna, '>=', Date::formataData($valor->de, 'Y-m-d'));
        }

        if ($valor->temAte()) {
            $consulta->where($campo->coluna, '<=', Date::formataData($valor->ate, 'Y-m-d') . self::FIM_DO_DIA);
        }
    }

    /** @param string|list<string>|Faixa $valor */
    private function moeda(Builder $consulta, Campo $campo, string|array|Faixa $valor): void
    {
        if (!$valor instanceof Faixa) {
            return;
        }

        if ($valor->temDe()) {
            $consulta->where($campo->coluna, '>=', Number::parseMoney($valor->de));
        }

        if ($valor->temAte()) {
            $consulta->where($campo->coluna, '<=', Number::parseMoney($valor->ate));
        }
    }
}
