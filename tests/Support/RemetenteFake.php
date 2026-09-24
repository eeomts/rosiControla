<?php

namespace Controla\Tests\Support;

use Controla\Email\EmailNaoEnviadoException;
use Controla\Email\Remetente;

/**
 * Guarda os emails em vez de mandar.
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class RemetenteFake implements Remetente
{
    /** @var list<array{para: string, assunto: string, html: string, texto: string}> */
    public array $enviados = [];

    public bool $falhar = false;

    public function enviar(string $para, string $assunto, string $html, string $texto): void
    {
        if ($this->falhar) {
            throw EmailNaoEnviadoException::configuracaoIncompleta();
        }

        $this->enviados[] = ['para' => $para, 'assunto' => $assunto, 'html' => $html, 'texto' => $texto];
    }

    /** Os 6 digitos do ultimo email: o teste precisa digitar o codigo que "chegou". */
    public function ultimoCodigo(): string
    {
        $ultimo = end($this->enviados);

        preg_match('/\b(\d{6})\b/', $ultimo === false ? '' : $ultimo['texto'], $achado);

        return $achado[1] ?? '';
    }
}
