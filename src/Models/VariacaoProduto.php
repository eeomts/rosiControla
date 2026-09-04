<?php

namespace Controla\Models;

use Controla\Models\Ciclo;
use Controla\Models\Produto;
use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UMA LINHA = UMA UNIDADE fisica do produto, com o preco daquele pedido.
 * Tres batons iguais no mesmo pedido sao tres linhas.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class VariacaoProduto extends Model
{
    protected $table = 'variacao_produto';

    /** Colunas que tornam duas unidades identicas -- a chave de agrupamento na tela. */
    private const COLUNAS_IDENTIDADE = [
        'fk_produto', 'fk_pedido', 'fk_ciclo', 'mon_custo', 'mon_venda', 'data_validade',
    ];

    protected $fillable = [
        'fk_produto', 'fk_pedido', 'fk_ciclo', 'data_validade', 'mon_custo', 'mon_venda', 'vendido',
    ];

    protected $casts = [
        'fk_produto' => 'integer',
        'fk_pedido' => 'integer',
        'fk_ciclo' => 'integer',
        'data_validade' => 'date',
        'mon_custo' => 'decimal:2',
        'mon_venda' => 'decimal:2',
        'vendido' => 'boolean',
    ];

    # -------------------------------------------------------------- RELACOES

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'fk_produto', 'id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'fk_pedido', 'id');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'fk_ciclo', 'id');
    }

    # ---------------------------------------------------------------- SCOPES

    /** Ainda em estoque. */
    public function scopeDisponivel(Builder $query): Builder
    {
        return $query->where('vendido', 0);
    }

    public function scopeDoProduto(Builder $query, int $produto): Builder
    {
        return $query->where('fk_produto', $produto);
    }

    public function scopeDoPedido(Builder $query, int $pedido): Builder
    {
        return $query->where('fk_pedido', $pedido);
    }

    public function scopeDoCiclo(Builder $query, int $ciclo): Builder
    {
        return $query->where('fk_ciclo', $ciclo);
    }

    # ------------------------------------------------------------- ESTOQUE

    /**
     * Unidades disponiveis de um produto, agrupadas por dados identicos.
     *
     * E o que a tela de venda mostra: ela busca o PRODUTO e escolhe de qual
     * ciclo/preco vender, com a quantidade de cada grupo.
     *
     * @return Collection<int,static>
     */
    public static function disponiveisAgrupadas(int $produto): Collection
    {
        $colunas = implode(', ', self::COLUNAS_IDENTIDADE);

        return static::query()
            ->disponivel()
            ->doProduto($produto)
            ->selectRaw("{$colunas}, COUNT(*) as quantidade, MIN(id) as id")
            ->groupBy(self::COLUNAS_IDENTIDADE)
            ->get();
    }

    /**
     * Ultima unidade cadastrada de um produto, para repetir custo e preco no
     * proximo pedido. A validade nao se repete: e sempre digitada.
     */
    public static function ultimaDoProduto(int $produto): ?static
    {
        return static::query()
            ->doProduto($produto)
            ->orderByDesc('id')
            ->first();
    }
}
