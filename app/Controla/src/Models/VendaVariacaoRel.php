<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class VendaVariacaoRel extends Model
{
    protected $table = 'venda_variacao_rel';

    protected $fillable = [
        'fk_venda', 'fk_variacao_produto', 'mon_venda', 'mon_desconto',
    ];

    protected $casts = [
        'fk_venda' => 'integer',
        'fk_variacao_produto' => 'integer',
        'mon_venda' => 'decimal:2',
        'mon_desconto' => 'decimal:2',
    ];

    # -------------------------------------------------------------- RELACOES

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'fk_venda', 'id');
    }

    public function variacao(): BelongsTo
    {
        return $this->belongsTo(VariacaoProduto::class, 'fk_variacao_produto', 'id');
    }

    # ---------------------------------------------------------------- SCOPES

    public function scopeDaVenda(Builder $query, int $venda): Builder
    {
        return $query->where('fk_venda', $venda);
    }

    # ---------------------------------------------------------------- ESTADO

    /** O que a cliente pagou por este item. */
    public function totalLiquido(): string
    {
        return number_format((float) $this->mon_venda - (float) $this->mon_desconto, 2, '.', '');
    }
}
