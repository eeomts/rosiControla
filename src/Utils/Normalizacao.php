<?php

namespace Controla\Utils;

use Cubo\Validation\Sanitizer;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Normalizacao
{

    private const MENSAGENS = [
        'date' => 'Data invalida.',
        'money' => 'Valor invalido.',
    ];

    private const MENSAGEM_PADRAO = 'Valor invalido.';

    /**
     * @param array<string,mixed> $dados campos crus do formulario
     * @param array<string,string> $filtros campo => 'date' | 'money' | 'trim|money'
     * @return array{0: array<string,mixed>, 1: array<string,string>} [dados, erros]
     */
    public static function aplicar(array $dados, array $filtros): array
    {
        $sanitizer = new Sanitizer($dados, $filtros);
        $sanitizer->sanitize();

        return [$sanitizer->getData(), self::traduzir($sanitizer->getErrorsFlat(), $filtros)];
    }

    /**
     * @param array<string,mixed> $dados
     * @param array<string,string> $filtros
     * @return array<string,mixed>
     */
    public static function valores(array $dados, array $filtros): array
    {
        return self::aplicar($dados, $filtros)[0];
    }

    /**
     * @param array<string,string> $erros do Sanitizer, campo => mensagem dele
     * @param array<string,string> $filtros
     * @return array<string,string>
     */
    private static function traduzir(array $erros, array $filtros): array
    {
        $traduzidos = [];

        foreach (array_keys($erros) as $campo) {
            $traduzidos[$campo] = self::mensagem($filtros[$campo] ?? '');
        }

        return $traduzidos;
    }

    private static function mensagem(string $filtro): string
    {
        foreach (explode('|', $filtro) as $nome) {
            $nome = trim(explode(':', $nome, 2)[0]);

            if (isset(self::MENSAGENS[$nome])) {
                return self::MENSAGENS[$nome];
            }
        }

        return self::MENSAGEM_PADRAO;
    }
}
