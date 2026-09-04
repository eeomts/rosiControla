<?php

namespace Controla\Models;

use Controla\Models\Ciclo;
use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido feito a Natura, cadastrado quando chega com a nota fiscal.
 * Manda nos produtos e e mandado pelo ciclo.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Pedido extends Model
{
    protected $table = 'pedido';

    protected $fillable = [
        'fk_ciclo', 'nome', 'data_pedido', 'mon_total', 'mon_lucro_estimado', 'mon_lucro_real',
    ];

    protected $casts = [
        'fk_ciclo' => 'integer',
        'data_pedido' => 'date',
        'mon_total' => 'decimal:2',
        'mon_lucro_estimado' => 'decimal:2',
        'mon_lucro_real' => 'decimal:2',
    ];

    # -------------------------------------------------------------- RELACOES

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'fk_ciclo', 'id');
    }

    /** As unidades que entraram por este pedido. */
    public function variacoes(): HasMany
    {
        return $this->hasMany(VariacaoProduto::class, 'fk_pedido', 'id');
    }

    # ---------------------------------------------------------------- SCOPES

    public function scopeDoCiclo(Builder $query, int $ciclo): Builder
    {
        return $query->where('fk_ciclo', $ciclo);
    }

    public function scopeMaisRecente(Builder $query): Builder
    {
        return $query->orderByDesc('data_pedido')->orderByDesc('id');
    }
}
