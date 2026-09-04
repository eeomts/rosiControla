<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Ciclo;
use Controla\Models\Pedido;
use Controla\Models\Produto;
use Controla\Services\PedidoService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\RegistroEmUsoException;
use Controla\Utils\Redirecionamento;
use RuntimeException;

/**
 * O pedido e as unidades que entraram por ele.
 *
 * A tela tem dois passos porque o cadastro dela e assim: primeiro o cabecalho
 * (ciclo e data), depois os produtos vao entrando um a um com a nota na mao.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class PedidoController extends FeatureController
{
    private const URL_LISTA = '/pedido';

    protected const CAMPOS = ['fk_ciclo', 'nome', 'data_pedido'];

    private PedidoService $service;

    protected function iniciar(): void
    {
        $this->service = new PedidoService();
    }

    public function index(): void
    {
        $this->pagina('Pedidos', 'pedido/lista.php', [
            'pedidos' => $this->service->listar()->load('ciclo'),
        ]);
    }

    public function form(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        if ($id === null) {
            $this->formulario(null, $this->valoresVazios(), []);

            return;
        }

        try {
            $pedido = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Esse pedido nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->formulario($pedido->id, $this->valoresDe($pedido), [], $pedido);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $pedido = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->formulario($id, $this->valoresDigitados(), $e->erros(), $this->pedidoOuNulo($id));

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Esse pedido nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        // pedido novo continua no form: e la que ela adiciona os produtos
        $this->flash->sucesso("{$pedido->nome} salvo.");
        Redirecionamento::para(self::URL_LISTA . '/form/' . $pedido->id)->enviar();
    }

    /** Cadastra N unidades de um produto neste pedido. */
    public function adicionar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $pedido = $this->service->encontrar($id);
            $unidades = $this->service->adicionarProduto($pedido, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            $this->formulario($id, $this->valoresDe($this->service->encontrar($id)), $e->erros(), $this->pedidoOuNulo($id));

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Esse pedido nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();

            return;
        }

        $this->flash->sucesso(count($unidades) . ' unidade(s) adicionada(s).');
        Redirecionamento::para(self::URL_LISTA . '/form/' . $pedido->id)->enviar();
    }

    /** Tira UMA unidade do grupo -- a tela manda o id de uma delas. */
    public function remover(): void
    {
        $pedidoId = $this->request->inteiroOuNulo('id');

        try {
            $unidade = $this->service->encontrarUnidade($this->request->inteiroOuNulo('unidade'));
            $this->service->removerUnidade($unidade);
            $this->flash->sucesso('Unidade removida.');
        } catch (RegistroEmUsoException $e) {
            $this->flash->erro($e->getMessage());
        } catch (RuntimeException) {
            $this->flash->erro('Essa unidade nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA . '/form/' . (int) $pedidoId)->enviar();
    }

    public function excluir(): void
    {
        try {
            $pedido = $this->service->excluir($this->request->inteiroOuNulo('id'));
            $this->flash->sucesso("{$pedido->nome} excluido com as unidades dele.");
        } catch (RegistroEmUsoException $e) {
            $this->flash->erro($e->getMessage());
        } catch (RuntimeException) {
            $this->flash->erro('Esse pedido nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function formulario(?int $id, array $valores, array $erros, ?Pedido $pedido = null): void
    {
        $this->pagina($id === null ? 'Novo pedido' : 'Editar pedido', 'pedido/form.php', [
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
            'pedido' => $pedido,
            'unidades' => $pedido === null ? [] : $this->service->unidadesAgrupadas($pedido),
            'ciclos' => Ciclo::query()->maisRecente()->pluck('nome', 'id')->all(),
            'produtos' => Produto::query()->ordenado()->pluck('nome', 'id')->all(),
        ]);
    }

    /** @return array<string,string> */
    private function valoresDe(Pedido $pedido): array
    {
        return [
            'fk_ciclo' => (string) $pedido->fk_ciclo,
            'nome' => (string) $pedido->nome,
            'data_pedido' => $pedido->data_pedido?->format('Y-m-d') ?? '',
        ];
    }

    private function pedidoOuNulo(?int $id): ?Pedido
    {
        return $id === null ? null : Pedido::findById($id);
    }
}
