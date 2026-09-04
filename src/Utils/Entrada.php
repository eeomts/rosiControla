<?php

namespace Controla\Utils;

use Cubo\Http\Request;
use Cubo\Routing\Route;

/**
 * O que a tela mandou, ja convertido para o tipo que o controlador usa.
 *
 * Existe porque o Cubo\Http\Request e o Cubo\Routing\Route respondem meia
 * pergunta cada um: o Request sabe do corpo e da query, a Route sabe dos
 * parametros do caminho ('/ciclo/form/12'). O controlador quer perguntar
 * "quanto vale id" sem saber por onde o valor chegou -- e esta classe e o unico
 * lugar do Controla que conhece as duas fontes.
 *
 * Converter aqui, e nao no controlador, evita o (int) espalhado por sete telas:
 * campo em branco, texto e zero viram null do mesmo jeito em todas elas.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Entrada
{
    public function __construct(
        private readonly Request $request,
        private readonly ?Route $route = null
    ) {}

    public function ehPost(): bool
    {
        return $this->request->isPost();
    }

    /**
     * Campos do POST, crus, do jeito que os Services esperam receber.
     *
     * @return array<string,mixed>
     */
    public function corpo(): array
    {
        return (array) $this->request->post();
    }

    public function texto(string $campo, string $default = ''): string
    {
        $valor = $this->valor($campo);

        return is_scalar($valor) ? (string) $valor : $default;
    }

    /**
     * Linhas de um campo repetido (os itens da venda, as unidades do pedido).
     *
     * @return list<array<string,mixed>>
     */
    public function linhas(string $campo): array
    {
        $valor = $this->request->post($campo);

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_array'));
    }

    /**
     * Id positivo, ou null quando nao veio, veio vazio ou nao e numero.
     */
    public function inteiroOuNulo(string $campo): ?int
    {
        $valor = $this->valor($campo);

        if (!is_scalar($valor) || !is_numeric($valor)) {
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0 ? $inteiro : null;
    }

    /** Corpo e query primeiro; o parametro do caminho e o ultimo recurso. */
    private function valor(string $campo): mixed
    {
        return $this->request->input($campo) ?? $this->route?->params[$campo] ?? null;
    }
}
