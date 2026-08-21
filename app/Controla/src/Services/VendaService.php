<?php

namespace Controla\Services;

use Controla\Models\Cliente;
use Controla\Models\Pedido;
use Controla\Models\StatusEntrega;
use Controla\Models\StatusPagamento;
use Controla\Models\VariacaoProduto;
use Controla\Models\Venda;
use Controla\Models\VendaVariacaoRel;
use Controla\Utils\Concerns\NormalizaDatas;
use Controla\Utils\Concerns\NormalizaMoeda;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Cubo\Database\Db;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * A venda e as unidades que sairam nela.
 *
 * Duas regras dao o tom deste service:
 *
 * 1. Vender CONSOME estoque -- cada unidade vira `vendido = 1` e volta para a
 *    prateleira se a venda for editada ou excluida.
 * 2. O desconto da venda inteira e RATEADO na hora de salvar e gravado item a
 *    item, nunca recalculado depois. Assim o lucro real do pedido e uma soma.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class VendaService
{
    use NormalizaDatas;
    use NormalizaMoeda;

    private PedidoService $pedidos;

    public function __construct(?PedidoService $pedidos = null)
    {
        $this->pedidos = $pedidos ?? new PedidoService();
    }

    /**
     * Cria (id null) ou atualiza a venda inteira: cabecalho e itens de uma vez.
     *
     * @param array<string,mixed> $dados fk_cliente, data_venda, os dois status e mon_desconto
     * @param list<array<string,mixed>> $itens Cada um com fk_variacao_produto e,
     *                                         opcionalmente, mon_venda e mon_desconto
     * @throws DadosInvalidosException Com o mapa campo => mensagem.
     * @throws RuntimeException Se o id informado nao existe.
     */
    public function salvar(?int $id, array $dados, array $itens): Venda
    {
        $venda = $this->encontrarOuCriar($id);

        [$dados, $erros] = $this->normalizarDatas($dados, ['data_venda']);
        $dados = $this->normalizarMoeda($dados, ['mon_desconto']);
        $dados['mon_desconto'] ??= '0.00';

        $venda->fill($dados);

        $unidades = $this->validar($venda, $itens, $erros);

        return Db::getInstance()->getConnection()->transaction(
            fn(): Venda => $this->gravar($venda, $itens, $unidades)
        );
    }

    /**
     * @return Collection<int,Venda>
     */
    public function listar(): Collection
    {
        return Venda::query()->maisRecente()->get();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function estoqueParaVenda(?Venda $venda = null): array
    {
        $daVenda = $venda?->exists
            ? VendaVariacaoRel::query()->daVenda($venda->getKey())->pluck('fk_variacao_produto')->all()
            : [];

        $unidades = VariacaoProduto::query()
            ->with(['produto', 'ciclo'])
            ->where(fn($query) => $query->where('vendido', 0)->orWhereIn('id', $daVenda))
            ->orderBy('fk_produto')
            ->orderBy('fk_ciclo')
            ->get();

        $grupos = [];

        foreach ($unidades as $unidade) {
            $chave = implode('|', [
                $unidade->fk_produto, $unidade->fk_pedido, $unidade->fk_ciclo,
                $unidade->mon_custo, $unidade->mon_venda, $unidade->data_validade?->format('Y-m-d'),
            ]);

            $grupos[$chave] ??= [
                'produto' => (string) $unidade->produto?->nome,
                'fk_produto' => (int) $unidade->fk_produto,
                'ciclo' => (string) $unidade->ciclo?->nome,
                'preco' => $this->somaMoeda((float) $unidade->mon_venda),
                'validade' => $unidade->data_validade?->format('d/m/Y') ?? '',
                'ids' => [],
            ];

            $grupos[$chave]['ids'][] = (int) $unidade->getKey();
        }

        return array_values($grupos);
    }

    /**
     * @throws RuntimeException Se o id nao aponta para uma venda.
     */
    public function encontrar(?int $id): Venda
    {
        $venda = $id === null ? null : Venda::findById($id);

        if ($venda === null) {
            throw new RuntimeException('Venda ' . ($id ?? '?') . ' nao encontrada.');
        }

        return $venda;
    }

    /**
     * @throws RuntimeException Se o id nao aponta para uma venda.
     */
    public function excluir(?int $id): Venda
    {
        $venda = $this->encontrar($id);

        return Db::getInstance()->getConnection()->transaction(function () use ($venda): Venda {
            $pedidos = $this->devolverAoEstoque($venda);

            $venda->delete();

            $this->recalcularPedidos($pedidos);

            return $venda;
        });
    }

    /**
     * @param list<array<string,mixed>> $itens
     * @param array<int,VariacaoProduto> $unidades fk_variacao_produto => unidade
     */
    private function gravar(Venda $venda, array $itens, array $unidades): Venda
    {
        
        $pedidosTocados = $venda->exists ? $this->devolverAoEstoque($venda) : [];

        foreach ($unidades as $unidade) {
            $unidade->refresh();
        }

        $venda->mon_total = '0.00';
        $venda->save();

        $linhas = [];

        foreach ($itens as $item) {
            $unidade = $unidades[(int) $item['fk_variacao_produto']];

            $linhas[] = VendaVariacaoRel::create([
                'fk_venda' => $venda->getKey(),
                'fk_variacao_produto' => $unidade->getKey(),
                'mon_venda' => $this->precoDoItem($item, $unidade),
                'mon_desconto' => $this->descontoDoItem($item),
            ]);

            $unidade->vendido = 1;
            $unidade->save();

            $pedidosTocados[(int) $unidade->fk_pedido] = (int) $unidade->fk_pedido;
        }

        $this->ratear((float) $venda->mon_desconto, $linhas);

        $venda->mon_total = $this->somaMoeda($this->somaLiquida($linhas));
        $venda->save();

        $this->recalcularPedidos($pedidosTocados);

        return $venda;
    }

    /**

     * @param list<VendaVariacaoRel> $linhas
     */
    private function ratear(float $desconto, array $linhas): void
    {
        $base = $this->somaLiquida($linhas);

        if ($desconto <= 0.0 || $base <= 0.0) {
            return;
        }

        $distribuido = 0.0;
        $ultima = array_key_last($linhas);

        foreach ($linhas as $indice => $linha) {
            $parte = $indice === $ultima
                ? round($desconto - $distribuido, 2)
                : round($desconto * ((float) $linha->totalLiquido() / $base), 2);

            $distribuido += $parte;

            // coluna separada: o mon_desconto continua sendo so o que ela deu
            // NAQUELE item, entao reabrir a venda e salvar de novo cai no mesmo
            // rateio em vez de descontar duas vezes
            $linha->mon_desconto_rateio = $this->somaMoeda($parte);
            $linha->save();
        }
    }

    /**
     * @param list<VendaVariacaoRel> $linhas
     */
    private function somaLiquida(array $linhas): float
    {
        $total = 0.0;

        foreach ($linhas as $linha) {
            $total += (float) $linha->totalLiquido();
        }

        return $total;
    }

    /**
     * @return array<int,int> Ids dos pedidos que precisam de recalculo.
     */
    private function devolverAoEstoque(Venda $venda): array
    {
        $pedidos = [];

        foreach (VendaVariacaoRel::query()->daVenda($venda->getKey())->get() as $linha) {
            $unidade = $linha->variacao;

            if ($unidade !== null) {
                $unidade->vendido = 0;
                $unidade->save();

                $pedidos[(int) $unidade->fk_pedido] = (int) $unidade->fk_pedido;
            }

            $linha->delete();
        }

        return $pedidos;
    }

    /**
     * @param array<int,int> $pedidos
     */
    private function recalcularPedidos(array $pedidos): void
    {
        foreach ($pedidos as $id) {
            $pedido = Pedido::findById($id);

            if ($pedido !== null) {
                $this->pedidos->recalcularLucroReal($pedido);
            }
        }
    }

    /**

     * @param array<string,mixed> $item
     */
    private function precoDoItem(array $item, VariacaoProduto $unidade): string
    {
        $digitado = $this->normalizarMoeda($item, ['mon_venda'])['mon_venda'] ?? null;

        return $digitado ?? $this->somaMoeda((float) $unidade->mon_venda);
    }

    /**
     * @param array<string,mixed> $item
     */
    private function descontoDoItem(array $item): string
    {
        return $this->normalizarMoeda($item, ['mon_desconto'])['mon_desconto'] ?? '0.00';
    }

    private function encontrarOuCriar(?int $id): Venda
    {
        return $id === null ? new Venda() : $this->encontrar($id);
    }

    /**
     * @param list<array<string,mixed>> $itens
     * @param array<string,string> $erros Erros ja coletados na normalizacao.
     * @return array<int,VariacaoProduto> fk_variacao_produto => unidade, ja carregada
     * @throws DadosInvalidosException
     */
    private function validar(Venda $venda, array $itens, array $erros = []): array
    {
        if (empty($venda->fk_cliente)) {
            $erros['fk_cliente'] = 'Selecione a cliente.';
        } elseif (Cliente::findById((int) $venda->fk_cliente) === null) {
            $erros['fk_cliente'] = 'A cliente selecionada nao existe.';
        }

        if (!isset($erros['data_venda']) && empty($venda->data_venda)) {
            $erros['data_venda'] = 'Informe a data da venda.';
        }

        if (StatusPagamento::findById((int) $venda->fk_status_pagamento) === null) {
            $erros['fk_status_pagamento'] = 'Selecione o status de pagamento.';
        }

        if (StatusEntrega::findById((int) $venda->fk_status_entrega) === null) {
            $erros['fk_status_entrega'] = 'Selecione o status de entrega.';
        }

        $unidades = $this->validarItens($venda, $itens, $erros);

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }

        return $unidades;
    }

    /**
     * @param list<array<string,mixed>> $itens
     * @param array<string,string> $erros
     * @return array<int,VariacaoProduto>
     */
    private function validarItens(Venda $venda, array $itens, array &$erros): array
    {
        if ($itens === []) {
            $erros['itens'] = 'Adicione ao menos um produto na venda.';

            return [];
        }

        $unidades = [];
        $liquido = 0.0;

        foreach ($itens as $indice => $item) {
            $id = (int) ($item['fk_variacao_produto'] ?? 0);
            $campo = "itens.{$indice}";

            if (isset($unidades[$id])) {
                $erros[$campo] = 'Essa unidade ja esta nesta venda.';

                continue;
            }

            $unidade = $id > 0 ? VariacaoProduto::findById($id) : null;

            if ($unidade === null) {
                $erros[$campo] = 'A unidade escolhida nao existe.';

                continue;
            }

        
            if ($unidade->vendido && !$this->ehDaVenda($venda, $id)) {
                $erros[$campo] = 'Essa unidade ja foi vendida.';

                continue;
            }

            $preco = (float) $this->precoDoItem($item, $unidade);
            $desconto = (float) $this->descontoDoItem($item);

            if ($preco < 0) {
                $erros[$campo] = 'O preco do item nao pode ser negativo.';

                continue;
            }

            if ($desconto < 0 || $desconto > $preco) {
                $erros[$campo] = 'O desconto do item nao pode passar do preco dele.';

                continue;
            }

            $unidades[$id] = $unidade;
            $liquido += $preco - $desconto;
        }

        if (!isset($erros['itens']) && (float) $venda->mon_desconto > $liquido) {
            $erros['mon_desconto'] = 'O desconto nao pode passar do total da venda.';
        }

        return $unidades;
    }

    private function ehDaVenda(Venda $venda, int $variacao): bool
    {
        return $venda->exists && VendaVariacaoRel::query()
            ->daVenda($venda->getKey())
            ->where('fk_variacao_produto', $variacao)
            ->exists();
    }
}
