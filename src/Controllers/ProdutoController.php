<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Genero;
use Controla\Models\Produto;
use Controla\Services\ProdutoService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\RegistroEmUsoException;
use Controla\Utils\Redirecionamento;
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
        $this->pagina('Produtos', 'produto/lista.php', [
            'produtos' => $this->service->listar()->load('genero'),
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
            $produto = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Esse produto nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->formulario($produto->id, $this->valoresDe($produto), []);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $produto = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->formulario($id, $this->valoresDigitados(), $e->erros());

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Esse produto nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("{$produto->nome} salvo.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
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
    private function formulario(?int $id, array $valores, array $erros): void
    {
        $this->pagina($id === null ? 'Novo produto' : 'Editar produto', 'produto/form.php', [
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
