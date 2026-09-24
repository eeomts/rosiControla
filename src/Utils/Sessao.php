<?php

namespace Controla\Utils;

use Controla\Models\Usuario;
use Cubo\Session;

/**
 * Quem esta logada, e quem ainda esta confirmando o email.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Sessao
{
    private const LOGADA = 'usuario';

    private const PENDENTE = 'confirmacao_pendente';

    public function __construct(private readonly Session $sessao) {}

    public static function daGlobal(): self
    {
        return new self(Session::getInstance());
    }

    public function entrar(Usuario $usuario): void
    {
        $this->trocarIdentidade();

        $this->sessao->remove(self::PENDENTE);
        $this->sessao->set(self::LOGADA, ['id' => (int) $usuario->id, 'nome' => (string) $usuario->nome]);
    }

    public function sair(): void
    {
        $this->sessao->remove(self::LOGADA);
        $this->sessao->remove(self::PENDENTE);

        $this->trocarIdentidade();
    }

    public function logada(): bool
    {
        return $this->usuarioId() !== null;
    }

    public function usuarioId(): ?int
    {
        $id = (int) $this->sessao->get(self::LOGADA . '.id', 0);

        return $id > 0 ? $id : null;
    }

    public function nome(): string
    {
        return (string) $this->sessao->get(self::LOGADA . '.nome', '');
    }

    /** Cadastrou (ou tentou entrar sem ter confirmado) e falta o codigo. */
    public function aguardarConfirmacao(Usuario $usuario): void
    {
        $this->sessao->set(self::PENDENTE, (int) $usuario->id);
    }

    public function pendente(): ?int
    {
        $id = (int) $this->sessao->get(self::PENDENTE, 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Id de sessao novo e token de CSRF novo: o que alguem capturou antes do
     * login nao vale depois dele.
     */
    private function trocarIdentidade(): void
    {
        // sem sessao ativa (CLI, teste) nao ha id para trocar
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        (new Csrf($this->sessao))->girar();
    }
}
