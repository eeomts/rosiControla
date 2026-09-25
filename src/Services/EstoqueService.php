<?php

namespace Controla\Services;

use Controla\Filtro\Campo;
use Controla\Filtro\Definicao;
use Controla\Filtro\Opcoes;
use Controla\Filtro\Tipo;
use Controla\Filtro\Valores;
use Controla\Models\Categoria;
use Controla\Models\Pedido;
use Controla\Models\Produto;
use Controla\Models\VariacaoProduto;
use Controla\Types\Validade;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class EstoqueService
{
    public function filtros(): Definicao
    {
        return new Definicao(
            Campo::de('fk_categoria', 'Categoria', opcoes: Opcoes::deChamada(
                static fn (): array => Categoria::paraSelect()
            )),
            Campo::de('situacao', 'Validade', Tipo::Chave, Opcoes::deLista(Validade::paraSelect())),
        );
    }

    /**
     * @return array{resumo: array<string,int|float>, produtos: list<array<string,mixed>>}
     */
    public function estoque(?Valores $filtros = null): array
    {
        $filtros ??= Valores::vazios();

        $lotes = $this->lotes($filtros);
        $resumo = $this->resumo($lotes);

        $situacao = Validade::tryFrom((string) $filtros->para('situacao'));

        if ($situacao !== null) {
            $lotes = array_values(array_filter($lotes, static fn (array $lote): bool => $lote['situacao'] === $situacao));
        }

        return ['resumo' => $resumo, 'produtos' => $this->porProduto($lotes)];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function lotes(Valores $filtros): array
    {
        $consulta = VariacaoProduto::query()
            ->disponivel()
            ->selectRaw('fk_produto, fk_pedido, data_validade, mon_custo, mon_venda, COUNT(*) as quantidade')
            ->groupBy('fk_produto', 'fk_pedido', 'data_validade', 'mon_custo', 'mon_venda');

        $categoria = $filtros->para('fk_categoria');

        if (is_string($categoria) && $categoria !== '') {
            $consulta->whereIn('fk_produto', Produto::query()->where('fk_categoria', (int) $categoria)->select('id'));
        }

        $linhas = $consulta->get();

        $pedidos = Pedido::query()->whereIn('id', $linhas->pluck('fk_pedido')->unique())->pluck('nome', 'id');

        $lotes = [];

        foreach ($linhas as $linha) {
            $dias = Validade::diasAte($linha->data_validade);

            $lotes[] = [
                'fk_produto' => (int) $linha->fk_produto,
                'pedido' => (string) ($pedidos[$linha->fk_pedido] ?? ''),
                'validade' => $linha->data_validade,
                'dias' => $dias,
                'situacao' => Validade::da($linha->data_validade),
                'quantidade' => (int) $linha->quantidade,
                'custo' => (float) $linha->mon_custo,
                'venda' => (float) $linha->mon_venda,
            ];
        }

        usort($lotes, fn (array $a, array $b): int => $this->ordemDaValidade($a, $b));

        return $lotes;
    }

    /**
     * @param list<array<string,mixed>> $lotes
     * @return array<string,int|float>
     */
    private function resumo(array $lotes): array
    {
        $resumo = ['unidades' => 0, 'vencidas' => 0, 'breve' => 0, 'custo' => 0.0, 'venda' => 0.0];

        foreach ($lotes as $lote) {
            $resumo['unidades'] += $lote['quantidade'];
            $resumo['custo'] += $lote['quantidade'] * $lote['custo'];
            $resumo['venda'] += $lote['quantidade'] * $lote['venda'];

            if ($lote['situacao'] === Validade::Vencido) {
                $resumo['vencidas'] += $lote['quantidade'];
            } elseif ($lote['situacao'] === Validade::Breve) {
                $resumo['breve'] += $lote['quantidade'];
            }
        }

        $resumo['lucro'] = $resumo['venda'] - $resumo['custo'];

        return $resumo;
    }

    /**
     * @param list<array<string,mixed>> $lotes
     * @return list<array<string,mixed>>
     */
    private function porProduto(array $lotes): array
    {
        $produtos = Produto::query()
            ->with('categoria')
            ->whereIn('id', array_unique(array_column($lotes, 'fk_produto')))
            ->get()
            ->keyBy('id');

        $grupos = [];

        foreach ($lotes as $lote) {
            $produto = $produtos[$lote['fk_produto']] ?? null;

            if ($produto === null) {
                continue;
            }

            $grupos[$produto->id] ??= [
                'id' => (int) $produto->id,
                'nome' => (string) $produto->nome,
                'codigo' => (string) ($produto->codigo_produto ?? ''),
                'categoria' => (string) ($produto->categoria?->nome ?? ''),
                'quantidade' => 0,
                'custo' => 0.0,
                'venda' => 0.0,
                'validade' => $lote['validade'],
                'dias' => $lote['dias'],
                'situacao' => $lote['situacao'],
                'lotes' => [],
            ];

            $grupo = &$grupos[$produto->id];

            $grupo['quantidade'] += $lote['quantidade'];
            $grupo['custo'] += $lote['quantidade'] * $lote['custo'];
            $grupo['venda'] += $lote['quantidade'] * $lote['venda'];
            $grupo['lotes'][] = $lote;

            unset($grupo);
        }

        $grupos = array_values($grupos);

        foreach ($grupos as &$grupo) {
            $grupo['lucro'] = $grupo['venda'] - $grupo['custo'];
        }

        unset($grupo);

        usort($grupos, fn (array $a, array $b): int => $this->ordemDaValidade($a, $b) ?: strcmp($a['nome'], $b['nome']));

        return $grupos;
    }

    /**
     * @param array<string,mixed> $a
     * @param array<string,mixed> $b
     */
    private function ordemDaValidade(array $a, array $b): int
    {
        if ($a['validade'] === null || $b['validade'] === null) {
            return ($a['validade'] === null) <=> ($b['validade'] === null);
        }

        return $a['validade'] <=> $b['validade'];
    }
}
