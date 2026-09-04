<?php

namespace Controla\Utils\Exceptions;

use Cubo\Validation\ValidationException;

/**
 * O formulario nao passou nas regras do dominio.
 *
 * Estende a ValidationException do Cubo para falar o contrato do framework:
 * quem so conhece o Cubo (um middleware de API que devolva 422, por exemplo)
 * captura ValidationException e alcanca esta tambem, e getErrors() /
 * getMessagesFlat() passam a existir de graca.
 *
 * O que NAO foi adotado, e de proposito, e o Cubo\Validation\Validator. Ele
 * cobre required/email/min/max/numeric, e as regras daqui sao de dominio:
 * ciclo repetido, unidade ja vendida, desconto maior que o total, ano entre
 * 2000 e 2100 -- que no Validator viraria `min:2000`, ou seja, "no minimo 2000
 * CARACTERES". Fora isso, a mensagem dele e montada com o nome da coluna
 * ('num_ano e obrigatorio'), e quem le a tela e a usuaria, nao o programador.
 *
 * Por isso o mapa continua sendo campo => UMA mensagem em portugues, pronta
 * para o form; a lista de mensagens por campo do framework nao acrescenta nada
 * aqui, e o getMessagesFlat() ja aceita string.
 *
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
class DadosInvalidosException extends ValidationException
{
    /** @param array<string,string> $erros campo => mensagem */
    public function __construct(private readonly array $erros)
    {
        parent::__construct($erros, implode(' ', $erros));
    }

    /** @param array<string,string> $erros */
    public static function com(array $erros): self
    {
        return new self($erros);
    }

    /** @return array<string,string> */
    public function erros(): array
    {
        return $this->erros;
    }

    public function temErroEm(string $campo): bool
    {
        return isset($this->erros[$campo]);
    }
}
