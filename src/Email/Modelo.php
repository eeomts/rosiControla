<?php

namespace Controla\Email;

use RuntimeException;

/**

 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Modelo
{
    private readonly string $pasta;

    public function __construct(?string $pasta = null)
    {
        $this->pasta = rtrim($pasta ?? dirname(__DIR__, 2) . '/templates/email', '/\\');
    }

    /**
     * @param array<string,mixed> $dados viram variaveis dentro do template
     * @throws RuntimeException Se o template nao existe.
     */
    public function html(string $nome, array $dados): string
    {
        return $this->renderizar("{$nome}.php", $dados);
    }

    /**
     * @param array<string,mixed> $dados
     * @throws RuntimeException Se o template nao existe.
     */
    public function texto(string $nome, array $dados): string
    {
        return $this->renderizar("{$nome}.txt.php", $dados);
    }

    /**
     * @param array<string,mixed> $dados
     */
    private function renderizar(string $arquivo, array $dados): string
    {
        $caminho = $this->pasta . DIRECTORY_SEPARATOR . $arquivo;

        if (!is_file($caminho)) {
            throw new RuntimeException("Template de email nao encontrado: {$caminho}");
        }

        // funcao estatica: o template enxerga so os $dados, nao o $this
        $incluir = static function (string $__caminho, array $__dados): void {
            extract($__dados, EXTR_SKIP);
            include $__caminho;
        };

        ob_start();

        try {
            $incluir($caminho, $dados);
        } finally {
            $saida = (string) ob_get_clean();
        }

        return $saida;
    }
}
