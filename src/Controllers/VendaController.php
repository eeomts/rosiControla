<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Cliente;
use Controla\Models\StatusEntrega;
use Controla\Models\StatusPagamento;
use Controla\Models\Venda;
use Controla\Services\VendaService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Redirecionamento;
use Cubo\Tools\Date;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class VendaController extends FeatureController
{
    private const URL_LISTA = '/venda';

    protected const CAMPOS = [
        'fk_cliente', 'data_venda', 'fk_status_pagamento', 'fk_status_entrega', 'mon_desconto',
    ];

    private VendaService $service;

    protected function iniciar(): void
    {
        $this->service = new VendaService();
    }

    public function index(): void
    {
        $this->pagina('Vendas', 'venda/lista.php', [
            'vendas' => $this->service->listar()->load(['cliente', 'statusPagamento', 'statusEntrega']),
        ]);
    }

    public function form(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        if ($id === null) {
            $this->formulario(null, $this->valoresNovos(), [], [], null);

            return;
        }

        try {
            $venda = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Essa venda nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->formulario($venda->id, $this->valoresDe($venda), [], $this->itensDe($venda), $venda);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');
        $itens = $this->request->linhas('itens');

        try {
            $venda = $this->service->salvar($id, $this->request->corpo(), $itens);
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa dos itens que ela ja montou
            $this->formulario($id, $this->valoresDigitados(), $e->erros(), $itens, $this->vendaOuNulo($id));

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Essa venda nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("Venda para {$venda->cliente?->nome} salva.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    public function excluir(): void
    {
        try {
            $venda = $this->service->excluir($this->request->inteiroOuNulo('id'));
            $this->flash->sucesso("Venda para {$venda->cliente?->nome} excluida; as unidades voltaram ao estoque.");
        } catch (RuntimeException) {
            $this->flash->erro('Essa venda nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     * @param list<array<string,mixed>> $itens
     */
    private function formulario(?int $id, array $valores, array $erros, array $itens, ?Venda $venda): void
    {
        $this->pagina($id === null ? 'Nova venda' : 'Editar venda', 'venda/form.php', [
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
            'itens' => $this->normalizarItens($itens),
            'estoque' => $this->service->estoqueParaVenda($venda),
            'clientes' => Cliente::query()->ordenado()->pluck('nome', 'id')->all(),
            'statusPagamento' => StatusPagamento::paraSelect(),
            'statusEntrega' => StatusEntrega::paraSelect(),
        ]);
    }

    /**

     * @param list<array<string,mixed>> $itens
     * @return list<array<string,string>>
     */
    private function normalizarItens(array $itens): array
    {
        $limpos = [];

        foreach ($itens as $item) {
            $id = (int) ($item['fk_variacao_produto'] ?? 0);

            if ($id > 0) {
                $limpos[] = [
                    'fk_variacao_produto' => (string) $id,
                    'mon_venda' => (string) ($item['mon_venda'] ?? ''),
                    'mon_desconto' => (string) ($item['mon_desconto'] ?? ''),
                ];
            }
        }

        return $limpos;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function itensDe(Venda $venda): array
    {
        return $venda->itens->map(fn($item): array => [
            'fk_variacao_produto' => $item->fk_variacao_produto,
            'mon_venda' => $item->mon_venda,
            'mon_desconto' => $item->mon_desconto,
        ])->all();
    }

    /** @return array<string,string> */
    private function valoresDe(Venda $venda): array
    {
        return [
            'fk_cliente' => (string) $venda->fk_cliente,
            'data_venda' => $venda->data_venda?->format('Y-m-d') ?? '',
            'fk_status_pagamento' => (string) $venda->fk_status_pagamento,
            'fk_status_entrega' => (string) $venda->fk_status_entrega,
            'mon_desconto' => (string) $venda->mon_desconto,
        ];
    }

    
    private function valoresNovos(): array
    {
        return array_merge($this->valoresVazios(), [
            'data_venda' => Date::now('Y-m-d'),
            'mon_desconto' => '0,00',
        ]);
    }

    
    private function vendaOuNulo(?int $id): ?Venda
    {
        return $id === null ? null : Venda::findById($id);
    }
}
