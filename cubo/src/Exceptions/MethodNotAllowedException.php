<?php

namespace Cubo\Exceptions;

/**
 * Lançada quando o caminho existe na tabela de rotas, mas nao sob o verbo pedido.
 * @package Cubo
 * @author Mateus - github.com/eeomts
 */
final class MethodNotAllowedException extends CuboException
{
    /** @param list<string> $permitidos */
    private function __construct(string $mensagem, private readonly array $permitidos)
    {
        parent::__construct($mensagem, self::CODE_METHOD_NOT_ALLOWED);
    }

    /**
     * @param list<string> $permitidos verbos que a tabela declara para o caminho
     */
    public static function for(string $metodo, array $permitidos): self
    {
        return new self(
            "Método {$metodo} não permitido; a rota aceita: " . implode(', ', $permitidos) . '.',
            $permitidos
        );
    }

    /**
     * A resposta 405 exige o cabeçalho Allow (RFC 9110), montado a partir daqui:
     * header('Allow: ' . implode(', ', $e->permitidos()))
     *
     * @return list<string>
     */
    public function permitidos(): array
    {
        return $this->permitidos;
    }
}
