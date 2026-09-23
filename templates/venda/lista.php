<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Controla\Utils\Moeda;
use Cubo\Security;

$vendas = $view->getParam('vendas', []);
$id = $view->getParam('id');
$modalAberto = (bool) $view->getParam('modal_aberto', false);


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
<?php $view->addParam('titulo_acao', <<<'HTML'
<a class="botao botao-primario" href="/venda/form">Nova venda</a>
HTML); ?>
<div x-data="modal(<?= $modalAberto ? 'true' : 'false' ?>)">

<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <?php
    // a busca instantanea entra na mesma linha dos filtros; o x-model vale
    // porque o form do filtro fica dentro deste x-data
    $view->addParam('filtro_extra', <<<'HTML'
    <div class="campo cresce">
        <label for="busca-lista">Busca rapida</label>
        <!-- Enter aqui recarregaria a pagina (o form e GET) e perderia o que ela digitou -->
        <input id="busca-lista" class="busca" type="search" x-model="busca"
            @keydown.enter.prevent placeholder="Cliente, data ou status">
    </div>
    HTML);

    include __DIR__ . '/../componentes/filtro.php';
    ?>

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
                                R$ <?= Moeda::brl($venda->mon_total) ?>
                                <?php if ((float) $venda->mon_desconto > 0): ?>
                                    <span class="dica">-R$ <?= Moeda::brl($venda->mon_desconto) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="selo <?= (string) $venda->statusPagamento?->nome === 'Pago' ? 'selo-ok' : 'selo-atencao' ?>">
                                    <?= Security::escape((string) $venda->statusPagamento?->nome) ?>
                                </span>
                            </td>
                            <td>
                                <span class="selo <?= (string) $venda->statusEntrega?->nome === 'Entregue' ? 'selo-ok' : 'selo-atencao' ?>">
                                    <?= Security::escape((string) $venda->statusEntrega?->nome) ?>
                                </span>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/venda/form/<?= (int) $venda->id ?>">Editar</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/venda/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <?= Csrf::daGlobal()->campo() ?>
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

<?php if ($modalAberto): ?>
<?php
$modalTitulo = $id === null ? 'Nova venda' : 'Editar venda';
$modalCorpo = 'venda/form.php';
$modalTamanho = 'lg';

include __DIR__ . '/../componentes/modal.php';
?>
<?php endif; ?>

</div>
