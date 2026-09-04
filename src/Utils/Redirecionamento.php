<?php

namespace Controla\Utils;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Redirecionamento
{
    
    public const POS_POST = 303;

    private function __construct(
        private readonly string $url,
        private readonly int $status
    ) {}

    public static function para(string $url, int $status = self::POS_POST): self
    {
        return new self($url, $status);
    }

    public function url(): string
    {
        return $this->url;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function cabecalho(): string
    {
        return 'Location: ' . $this->url;
    }

    public function enviar(): never
    {
        header($this->cabecalho(), true, $this->status);

        exit;
    }
}
