<?php

namespace Controla\Utils;

use Cubo\Config;
use Cubo\Http\Request;
use Cubo\Routing\Route;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class Entrada
{
    public function __construct(
        private readonly Request $request,
        private readonly ?Route $route = null
    ) {}

    public function ehPost(): bool
    {
        return $this->request->isPost();
    }

    /**
     * O JS pediu so um pedaco da tela (ele marca o fetch com este cabecalho).
     * Sem ele, a resposta continua sendo o redirect de sempre.
     */
    public function querFragmento(): bool
    {
        return $this->request->header('X-Requested-With') === 'fetch';
    }

    /**
     * O ip de quem fez a requisicao. Atras do nginx o REMOTE_ADDR e o do proxy;
     * o X-Real-IP so vale com [app] trusted_proxy, senao qualquer um o forja.
     *
     * @param bool|null $confiaNoProxy null le o [app] trusted_proxy do config.ini
     */
    public function ip(?bool $confiaNoProxy = null): string
    {
        $confiaNoProxy ??= (bool) Config::getInstance()->getConfig('ini.app.trusted_proxy');

        $real = trim((string) $this->request->header('X-Real-IP'));

        if ($confiaNoProxy && filter_var($real, FILTER_VALIDATE_IP) !== false) {
            return $real;
        }

        return $this->request->ip();
    }

    /**
     * @return array<string,mixed>
     */
    public function corpo(): array
    {
        return (array) $this->request->post();
    }

    /**
     * A query string inteira, que e por onde os filtros viajam (GET, para a
     * tela filtrada virar um endereco).
     *
     * @return array<string,mixed>
     */
    public function query(): array
    {
        return (array) $this->request->get();
    }

    public function texto(string $campo, string $default = ''): string
    {
        $valor = $this->valor($campo);

        return is_scalar($valor) ? (string) $valor : $default;
    }

    /**

     * @return list<array<string,mixed>>
     */
    public function linhas(string $campo): array
    {
        $valor = $this->request->post($campo);

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_array'));
    }

    public function inteiroOuNulo(string $campo): ?int
    {
        $valor = $this->valor($campo);

        if (!is_scalar($valor) || !is_numeric($valor)) {
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0 ? $inteiro : null;
    }

    
    private function valor(string $campo): mixed
    {
        return $this->request->input($campo) ?? $this->route?->params[$campo] ?? null;
    }
}
