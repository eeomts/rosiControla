<?php

namespace Controla\Services;

use Controla\Models\TentativaLogin;
use Illuminate\Support\Carbon;

/**
 * Conta as senhas erradas por ip e por conta, e diz quando tem que esperar.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class LimiteDeTentativas
{
    public const FALHAS_POR_DEGRAU = 5;

    /** @var list<int> minutos de bloqueio a cada degrau; do ultimo em diante, repete */
    public const BLOQUEIOS = [1, 5, 20, 60];

    public const FALHAS_DA_CONTA = 10;

    public const MINUTOS_DA_CONTA = 60;

    /** falha mais velha que isso nao conta mais e e apagada */
    private const HORAS_DE_MEMORIA = 24;

    /** 0 quando o ip pode tentar; o bloqueio so vale logo depois de fechar um degrau */
    public function segundosBloqueado(string $ip): int
    {
        $falhas = TentativaLogin::query()->doIp($ip)->desde($this->inicioDaMemoria());

        $total = (clone $falhas)->count();

        if ($total < self::FALHAS_POR_DEGRAU || $total % self::FALHAS_POR_DEGRAU !== 0) {
            return 0;
        }

        $ultima = Carbon::parse((clone $falhas)->max('data_tentativa'));
        $libera = $ultima->addMinutes($this->minutosDoDegrau(intdiv($total, self::FALHAS_POR_DEGRAU)));

        return max(0, (int) ceil(Carbon::now()->diffInSeconds($libera, false)));
    }

    public function registrarFalha(string $ip, string $email): void
    {
        TentativaLogin::create([
            'ip' => $ip,
            'email' => $email,
            'data_tentativa' => Carbon::now(),
        ]);

        TentativaLogin::withTrashed()->where('data_tentativa', '<', $this->inicioDaMemoria())->toBase()->delete();
    }

    /** Muitas falhas na conta, de qualquer ip: quem esta tentando pode estar trocando de ip. */
    public function contaExigeCodigo(string $email): bool
    {
        $inicio = Carbon::now()->subMinutes(self::MINUTOS_DA_CONTA);

        return TentativaLogin::query()->doEmail($email)->desde($inicio)->count() >= self::FALHAS_DA_CONTA;
    }

    public function liberarIp(string $ip): void
    {
        TentativaLogin::withTrashed()->where('ip', $ip)->toBase()->delete();
    }

    public function liberarConta(string $email): void
    {
        TentativaLogin::withTrashed()->where('email', $email)->toBase()->delete();
    }

    private function minutosDoDegrau(int $degrau): int
    {
        return self::BLOQUEIOS[min($degrau, count(self::BLOQUEIOS)) - 1];
    }

    private function inicioDaMemoria(): Carbon
    {
        return Carbon::now()->subHours(self::HORAS_DE_MEMORIA);
    }
}
