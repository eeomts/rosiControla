<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Venda extends Model
{
    protected $table = 'venda';

    protected $fillable = [
        'fk_cliente', 'fk_status_pagamento', 'fk_status_entrega',
        'data_venda', 'mon_total', 'mon_desconto',
    ];

    protected $casts = [
        'fk_cliente' => 'integer',
        'fk_status_pagamento' => 'integer',
        'fk_status_entrega' => 'integer',
        'data_venda' => 'date',
        'mon_total' => 'decimal:2',
        'mon_desconto' => 'decimal:2',
    ];

    # -------------------------------------------------------------- RELACOES

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'fk_cliente', 'id');
    }

    public function statusPagamento(): BelongsTo
    {
        return $this->belongsTo(StatusPagamento::class, 'fk_status_pagamento', 'id');
    }

    public function statusEntrega(): BelongsTo
    {
        return $this->belongsTo(StatusEntrega::class, 'fk_status_entrega', 'id');
    }

    /** As unidades que sairam nesta venda. */
    public function itens(): HasMany
    {
        return $this->hasMany(VendaVariacaoRel::class, 'fk_venda', 'id');
    }

    # ---------------------------------------------------------------- SCOPES

    public function scopeDoCliente(Builder $query, int $cliente): Builder
    {
        return $query->where('fk_cliente', $cliente);
    }

    public function scopeComStatusPagamento(Builder $query, int $status): Builder
    {
        return $query->where('fk_status_pagamento', $status);
    }

    public function scopeComStatusEntrega(Builder $query, int $status): Builder
    {
        return $query->where('fk_status_entrega', $status);
    }

    public function scopeMaisRecente(Builder $query): Builder
    {
        return $query->orderByDesc('data_venda')->orderByDesc('id');
    }

    # ---------------------------------------------------------------- ESTADO

    /** Quanto a venda somaria sem nenhum desconto. */
    public function totalBruto(): string
    {
        return number_format((float) $this->mon_total + (float) $this->mon_desconto, 2, '.', '');
    }
}
