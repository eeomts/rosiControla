<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Models\Cliente;
use Controla\Services\ClienteService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Redirecionamento;
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
        $this->pagina('Clientes', 'cliente/lista.php', [
            'clientes' => $this->service->listar(),
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
            $cliente = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Essa cliente nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->formulario($cliente->id, $this->valoresDe($cliente), []);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $cliente = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou
            $this->formulario($id, $this->valoresDigitados(), $e->erros());

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Essa cliente nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("{$cliente->nome} salva.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
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
    private function formulario(?int $id, array $valores, array $erros): void
    {
        $this->pagina($id === null ? 'Nova cliente' : 'Editar cliente', 'cliente/form.php', [
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
