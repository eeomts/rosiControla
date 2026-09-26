<?php

namespace Controla\Models;

use Cubo\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Uma senha errada: de onde veio, para qual email e quando.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class TentativaLogin extends Model
{
    protected $table = 'tentativa_login';

    protected $fillable = [
        'ip', 'email', 'data_tentativa',
    ];

    protected $casts = [
        'data_tentativa' => 'datetime',
    ];

    # ---------------------------------------------------------------- SCOPES

    public function scopeDoIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip', $ip);
    }

    public function scopeDoEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    public function scopeDesde(Builder $query, Carbon $inicio): Builder
    {
        return $query->where('data_tentativa', '>=', $inicio);
    }
}
