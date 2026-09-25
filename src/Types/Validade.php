<?php

namespace Controla\Types;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
enum Validade: string
{
    case Vencido = 'vencido';
    case Breve = 'breve';
    case Ok = 'ok';
    case Sem = 'sem';

    public const DIAS_BREVE = 45;

    public static function da(?CarbonInterface $data): self
    {
        $dias = self::diasAte($data);

        return match (true) {
            $dias === null => self::Sem,
            $dias < 0 => self::Vencido,
            $dias <= self::DIAS_BREVE => self::Breve,
            default => self::Ok,
        };
    }

    public static function diasAte(?CarbonInterface $data): ?int
    {
        if ($data === null) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($data->copy()->startOfDay(), false);
    }

    /** @return array<string,string> */
    public static function paraSelect(): array
    {
        $opcoes = [];

        foreach (self::cases() as $caso) {
            $opcoes[$caso->value] = $caso->label();
        }

        return $opcoes;
    }

    public function label(): string
    {
        return match ($this) {
            self::Vencido => 'Vencidos',
            self::Breve => 'Vencem em ate ' . self::DIAS_BREVE . ' dias',
            self::Ok => 'No prazo',
            self::Sem => 'Sem validade',
        };
    }

    public function selo(): string
    {
        return match ($this) {
            self::Vencido => 'selo-perigo',
            self::Breve => 'selo-atencao',
            self::Ok => 'selo-ok',
            self::Sem => '',
        };
    }

    public function texto(?int $dias): string
    {
        return match ($this) {
            self::Vencido => $dias === -1 ? 'venceu ontem' : 'vencido ha ' . abs((int) $dias) . ' dias',
            self::Breve => match ($dias) {
                0 => 'vence hoje',
                1 => 'vence amanha',
                default => "vence em {$dias} dias",
            },
            self::Ok => 'no prazo',
            self::Sem => 'sem validade',
        };
    }
}
