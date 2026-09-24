<?php

namespace Controla\Services;

use Controla\Models\Ciclo;
use Controla\Models\Pedido;
use Controla\Models\StatusEntrega;
use Controla\Models\StatusPagamento;
use Controla\Models\VariacaoProduto;
use Controla\Models\Venda;
use Cubo\Tools\Date;
use Cubo\Tools\Number;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class DashboardService
{
    private const ULTIMAS_VENDAS = 5;

    private const NAO_PAGO = 'Nao pago';
    private const NAO_ENTREGUE = 'Nao entregue';

    /**
     * @return array{ciclo: Ciclo|null, vigente: bool, dias: int|null, comeca: int|null}
     */
    public function cicloVigente(?string $hoje = null): array
    {
        $hoje ??= Date::now('Y-m-d');

        $ciclo = Ciclo::query()->vigenteEm($hoje)->first();
        $vigente = $ciclo !== null;

        if (!$vigente) {
            $ciclo = Ciclo::query()->maisRecente()->first();
        }

        return [
            'ciclo' => $ciclo,
            'vigente' => $vigente,
            'dias' => $this->diasAte($hoje, $ciclo?->data_termino),
            'comeca' => $this->diasAte($hoje, $ciclo?->data_inicio),
        ];
    }

    /**
     * @return array{total: string, vendas: int, clientes: int}
     */
    public function aReceber(): array
    {
        $status = StatusPagamento::idPorNome(self::NAO_PAGO);

        if ($status === null) {
            return ['total' => '0.00', 'vendas' => 0, 'clientes' => 0];
        }

        return [
            'total' => $this->dinheiro(Venda::query()->comStatusPagamento($status)->sum('mon_total')),
            'vendas' => Venda::query()->comStatusPagamento($status)->count(),
            'clientes' => Venda::query()->comStatusPagamento($status)->distinct()->count('fk_cliente'),
        ];
    }

    /**
     * @return array{unidades: int, valor: string, lucro_real: string, lucro_estimado: string}
     */
    public function estoqueELucro(?Ciclo $ciclo): array
    {
        $pedidos = Pedido::query();

        
        if ($ciclo !== null) {
            $pedidos->where('fk_ciclo', $ciclo->id);
        }

        return [
            'unidades' => VariacaoProduto::query()->disponivel()->count(),
            'valor' => $this->dinheiro(VariacaoProduto::query()->disponivel()->sum('mon_venda')),
            'lucro_real' => $this->dinheiro((clone $pedidos)->sum('mon_lucro_real')),
            'lucro_estimado' => $this->dinheiro($pedidos->sum('mon_lucro_estimado')),
        ];
    }

    private function dinheiro(mixed $valor): string
    {
        return (string) Number::toDecimal(is_numeric($valor) ? $valor : 0);
    }

    public function entregasPendentes(): int
    {
        $status = StatusEntrega::idPorNome(self::NAO_ENTREGUE);

        return $status === null ? 0 : Venda::query()->comStatusEntrega($status)->count();
    }

    /**
     * @return Collection<int,Venda>
     */
    public function ultimasVendas(): Collection
    {
        return Venda::query()
            ->with(['cliente', 'statusPagamento', 'statusEntrega'])
            ->maisRecente()
            ->limit(self::ULTIMAS_VENDAS)
            ->get();
    }

    private function diasAte(string $hoje, ?Carbon $data): ?int
    {
        if ($data === null) {
            return null;
        }

        return (int) Carbon::parse($hoje)->startOfDay()->diffInDays($data->copy()->startOfDay(), false);
    }
}
