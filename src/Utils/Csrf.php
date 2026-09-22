<?php

namespace Controla\Utils;

use Cubo\Security;
use Cubo\Session;

/**
 * Token que prova que o POST saiu de uma tela nossa.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Csrf
{
    public const CAMPO = '_token';

    public const CABECALHO = 'X-CSRF-Token';

    private const CHAVE = 'csrf';

    public function __construct(private readonly Session $sessao) {}

    public static function daGlobal(): self
    {
        return new self(Session::getInstance());
    }

    public function token(): string
    {
        $token = (string) $this->sessao->get(self::CHAVE, '');

        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $this->sessao->set(self::CHAVE, $token);
        }

        return $token;
    }

    /** @param string|null $enviado o que veio do formulario ou do cabecalho */
    public function valido(?string $enviado): bool
    {
        $token = (string) $this->sessao->get(self::CHAVE, '');

        
        return $token !== '' && is_string($enviado) && hash_equals($token, $enviado);
    }

    public function campo(): string
    {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . Security::escape($this->token()) . '">';
    }
}
