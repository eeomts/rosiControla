<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Cliente;
use Controla\Services\ClienteService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Redirecionamento;
use Controla\Views\JsonView;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class ClienteController extends FeatureController
{
    private const URL_LISTA = '/cliente';

    protected const CAMPOS = ['nome', 'telefone'];

    private ClienteService $service;

    protected function iniciar(): void
    {
        $this->service = new ClienteService();
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
            $cliente = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Essa cliente nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->tela(true, $cliente->id, $this->valoresDe($cliente), []);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $cliente = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->tela(true, $id, $this->valoresDigitados(), $e->erros());

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Essa cliente nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("{$cliente->nome} salva.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * Cadastro so com o nome, pedido de dentro de outro modal. Responde JSON
     * porque quem chama e o fetch do modal empilhado: a tela de tras nao pode
     * recarregar, ela tem o que a usuaria ja digitou.
     */
    public function rapido(): void
    {
        $nome = trim($this->request->texto('nome'));

        try {
            $cliente = $this->service->cadastroRapido($nome);
        } catch (DadosInvalidosException $e) {
            $this->setView(new JsonView(['ok' => false, 'erro' => implode(' ', $e->erros())], 422));

            return;
        }

        $this->setView(new JsonView([
            'ok' => true,
            'id' => (int) $cliente->id,
            'nome' => (string) $cliente->nome,
        ]));
    }

    public function excluir(): void
    {
        try {
            $cliente = $this->service->excluir($this->request->inteiroOuNulo('id'));
            $this->flash->sucesso("{$cliente->nome} excluida.");
        } catch (RuntimeException) {
            $this->flash->erro('Essa cliente nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function tela(bool $modalAberto, ?int $id, array $valores, array $erros): void
    {
        $this->pagina('Clientes', 'cliente/lista.php', [
            'clientes' => $this->service->listar(),
            'modal_aberto' => $modalAberto,
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
        ]);
    }

    /**
     * O form recebe o telefone JA com mascara: e o que ela ve na tela, e o
     * ClienteService descarta a mascara de volta na hora de gravar.
     *
     * @return array<string,string>
     */
    private function valoresDe(Cliente $cliente): array
    {
        return [
            'nome' => (string) $cliente->nome,
            'telefone' => $cliente->telefoneFormatado(),
        ];
    }
}
