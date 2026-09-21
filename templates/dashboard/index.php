<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Moeda;
use Cubo\Security;

$ciclo = $view->getParam('ciclo');
$vigente = (bool) $view->getParam('vigente');
$dias = $view->getParam('dias');
$comeca = $view->getParam('comeca');
$receber = (array) $view->getParam('receber', []);
$estoque = (array) $view->getParam('estoque', []);
$entregas = (int) $view->getParam('entregas');
$vendas = $view->getParam('vendas', []);

$lucroReal = (float) ($estoque['lucro_real'] ?? 0);
$lucroEstimado = (float) ($estoque['lucro_estimado'] ?? 0);
$falta = $lucroEstimado - $lucroReal;

?>

<!-- ------------------------------------------------------- o ciclo de hoje -->

<?php if ($ciclo === null): ?>

    <div class="cartao vazio">
        <p>Nenhum ciclo cadastrado ainda. E por ele que tudo comeca.</p>
        <p><a class="botao botao-primario" href="/ciclo/form">Cadastrar o primeiro ciclo</a></p>
    </div>

<?php else: ?>

    <div class="cartao faixa-ciclo <?= $vigente ? '' : 'faixa-alerta' ?>">
        <div>
            <p class="overline"><?= $vigente ? 'Ciclo vigente' : 'Nenhum ciclo vigente' ?></p>
            <p class="faixa-titulo"><?= Security::escape((string) $ciclo->nome) ?></p>
            <p class="dica">
                <?= Security::escape((string) $ciclo->data_inicio?->format('d/m/Y')) ?>
                ate
                <?= Security::escape((string) $ciclo->data_termino?->format('d/m/Y')) ?>
            </p>
        </div>

        <div class="faixa-prazo">
            <?php if ($vigente && $dias !== null): ?>
                <span class="numero"><?= (int) $dias ?></span>
                <span class="rotulo"><?= $dias === 1 ? 'dia restante' : 'dias restantes' ?></span>
            <?php elseif ($comeca !== null && $comeca > 0): ?>
                <!-- nao vigente com inicio no futuro: o ciclo ainda vem, nao acabou -->
                <span class="numero"><?= (int) $comeca ?></span>
                <span class="rotulo"><?= $comeca === 1 ? 'dia para comecar' : 'dias para comecar' ?></span>
            <?php elseif ($dias !== null): ?>
                <span class="numero"><?= abs((int) $dias) ?></span>
                <span class="rotulo">dias desde o termino</span>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<!-- ------------------------------------------------------------ indicadores -->

<div class="paineis">

    <div class="cartao indicador">
        <p class="overline">A receber</p>
        <p class="numero">R$ <?= Moeda::brl($receber['total'] ?? 0) ?></p>
        <p class="rotulo">
            <?= (int) ($receber['vendas'] ?? 0) ?> venda(s) em aberto,
            <?= (int) ($receber['clientes'] ?? 0) ?> cliente(s)
        </p>
        <a class="botao botao-contorno" href="/venda">Ver vendas</a>
    </div>

    <div class="cartao indicador">
        <p class="overline">Estoque parado</p>
        <p class="numero"><?= (int) ($estoque['unidades'] ?? 0) ?></p>
        <p class="rotulo">
            unidade(s) disponivel(is), R$ <?= Moeda::brl($estoque['valor'] ?? 0) ?> a preco de venda
        </p>
        <a class="botao botao-contorno" href="/produto">Ver produtos</a>
    </div>

    <div class="cartao indicador">
        <p class="overline">Lucro <?= $vigente ? 'do ciclo' : 'de todos os pedidos' ?></p>
        <p class="numero">R$ <?= Moeda::brl($lucroReal) ?></p>
        <p class="rotulo">
            <?php if ($falta > 0): ?>
                faltam R$ <?= Moeda::brl($falta) ?> para o estimado (R$ <?= Moeda::brl($lucroEstimado) ?>)
            <?php else: ?>
                estimado alcancado (R$ <?= Moeda::brl($lucroEstimado) ?>)
            <?php endif; ?>
        </p>
        <a class="botao botao-contorno" href="/pedido">Ver pedidos</a>
    </div>

    <div class="cartao indicador">
        <p class="overline">Entregas pendentes</p>
        <p class="numero"><?= $entregas ?></p>
        <p class="rotulo">venda(s) fechada(s) que ainda nao foram entregues</p>
        <a class="botao botao-contorno" href="/venda">Ver vendas</a>
    </div>

</div>

<!-- ---------------------------------------------------------- ultimas vendas -->

<h2>Ultimas vendas</h2>

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
                    <th>Total</th>
                    <th>Pagamento</th>
                    <th>Entrega</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendas as $venda): ?>
                    <?php
                    $pago = (string) $venda->statusPagamento?->nome === 'Pago';
                    $entregue = (string) $venda->statusEntrega?->nome === 'Entregue';
                    ?>
                    <tr>
                        <td><?= Security::escape((string) $venda->cliente?->nome) ?></td>
                        <td><?= Security::escape((string) $venda->data_venda?->format('d/m/Y')) ?></td>
                        <td>R$ <?= Moeda::brl($venda->mon_total) ?></td>
                        <td>
                            <span class="selo <?= $pago ? 'selo-ok' : 'selo-atencao' ?>">
                                <?= Security::escape((string) $venda->statusPagamento?->nome) ?>
                            </span>
                        </td>
                        <td>
                            <span class="selo <?= $entregue ? 'selo-ok' : 'selo-atencao' ?>">
                                <?= Security::escape((string) $venda->statusEntrega?->nome) ?>
                            </span>
                        </td>
                        <td>
                            <div class="acoes">
                                <a class="botao botao-contorno" href="/venda/form/<?= (int) $venda->id ?>">Abrir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
