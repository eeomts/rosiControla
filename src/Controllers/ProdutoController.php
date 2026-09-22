<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Genero;
use Controla\Models\Produto;
use Controla\Services\ProdutoService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\RegistroEmUsoException;
use Controla\Utils\Redirecionamento;
use Controla\Views\JsonView;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class ProdutoController extends FeatureController
{
    private const URL_LISTA = '/produto';

    protected const CAMPOS = ['nome', 'codigo_produto', 'fk_genero'];

    private ProdutoService $service;

    protected function iniciar(): void
    {
        $this->service = new ProdutoService();
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
            $produto = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Esse produto nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->tela(true, $produto->id, $this->valoresDe($produto), []);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $produto = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->tela(true, $id, $this->valoresDigitados(), $e->erros());

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Esse produto nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("{$produto->nome} salvo.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * Cadastro so com o nome, pedido de dentro do modal do pedido. Responde
     * JSON: a tela de tras nao pode recarregar.
     */
    public function rapido(): void
    {
        $nome = trim($this->request->texto('nome'));

        try {
            $produto = $this->service->cadastroRapido($nome);
        } catch (DadosInvalidosException $e) {
            $this->setView(new JsonView(['ok' => false, 'erro' => implode(' ', $e->erros())], 422));

            return;
        }

        $this->setView(new JsonView([
            'ok' => true,
            'id' => (int) $produto->id,
            'nome' => (string) $produto->nome,
        ]));
    }

    public function excluir(): void
    {
        try {
            $produto = $this->service->excluir($this->request->inteiroOuNulo('id'));
            $this->flash->sucesso("{$produto->nome} excluido.");
        } catch (RegistroEmUsoException $e) {
            // aqui a mensagem do service e melhor do que qualquer generica: diz
            // quantas unidades seguram o produto
            $this->flash->erro($e->getMessage());
        } catch (RuntimeException) {
            $this->flash->erro('Esse produto nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function tela(bool $modalAberto, ?int $id, array $valores, array $erros): void
    {
        $this->pagina('Produtos', 'produto/lista.php', [
            'produtos' => $this->service->listar()->load('genero'),
            'modal_aberto' => $modalAberto,
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
            'generos' => Genero::paraSelect(),
        ]);
    }

    /** @return array<string,string> */
    private function valoresDe(Produto $produto): array
    {
        return [
            'nome' => (string) $produto->nome,
            'codigo_produto' => (string) $produto->codigo_produto,
            'fk_genero' => (string) $produto->fk_genero,
        ];
    }
}
