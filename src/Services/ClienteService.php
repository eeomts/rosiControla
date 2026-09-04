<?php

namespace Controla\Services;

use Controla\Models\Cliente;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class ClienteService
{
    /** @var list<int> */
    private const DIGITOS_TELEFONE = [10, 11];

    /**
     * @param array<string,mixed> $dados Campos crus vindos do formulario.
     * @throws DadosInvalidosException Com o mapa campo => mensagem.
     * @throws RuntimeException Se o id informado nao existe.
     */
    public function salvar(?int $id, array $dados): Cliente
    {
        $cliente = $this->encontrarOuCriar($id);

        $cliente->fill($this->normalizar($dados));

        $this->validar($cliente);

        $cliente->save();

        return $cliente;
    }

    /**
     * @throws DadosInvalidosException
     */
    public function cadastroRapido(string $nome): Cliente
    {
        return $this->salvar(null, ['nome' => $nome]);
    }

    /**
     * @param string|null $termo Filtra por nome ou telefone; vazio traz todas.
     * @return Collection<int,Cliente>
     */
    public function listar(?string $termo = null): Collection
    {
        return Cliente::query()->busca((string) $termo)->ordenado()->get();
    }

    /**
     * @throws RuntimeException Se o id nao aponta para uma cliente.
     */
    public function encontrar(?int $id): Cliente
    {
        $cliente = $id === null ? null : Cliente::findById($id);

        if ($cliente === null) {
            throw new RuntimeException('Cliente ' . ($id ?? '?') . ' nao encontrada.');
        }

        return $cliente;
    }

    /**
     * @throws RuntimeException Se o id nao aponta para uma cliente.
     */
    public function excluir(?int $id): Cliente
    {
        $cliente = $this->encontrar($id);

        $cliente->delete();

        return $cliente;
    }

    private function encontrarOuCriar(?int $id): Cliente
    {
        return $id === null ? new Cliente() : $this->encontrar($id);
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,mixed>
     */
    private function normalizar(array $dados): array
    {
        if (array_key_exists('nome', $dados)) {
            $dados['nome'] = trim((string) $dados['nome']);
        }

        if (array_key_exists('telefone', $dados)) {
            $digitos = preg_replace('/\D/', '', (string) $dados['telefone']) ?? '';

            $dados['telefone'] = $digitos === '' ? null : $digitos;
        }

        return $dados;
    }

    /**
     * @throws DadosInvalidosException
     */
    private function validar(Cliente $cliente): void
    {
        $erros = [];

        if ((string) $cliente->nome === '') {
            $erros['nome'] = 'Informe o nome da cliente.';
        }

        if ($cliente->telefone !== null && !in_array(strlen((string) $cliente->telefone), self::DIGITOS_TELEFONE, true)) {
            $erros['telefone'] = 'Informe o telefone com DDD.';
        }

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }
    }
}
