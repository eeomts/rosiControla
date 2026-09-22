<?php

namespace Controla\Views;

use Cubo\View\View;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class JsonView extends View
{
    /**
     * @param array<string,mixed> $dados
     */
    public function __construct(
        private readonly array $dados,
        private readonly int $status = 200
    ) {}

    public function render(): void
    {
        http_response_code($this->status);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($this->dados, JSON_UNESCAPED_UNICODE);
    }
}
