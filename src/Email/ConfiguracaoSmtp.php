<?php

namespace Controla\Email;

use Cubo\Config;
use Cubo\Tools\Str;

/**
 * A secao [email] do config.ini.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class ConfiguracaoSmtp
{
    public const SECAO = 'email';

    public function __construct(
        public readonly string $host,
        public readonly int $porta,
        public readonly string $seguranca,
        public readonly string $usuario,
        public readonly string $senha,
        public readonly string $remetente,
        public readonly string $nomeRemetente,
    ) {}

    public static function daApp(): self
    {
        $arquivo = Config::getInstance()->getAppRoot() . '/config/config.ini';

        $ini = is_file($arquivo) ? parse_ini_file($arquivo, true) : false;

        return self::daSecao(is_array($ini) ? (array) ($ini[self::SECAO] ?? []) : []);
    }

    /**
     * @param array<string,mixed> $secao
     */
    public static function daSecao(array $secao): self
    {
        $senha = (string) ($secao['senha'] ?? '');

        return new self(
            host: (string) ($secao['host'] ?? ''),
            porta: (int) ($secao['porta'] ?? 587),
            seguranca: (string) ($secao['seguranca'] ?? 'tls'),
            usuario: (string) ($secao['usuario'] ?? ''),
            // codificada pelo bin/encode, igual ao pass do [database]
            senha: $senha === '' ? '' : Str::cuboDecode($senha),
            remetente: (string) ($secao['remetente'] ?? ($secao['usuario'] ?? '')),
            nomeRemetente: (string) ($secao['nome_remetente'] ?? 'Controla'),
        );
    }

    public function completa(): bool
    {
        return $this->host !== '' && $this->usuario !== '' && $this->senha !== '' && $this->remetente !== '';
    }
}
