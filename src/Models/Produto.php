<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A BASE do produto: catalogo estavel, igual em qualquer ciclo ou pedido.
 * Preco, custo e validade nao moram aqui -- sao da variacao.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Produto extends Model
{
    protected $table = 'produto';

    protected $fillable = [
        'nome', 'codigo_produto', 'fk_genero',
    ];

    protected $casts = [
        'fk_genero' => 'integer',
    ];

    # -------------------------------------------------------------- RELACOES

    public function genero(): BelongsTo
    {
        return $this->belongsTo(Genero::class, 'fk_genero', 'id');
    }

    # ---------------------------------------------------------------- SCOPES

    /** Busca por nome ou codigo, que e como ela procura na tela de venda. */
    public function scopeBusca(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);

        if ($termo === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termo): void {
            $q->where('nome', 'like', "%{$termo}%")
                ->orWhere('codigo_produto', 'like', "%{$termo}%");
        });
    }

    public function scopeDoGenero(Builder $query, int $genero): Builder
    {
        return $query->where('fk_genero', $genero);
    }

    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('nome');
    }
}
