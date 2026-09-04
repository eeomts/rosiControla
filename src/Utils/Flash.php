<?php

namespace Controla\Utils;

use Cubo\Session;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Flash
{
    public const SUCESSO = 'sucesso';
    public const ERRO = 'erro';

    private const CHAVE = 'flash';

    public function __construct(private readonly Session $sessao) {}

    public static function daGlobal(): self
    {
        return new self(Session::getInstance());
    }

    public function sucesso(string $mensagem): void
    {
        $this->guardar(self::SUCESSO, $mensagem);
    }

    public function erro(string $mensagem): void
    {
        $this->guardar(self::ERRO, $mensagem);
    }

    /**
     * @return array{tipo: string, mensagem: string}|null
     */
    public function consumir(): ?array
    {
        $recado = $this->sessao->get(self::CHAVE, null);

        $this->sessao->remove(self::CHAVE);

        if (!is_array($recado) || !isset($recado['tipo'], $recado['mensagem'])) {
            return null;
        }

        return ['tipo' => (string) $recado['tipo'], 'mensagem' => (string) $recado['mensagem']];
    }

    private function guardar(string $tipo, string $mensagem): void
    {
        $this->sessao->set(self::CHAVE, ['tipo' => $tipo, 'mensagem' => $mensagem]);
    }
}
