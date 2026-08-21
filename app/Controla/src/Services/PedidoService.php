<?php

namespace Controla\Services;

use Controla\Models\Ciclo;
use Controla\Utils\Concerns\NormalizaDatas;
use Controla\Utils\Concerns\NormalizaMoeda;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Models\Pedido;
use Controla\Models\VariacaoProduto;
use Controla\Models\VendaVariacaoRel;
use Controla\Models\Produto;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class PedidoService
{
    use NormalizaDatas;
    use NormalizaMoeda;

    private const QUANTIDADE_MAXIMA = 999;

    /**
     * @param array<string,mixed> $dados Campos crus vindos do formulario.
     * @throws DadosInvalidosException Com o mapa campo => mensagem.
     * @throws RuntimeException Se o id informado nao existe.
     */
    public function salvar(?int $id, array $dados): Pedido
    {
        $pedido = $this->encontrarOuCriar($id);

        [$dados, $erros] = $this->normalizarDatas($dados, ['data_pedido']);

        $pedido->fill($dados);

        $this->validar($pedido, $erros);

        $pedido->nome = $this->nomeOuPadrao($pedido);

        $pedido->save();

        return $pedido;
    }

    /**
     * @param array<string,mixed> $dados fk_produto, quantidade, mon_custo, mon_venda, data_validade
     * @return list<VariacaoProduto>
     * @throws DadosInvalidosException
     */
    public function adicionarProduto(Pedido $pedido, array $dados): array
    {
        [$dados, $erros] = $this->normalizarDatas($dados, ['data_validade']);
        $dados = $this->normalizarMoeda($dados, ['mon_custo', 'mon_venda']);

        $this->validarProduto($dados, $erros);

        $quantidade = (int) $dados['quantidade'];
        $unidades = [];

        for ($i = 0; $i < $quantidade; $i++) {
            $unidades[] = VariacaoProduto::create([
                'fk_produto' => (int) $dados['fk_produto'],
                'fk_pedido' => $pedido->getKey(),
                'fk_ciclo' => $pedido->fk_ciclo,
                'data_validade' => $dados['data_validade'] ?? null,
                'mon_custo' => $dados['mon_custo'],
                'mon_venda' => $dados['mon_venda'],
                'vendido' => 0,
            ]);
        }

        $this->recalcular($pedido);

        return $unidades;
    }

    public function removerUnidade(VariacaoProduto $unidade): void
    {
        $pedido = $unidade->pedido;

        $unidade->delete();

        if ($pedido !== null) {
            $this->recalcular($pedido);
        }
    }

    public function recalcular(Pedido $pedido): Pedido
    {
        
        $totais = VariacaoProduto::query()
            ->doPedido($pedido->getKey())
            ->selectRaw('COALESCE(SUM(mon_custo), 0) as total')
            ->selectRaw('COALESCE(SUM(mon_venda - mon_custo), 0) as lucro')
            ->first();

        $pedido->mon_total = $this->somaMoeda((float) $totais->total);
        $pedido->mon_lucro_estimado = $this->somaMoeda((float) $totais->lucro);

        $pedido->save();

        return $pedido;
    }

    
    public function recalcularLucroReal(Pedido $pedido): Pedido
    {
        $total = VendaVariacaoRel::query()
            ->join('variacao_produto', 'variacao_produto.id', '=', 'venda_variacao_rel.fk_variacao_produto')
            ->where('variacao_produto.fk_pedido', $pedido->getKey())
            ->where('variacao_produto.deleted', '!=', 1)
            ->selectRaw(
                'COALESCE(SUM(venda_variacao_rel.mon_venda'
                . ' - venda_variacao_rel.mon_desconto'
                . ' - venda_variacao_rel.mon_desconto_rateio'
                . ' - variacao_produto.mon_custo), 0) as lucro'
            )
            ->first();

        $pedido->mon_lucro_real = $this->somaMoeda((float) $total->lucro);

        $pedido->save();

        return $pedido;
    }

    private function encontrarOuCriar(?int $id): Pedido
    {
        if ($id === null) {
            return new Pedido();
        }

        $pedido = Pedido::findById($id);

        if ($pedido === null) {
            throw new RuntimeException("Pedido {$id} nao encontrado.");
        }

        return $pedido;
    }

    
    private function nomeOuPadrao(Pedido $pedido): string
    {
        $nome = trim((string) $pedido->nome);

        if ($nome !== '') {
            return $nome;
        }

        $ciclo = Ciclo::findById((int) $pedido->fk_ciclo);
        $mes = $pedido->data_pedido->format('m');

        return "C{$ciclo->num_ciclo}-{$mes}-{$this->sequenciaNoCiclo($pedido)}";
    }

    private function sequenciaNoCiclo(Pedido $pedido): int
    {
        return Pedido::query()
            ->doCiclo((int) $pedido->fk_ciclo)
            ->when($pedido->exists, fn($query) => $query->where('id', '!=', $pedido->getKey()))
            ->count() + 1;
    }

    /**
     * @param array<string,string> $erros Erros ja coletados na normalizacao.
     * @throws DadosInvalidosException
     */
    private function validar(Pedido $pedido, array $erros = []): void
    {
        if (empty($pedido->fk_ciclo)) {
            $erros['fk_ciclo'] = 'Selecione o ciclo do pedido.';
        } elseif (Ciclo::findById((int) $pedido->fk_ciclo) === null) {
            $erros['fk_ciclo'] = 'O ciclo selecionado nao existe.';
        }

        if (!isset($erros['data_pedido']) && empty($pedido->data_pedido)) {
            $erros['data_pedido'] = 'Informe a data do pedido.';
        }

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }
    }

    /**
     * @param array<string,mixed> $dados
     * @param array<string,string> $erros
     * @throws DadosInvalidosException
     */
    private function validarProduto(array $dados, array $erros = []): void
    {
        $produto = (int) ($dados['fk_produto'] ?? 0);

        if ($produto < 1) {
            $erros['fk_produto'] = 'Selecione o produto.';
        } elseif (Produto::findById($produto) === null) {
            $erros['fk_produto'] = 'O produto selecionado nao existe.';
        }

        $quantidade = (int) ($dados['quantidade'] ?? 0);

        if ($quantidade < 1 || $quantidade > self::QUANTIDADE_MAXIMA) {
            $erros['quantidade'] = 'Informe uma quantidade entre 1 e ' . self::QUANTIDADE_MAXIMA . '.';
        }

        foreach (['mon_custo' => 'custo', 'mon_venda' => 'venda'] as $campo => $label) {
            $valor = $dados[$campo] ?? null;

            if ($valor === null) {
                $erros[$campo] = "Informe o preco de {$label}.";
            } elseif ($valor < 0) {
                $erros[$campo] = "O preco de {$label} nao pode ser negativo.";
            }
        }

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }
    }
}
