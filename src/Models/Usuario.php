<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quem entra no sistema. Na pratica existe uma so.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class Usuario extends Model
{
    protected $table = 'usuario';

    protected $fillable = [
        'nome', 'email',
    ];

    /** senha e codigo sao hash, mas nem o hash sai num toArray()/json */
    protected $hidden = [
        'senha', 'codigo',
    ];

    protected $casts = [
        'email_confirmado' => 'boolean',
        'data_codigo_expira' => 'datetime',
        'num_tentativas' => 'integer',
        'num_envios_codigo' => 'integer',
        'data_ultimo_envio' => 'datetime',
    ];

    # ---------------------------------------------------------------- SCOPES

    public function scopePorEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    # ---------------------------------------------------------------- ESTADO

    /** rosi@gmail.com -> ro******@gmail.com; o numero fixo de * nao entrega o tamanho */
    public function emailMascarado(): string
    {
        $email = (string) $this->email;
        $arroba = strrpos($email, '@');

        if ($arroba === false) {
            return '******';
        }

        $local = substr($email, 0, $arroba);
        $visivel = mb_substr($local, 0, max(1, intdiv(mb_strlen($local), 2)));

        return $visivel . '******' . substr($email, $arroba);
    }
}
