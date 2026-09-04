<?php

/**
 * Lista de ciclos.
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$ciclos = $view->getParam('ciclos', []);
$hoje = (string) $view->getParam('hoje');

// o JSON vai dentro de um atributo HTML: aspas, & e <> escapados, senao o
// navegador decodifica entidade antes de o Alpine ler
$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

// indice do filtro: uma string por linha, ja em minusculas, para o Alpine so
// comparar com includes()
$termos = [];

foreach ($ciclos as $ciclo) {
    $termos[$ciclo->id] = mb_strtolower(
        $ciclo->nome . ' ' . $ciclo->num_ciclo . ' ' . $ciclo->num_ano
            . ' ' . $ciclo->data_inicio?->format('d/m/Y')
            . ' ' . $ciclo->data_termino?->format('d/m/Y')
    );
}

?>
<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <div class="barra">
        <input class="busca cresce" type="search" x-model="busca" placeholder="Filtrar por nome, numero, ano ou data">
        <a class="botao botao-primario" href="/ciclo/form">Novo ciclo</a>
    </div>

    <?php if (count($ciclos) === 0): ?>

        <div class="cartao vazio">
            <p>Nenhum ciclo cadastrado ainda.</p>
            <p><a class="botao botao-contorno" href="/ciclo/form">Cadastrar o primeiro</a></p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Ciclo</th>
                        <th>Numero</th>
                        <th>Ano</th>
                        <th>Inicio</th>
                        <th>Termino</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ciclos as $ciclo): ?>
                        <tr data-busca="<?= Security::escape($termos[$ciclo->id]) ?>" x-show="casa($el)">
                            <td>
                                <?= Security::escape((string) $ciclo->nome) ?>
                                <?php if ($ciclo->estaVigente($hoje)): ?>
                                    <span class="selo-vigente">vigente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= Security::escape((string) $ciclo->num_ciclo) ?></td>
                            <td><?= Security::escape((string) $ciclo->num_ano) ?></td>
                            <td><?= Security::escape((string) $ciclo->data_inicio?->format('d/m/Y')) ?></td>
                            <td><?= Security::escape((string) $ciclo->data_termino?->format('d/m/Y')) ?></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/ciclo/form/<?= (int) $ciclo->id ?>">Editar</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/ciclo/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <input type="hidden" name="id" value="<?= (int) $ciclo->id ?>">
                                        <button class="botao botao-perigo" x-text="confirmando ? 'Excluir mesmo?' : 'Excluir'">Excluir</button>
                                        <button type="button" class="botao botao-fantasma" x-show="confirmando" x-cloak @click="cancelar()">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="vazio" x-show="!achou" x-cloak>Nenhum ciclo bate com esse filtro.</p>
        </div>

    <?php endif; ?>

</div>