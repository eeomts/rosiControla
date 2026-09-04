<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * A cliente da revenda: nao pertence a ciclo nem a pedido, so a venda aponta
 * para ela.
 *
 * O telefone e gravado so com digitos (11999998888); a mascara e coisa da tela,
 * na entrada, e do telefoneFormatado(), na saida.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Cliente extends Model
{
    protected $table = 'cliente';

    protected $fillable = [
        'nome', 'telefone',
    ];

    # ---------------------------------------------------------------- SCOPES

    /** Busca por nome ou telefone, que e como ela procura na tela de venda. */
    public function scopeBusca(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);

        if ($termo === '') {
            return $query;
        }

        // digitar "(11) 9999" tem que achar o telefone gravado sem mascara
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        return $query->where(function (Builder $q) use ($termo, $digitos): void {
            $q->where('nome', 'like', "%{$termo}%");

            if ($digitos !== '') {
                $q->orWhere('telefone', 'like', "%{$digitos}%");
            }
        });
    }

    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('nome');
    }

    # ---------------------------------------------------------------- ESTADO

    /** O telefone de volta com mascara; formato desconhecido sai como esta. */
    public function telefoneFormatado(): string
    {
        $digitos = (string) $this->telefone;

        return match (strlen($digitos)) {
            11 => sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 5), substr($digitos, 7)),
            10 => sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 4), substr($digitos, 6)),
            default => $digitos,
        };
    }
}
