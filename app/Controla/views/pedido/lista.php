<?php

/**
 * Lista de pedidos.
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$pedidos = $view->getParam('pedidos', []);

$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

$termos = [];

foreach ($pedidos as $pedido) {
    $termos[$pedido->id] = mb_strtolower(
        $pedido->nome . ' ' . $pedido->ciclo?->nome . ' ' . $pedido->data_pedido?->format('d/m/Y')
    );
}

?>
<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <div class="barra">
        <input class="busca cresce" type="search" x-model="busca" placeholder="Filtrar por nome, ciclo ou data">
        <a class="botao botao-primario" href="/pedido/form">Novo pedido</a>
    </div>

    <?php if (count($pedidos) === 0): ?>

        <div class="cartao vazio">
            <p>Nenhum pedido cadastrado ainda.</p>
            <p><a class="botao botao-contorno" href="/pedido/form">Cadastrar o primeiro</a></p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Ciclo</th>
                        <th>Data</th>
                        <th>Unidades</th>
                        <th>Custo</th>
                        <th>Lucro estimado</th>
                        <th>Lucro real</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr data-busca="<?= Security::escape($termos[$pedido->id]) ?>" x-show="casa($el)">
                            <td><?= Security::escape((string) $pedido->nome) ?></td>
                            <td><?= Security::escape((string) $pedido->ciclo?->nome) ?></td>
                            <td><?= Security::escape((string) $pedido->data_pedido?->format('d/m/Y')) ?></td>
                            <td><?= (int) $pedido->variacoes()->count() ?></td>
                            <td>R$ <?= Security::escape((string) $pedido->mon_total) ?></td>
                            <td>R$ <?= Security::escape((string) $pedido->mon_lucro_estimado) ?></td>
                            <td>R$ <?= Security::escape((string) $pedido->mon_lucro_real) ?></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/pedido/form/id/<?= (int) $pedido->id ?>">Abrir</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/pedido/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">
                                        <button class="botao botao-perigo" x-text="confirmando ? 'Excluir com as unidades?' : 'Excluir'">Excluir</button>
                                        <button type="button" class="botao botao-fantasma" x-show="confirmando" x-cloak @click="cancelar()">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="vazio" x-show="!achou" x-cloak>Nenhum pedido bate com esse filtro.</p>
        </div>

    <?php endif; ?>

</div>
