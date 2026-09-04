<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Cubo\Tools\Date;
use Illuminate\Database\Eloquent\Builder;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Ciclo extends Model
{
    protected $table = 'ciclo';

    protected $fillable = [
        'nome', 'num_ciclo', 'num_ano', 'data_inicio', 'data_termino',
    ];

    protected $casts = [
        'num_ciclo' => 'integer',
        'num_ano' => 'integer',
        'data_inicio' => 'date',
        'data_termino' => 'date',
    ];

    # --------------------------------------------------------------- SCOPES

    /** Ciclos de um ano. */
    public function scopeDoAno(Builder $query, int $ano): Builder
    {
        return $query->where('num_ano', $ano);
    }

    /** Ciclo que contem a data informada (Y-m-d); sem data, hoje. */
    public function scopeVigenteEm(Builder $query, ?string $data = null): Builder
    {
        $data ??= Date::now('Y-m-d');

        return $query->where('data_inicio', '<=', $data)
            ->where('data_termino', '>=', $data);
    }

    /** Do ciclo mais novo para o mais antigo. */
    public function scopeMaisRecente(Builder $query): Builder
    {
        return $query->orderByDesc('num_ano')->orderByDesc('num_ciclo');
    }

    # ---------------------------------------------------------------- ESTADO

    public function estaVigente(?string $data = null): bool
    {
        $data ??= Date::now('Y-m-d');

        return $this->data_inicio?->format('Y-m-d') <= $data
            && $this->data_termino?->format('Y-m-d') >= $data;
    }
}
