<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Filtro\Valores;
use Controla\Models\Ciclo;
use Controla\Services\CicloService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Redirecionamento;
use Cubo\Tools\Date;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class CicloController extends FeatureController
{
    private const URL_LISTA = '/ciclo';

    protected const CAMPOS = ['nome', 'num_ciclo', 'num_ano', 'data_inicio', 'data_termino'];

    private CicloService $service;

    protected function iniciar(): void
    {
        $this->service = new CicloService();
    }

    public function index(): void
    {
        $this->tela(false, null, $this->valoresVazios(), []);
    }

    public function form(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        if ($id === null) {
            $this->tela(true, null, $this->valoresVazios(), []);

            return;
        }

        try {
            $ciclo = $this->service->encontrar($id);
        } catch (RuntimeException) {
            $this->flash->erro('Esse ciclo nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->tela(true, $ciclo->id, $this->valoresDe($ciclo), []);
    }

    public function salvar(): void
    {
        $id = $this->request->inteiroOuNulo('id');

        try {
            $ciclo = $this->service->salvar($id, $this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // sem redirect: a tela de erro precisa do que ela digitou, e o
            // modal tem de voltar aberto
            $this->tela(true, $id, $this->valoresDigitados(), $e->erros());

            return;
        } catch (RuntimeException) {
            $this->flash->erro('Esse ciclo nao existe mais.');
            Redirecionamento::para(self::URL_LISTA)->enviar();
        }

        $this->flash->sucesso("{$ciclo->nome} salvo.");
        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    public function excluir(): void
    {
        try {
            $ciclo = $this->service->excluir($this->request->inteiroOuNulo('id'));
            $this->flash->sucesso("{$ciclo->nome} excluido.");
        } catch (RuntimeException) {
            $this->flash->erro('Esse ciclo nao existe mais.');
        }

        Redirecionamento::para(self::URL_LISTA)->enviar();
    }

    /**
     * A tela e sempre a lista; o formulario e um modal dentro dela. Por isso
     * ate o erro de validacao passa por aqui: a lista precisa vir junto.
     *
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function tela(bool $modalAberto, ?int $id, array $valores, array $erros): void
    {
        # os filtros viajam por GET, entao valem em qualquer tela da feature: o
        # POST que volta com erro de validacao nao perde o que ela filtrou
        $definicao = $this->service->filtros();
        $filtros = Valores::deRequest($this->request->query(), $definicao);

        $this->pagina('Ciclos', 'ciclo/lista.php', [
            'ciclos' => $this->service->listar($filtros),
            'filtro_definicao' => $definicao,
            'filtro_valores' => $filtros,
            'hoje' => Date::now('Y-m-d'),
            'modal_aberto' => $modalAberto,
            'id' => $id,
            'valores' => $valores,
            'erros' => $erros,
        ]);
    }

    /** @return array<string,string> */
    private function valoresDe(Ciclo $ciclo): array
    {
        return [
            'nome' => (string) $ciclo->nome,
            'num_ciclo' => (string) $ciclo->num_ciclo,
            'num_ano' => (string) $ciclo->num_ano,
            'data_inicio' => $ciclo->data_inicio?->format('Y-m-d') ?? '',
            'data_termino' => $ciclo->data_termino?->format('Y-m-d') ?? '',
        ];
    }
}
