<?php

/**
 * O "No pedido": tabela de unidades e totais.
 * @var Cubo\View\View $view
 */

use Controla\Utils\Moeda;
use Cubo\Security;

$pedido = $view->getParam('pedido');
$unidades = (array) $view->getParam('unidades', []);
$aviso = (string) $view->getParam('aviso', '');

?>
<?php if ($pedido === null): ?>

    <p class="erro"><?= Security::escape($aviso !== '' ? $aviso : 'Esse pedido nao existe mais.') ?></p>

<?php else: ?>

<!-- os data-* sao para a lista atras do modal acompanhar sem recarregar -->
<div class="unidades"
    data-pedido="<?= (int) $pedido->id ?>"
    data-unidades="<?= array_sum(array_map(static fn(array $g): int => count($g['ids']), $unidades)) ?>"
    data-total="R$ <?= Moeda::brl($pedido->mon_total) ?>"
    data-lucro-estimado="R$ <?= Moeda::brl($pedido->mon_lucro_estimado) ?>"
    data-lucro-real="R$ <?= Moeda::brl($pedido->mon_lucro_real) ?>">

    <?php if ($aviso !== ''): ?>
        <p class="erro"><?= Security::escape($aviso) ?></p>
    <?php endif; ?>

    <?php if ($unidades === []): ?>

        <div class="cartao vazio">
            <p>Nenhum produto lancado neste pedido ainda.</p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Validade</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Vendidas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unidades as $grupo): ?>
                        <?php
                        // identifica o grupo entre uma troca e outra, para o foco voltar no mesmo botao
                        $chave = Security::escape(implode('|', [$grupo['fk_produto'], $grupo['custo'], $grupo['venda'], $grupo['validade']]));
                        ?>
                        <tr>
                            <td><?= Security::escape((string) $grupo['produto']) ?></td>
                            <td>
                                <div class="contador">
                                    <!-- tira UMA unidade: a primeira do grupo que ainda nao saiu -->
                                    <form method="post" action="/pedido/remover" data-fragmento>
                                        <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">
                                        <input type="hidden" name="unidade" value="<?= (int) ($grupo['disponiveis'][0] ?? 0) ?>">
                                        <button class="botao botao-contorno" title="Tirar uma unidade"
                                            data-foco="<?= $chave ?>|menos"
                                            <?= $grupo['disponiveis'] === [] ? 'disabled' : '' ?>>&minus;</button>
                                    </form>

                                    <span class="contador-numero"><?= count($grupo['ids']) ?></span>

                                    <!-- repete a MESMA unidade: mesmo produto, validade e precos -->
                                    <form method="post" action="/pedido/adicionar" data-fragmento>
                                        <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">
                                        <input type="hidden" name="fk_produto" value="<?= (int) $grupo['fk_produto'] ?>">
                                        <input type="hidden" name="quantidade" value="1">
                                        <input type="hidden" name="data_validade" value="<?= Security::escape((string) $grupo['validade']) ?>">
                                        <input type="hidden" name="mon_custo" value="<?= Security::escape((string) $grupo['custo']) ?>">
                                        <input type="hidden" name="mon_venda" value="<?= Security::escape((string) $grupo['venda']) ?>">
                                        <button class="botao botao-contorno" title="Adicionar mais uma"
                                            data-foco="<?= $chave ?>|mais">+</button>
                                    </form>
                                </div>
                            </td>
                            <td><?= Security::escape((string) $grupo['validade']) ?: '-' ?></td>
                            <td>R$ <?= Moeda::brl($grupo['custo']) ?></td>
                            <td>R$ <?= Moeda::brl($grupo['venda']) ?></td>
                            <td><?= (int) $grupo['vendidas'] ?></td>
                            <td>
                                <?php if ($grupo['disponiveis'] === []): ?>
                                    <span class="dica">todas vendidas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cartao total">
            <p>Custo do pedido: <strong>R$ <?= Moeda::brl($pedido->mon_total) ?></strong></p>
            <p>Lucro estimado: <strong>R$ <?= Moeda::brl($pedido->mon_lucro_estimado) ?></strong></p>
            <p>Lucro real ate agora: <strong>R$ <?= Moeda::brl($pedido->mon_lucro_real) ?></strong></p>
        </div>

    <?php endif; ?>

</div>

<?php endif; ?>
