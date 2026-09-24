<?php

namespace Controla\Email;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
interface Remetente
{
    /**
     * @param string $html corpo principal
     * @param string $texto versao sem formatacao, para cliente de email que nao le html
     * @throws EmailNaoEnviadoException
     */
    public function enviar(string $para, string $assunto, string $html, string $texto): void;
}
