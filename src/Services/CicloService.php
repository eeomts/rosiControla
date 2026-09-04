<?php

namespace Controla\Services;

use Controla\Models\Ciclo;
use Controla\Utils\Concerns\NormalizaDatas;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class CicloService
{
    use NormalizaDatas;

    private const ANO_MINIMO = 2000;
    private const ANO_MAXIMO = 2100;

    /** @var list<string> */
    private const CAMPOS_DATA = ['data_inicio', 'data_termino'];

    /**
     * @param array<string,mixed> $dados Campos crus vindos do formulario.
     * @throws DadosInvalidosException Com o mapa campo => mensagem.
     * @throws RuntimeException Se o id informado nao existe.
     */
    public function salvar(?int $id, array $dados): Ciclo
    {
        $ciclo = $this->encontrarOuCriar($id);

        [$dados, $erros] = $this->normalizarDatas($dados, self::CAMPOS_DATA);

        $ciclo->fill($dados);
        $ciclo->nome = $this->nomeOuPadrao($ciclo);

        $this->validar($ciclo, $erros);

        $ciclo->save();

        return $ciclo;
    }

    /**
     * @return Collection<int,Ciclo>
     */
    public function listar(): Collection
    {
        return Ciclo::query()->maisRecente()->get();
    }

    /**
     * @throws RuntimeException Se o id nao aponta para um ciclo.
     */
    public function encontrar(?int $id): Ciclo
    {
        $ciclo = $id === null ? null : Ciclo::findById($id);

        if ($ciclo === null) {
            throw new RuntimeException('Ciclo ' . ($id ?? '?') . ' nao encontrado.');
        }

        return $ciclo;
    }

    /**
     * @throws RuntimeException Se o id nao aponta para um ciclo.
     */
    public function excluir(?int $id): Ciclo
    {
        $ciclo = $this->encontrar($id);

        $ciclo->delete();

        return $ciclo;
    }

    private function encontrarOuCriar(?int $id): Ciclo
    {
        return $id === null ? new Ciclo() : $this->encontrar($id);
    }

    
    private function nomeOuPadrao(Ciclo $ciclo): string
    {
        $nome = trim((string) $ciclo->nome);

        if ($nome !== '') {
            return $nome;
        }

        return $ciclo->num_ciclo > 0 ? "Ciclo {$ciclo->num_ciclo}" : '';
    }

    /**
     * @param array<string,string> $erros Erros ja coletados na normalizacao.
     * @throws DadosInvalidosException
     */
    private function validar(Ciclo $ciclo, array $erros = []): void
    {
        if ((int) $ciclo->num_ciclo < 1) {
            $erros['num_ciclo'] = 'Informe o numero do ciclo.';
        }

        $ano = (int) $ciclo->num_ano;

        if ($ano < self::ANO_MINIMO || $ano > self::ANO_MAXIMO) {
            $erros['num_ano'] = 'Informe um ano entre ' . self::ANO_MINIMO . ' e ' . self::ANO_MAXIMO . '.';
        }

        foreach (self::CAMPOS_DATA as $campo) {
            if (!isset($erros[$campo]) && empty($ciclo->{$campo})) {
                $erros[$campo] = 'Informe a data.';
            }
        }

        if ($erros === [] && $ciclo->data_termino < $ciclo->data_inicio) {
            $erros['data_termino'] = 'O termino nao pode ser antes do inicio.';
        }

        if (!isset($erros['num_ciclo'], $erros['num_ano']) && $this->jaExiste($ciclo)) {
            $erros['num_ciclo'] = "O ciclo {$ciclo->num_ciclo} de {$ciclo->num_ano} ja esta cadastrado.";
        }

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }
    }

    private function jaExiste(Ciclo $ciclo): bool
    {
        return Ciclo::query()
            ->where('num_ciclo', $ciclo->num_ciclo)
            ->where('num_ano', $ciclo->num_ano)
            ->when($ciclo->exists, fn($query) => $query->where('id', '!=', $ciclo->getKey()))
            ->exists();
    }
}
