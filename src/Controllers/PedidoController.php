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
use Controla\Views\FragmentoView;
use RuntimeException;

/**
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
        $this->tela(false, null, $this->valoresVazios(), []);
    }

    public function form(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        if ($id === null) {
            $this->tela(true, null, $this->valoresVazios(), []);

            return;
        }

        try {
            $pedido = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Esse pedido nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->tela(true, $pedido->id, $this->valoresDe($pedido), [], $pedido);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $pedido = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->tela(true, $id, $this->valoresDigitados(), $e->erros(), $this->pedidoOuNulo($id));

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
            if ($this->request->querFragmento()) {
                $this->fragmentoUnidades($id, implode(' ', $e->erros()), 422);

                return;
            }

            $this->tela(true, $id, $this->valoresDe($this->service->encontrar($id)), $e->erros(), $this->pedidoOuNulo($id));

            return;
        } catch (RuntimeException) {
            if ($this->request->querFragmento()) {
                $this->fragmentoUnidades(null, 'Esse pedido nao existe mais.', 404);

                return;
            }

            $this->flash->erro('Esse pedido nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();

            return;
        }

        // o +/- so quer o "No pedido" de novo; o contador ja e o recado
        if ($this->request->querFragmento()) {
            $this->fragmentoUnidades($pedido->id);

            return;
        }

        $this->flash->sucesso(count($unidades) . ' unidade(s) adicionada(s).');
        Redirecionamento::para(self::URL_LISTA . '/form/' . $pedido->id)->enviar();
    }

    /** Tira UMA unidade do grupo -- a tela manda o id de uma delas. */
    public function remover(): void
    {
        $pedidoId = $this->request->inteiroOuNulo('id');
        $aviso = '';

        try {
            $unidade = $this->service->encontrarUnidade($this->request->inteiroOuNulo('unidade'));
            $this->service->removerUnidade($unidade);
        } catch (RegistroEmUsoException $e) {
            $aviso = $e->getMessage();
        } catch (RuntimeException) {
            $aviso = 'Essa unidade nao existe mais.';
        }

        if ($this->request->querFragmento()) {
            // 200 mesmo com aviso: a tabela nova ja mostra o estado certo
            $this->fragmentoUnidades($pedidoId, $aviso);

            return;
        }

        if ($aviso === '') {
            $this->flash->sucesso('Unidade removida.');
        } else {
            $this->flash->erro($aviso);
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
     * A tela e sempre a lista; o pedido abre num modal dentro dela.
     *
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function tela(
        bool $modalAberto,
        ?int $id,
        array $valores,
        array $erros,
        ?Pedido $pedido = null
    ): void {
        $this->pagina('Pedidos', 'pedido/lista.php', [
            'pedidos' => $this->service->listar()->load('ciclo'),
            'modal_aberto' => $modalAberto,
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
            'pedido' => $pedido,
            # so quando o modal abre: agrupar unidade e consulta, e a listagem
            # nao usa nada disso
            'unidades' => ($modalAberto && $pedido !== null) ? $this->service->unidadesAgrupadas($pedido) : [],
            'ciclos' => $modalAberto ? Ciclo::query()->maisRecente()->pluck('nome', 'id')->all() : [],
            'produtos' => $modalAberto ? Produto::query()->ordenado()->pluck('nome', 'id')->all() : [],
        ]);
    }

    /** So o "No pedido" (pedido/unidades.php), sem layout: resposta do +/-. */
    private function fragmentoUnidades(?int $id, string $aviso = '', int $status = 200): void
    {
        $pedido = $this->pedidoOuNulo($id);

        $view = new FragmentoView('pedido/unidades.php', $pedido === null ? 404 : $status);
        $view->addParam('pedido', $pedido);
        $view->addParam('unidades', $pedido === null ? [] : $this->service->unidadesAgrupadas($pedido));
        $view->addParam('aviso', $aviso);

        $this->setView($view);
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
