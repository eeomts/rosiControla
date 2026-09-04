<?php

/**
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$vendas = $view->getParam('vendas', []);


$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;


$termos = [];

foreach ($vendas as $venda) {
    $termos[$venda->id] = mb_strtolower(
        $venda->cliente?->nome
            . ' ' . $venda->data_venda?->format('d/m/Y')
            . ' ' . $venda->statusPagamento?->nome
            . ' ' . $venda->statusEntrega?->nome
    );
}

?>
<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <div class="barra">
        <input class="busca cresce" type="search" x-model="busca" placeholder="Filtrar por cliente, data ou status">
        <a class="botao botao-primario" href="/venda/form">Nova venda</a>
    </div>

    <?php if (count($vendas) === 0): ?>

        <div class="cartao vazio">
            <p>Nenhuma venda registrada ainda.</p>
            <p><a class="botao botao-contorno" href="/venda/form">Registrar a primeira</a></p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Itens</th>
                        <th>Total</th>
                        <th>Pagamento</th>
                        <th>Entrega</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr data-busca="<?= Security::escape($termos[$venda->id]) ?>" x-show="casa($el)">
                            <td><?= Security::escape((string) $venda->cliente?->nome) ?></td>
                            <td><?= Security::escape((string) $venda->data_venda?->format('d/m/Y')) ?></td>
                            <td><?= (int) $venda->itens()->count() ?></td>
                            <td>
                                R$ <?= Security::escape((string) $venda->mon_total) ?>
                                <?php if ((float) $venda->mon_desconto > 0): ?>
                                    <span class="dica">-<?= Security::escape((string) $venda->mon_desconto) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= Security::escape((string) $venda->statusPagamento?->nome) ?></td>
                            <td><?= Security::escape((string) $venda->statusEntrega?->nome) ?></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/venda/form/<?= (int) $venda->id ?>">Editar</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/venda/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <input type="hidden" name="id" value="<?= (int) $venda->id ?>">
                                        <button class="botao botao-perigo" x-text="confirmando ? 'Excluir mesmo?' : 'Excluir'">Excluir</button>
                                        <button type="button" class="botao botao-fantasma" x-show="confirmando" x-cloak @click="cancelar()">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="vazio" x-show="!achou" x-cloak>Nenhuma venda bate com esse filtro.</p>
        </div>

    <?php endif; ?>

</div>
