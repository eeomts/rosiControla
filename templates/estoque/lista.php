<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Filtro\Valores;
use Controla\Types\Validade;
use Controla\Utils\Moeda;
use Cubo\Security;

$resumo = (array) $view->getParam('resumo', []);
$produtos = (array) $view->getParam('produtos', []);
$abertos = (bool) $view->getParam('abertos', false);
$filtros = $view->getParam('filtro_valores');
$filtrado = $filtros instanceof Valores && !$filtros->vazio();

$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

$termos = [];

foreach ($produtos as $produto) {
    $termos[$produto['id']] = mb_strtolower($produto['nome'] . ' ' . $produto['codigo'] . ' ' . $produto['categoria']);
}

$link = static function (?Validade $situacao) use ($filtros): string {
    $query = $filtros instanceof Valores ? $filtros->comoArray() : [];
    unset($query['situacao']);

    if ($situacao !== null) {
        $query['situacao'] = $situacao->value;
    }

    return '/estoque' . ($query === [] ? '' : '?' . http_build_query($query));
};

$data = static fn ($validade): string => $validade === null ? '' : $validade->format('d/m/Y');

$selo = static fn (Validade $situacao, ?int $dias): string
    => '<span class="selo ' . $situacao->selo() . '">' . Security::escape($situacao->texto($dias)) . '</span>';

?>
<div class="paineis estoque-resumo">

    <div class="cartao indicador">
        <p class="overline">Em estoque</p>
        <p class="numero"><?= (int) ($resumo['unidades'] ?? 0) ?></p>
        <p class="rotulo">unidade(s) disponivel(is)</p>
        <?php if ($filtrado): ?>
            <a class="botao botao-contorno" href="/estoque">Ver tudo</a>
        <?php endif; ?>
    </div>

    <div class="cartao indicador">
        <p class="overline">Vencidas</p>
        <p class="numero"><?= (int) ($resumo['vencidas'] ?? 0) ?></p>
        <p class="rotulo">unidade(s) com a validade vencida</p>
        <a class="botao botao-contorno" href="<?= Security::escape($link(Validade::Vencido)) ?>">Ver vencidas</a>
    </div>

    <div class="cartao indicador">
        <p class="overline">Vencem em ate <?= Validade::DIAS_BREVE ?> dias</p>
        <p class="numero"><?= (int) ($resumo['breve'] ?? 0) ?></p>
        <p class="rotulo">unidade(s) para sair primeiro</p>
        <a class="botao botao-contorno" href="<?= Security::escape($link(Validade::Breve)) ?>">Ver quais</a>
    </div>

    <div class="cartao indicador">
        <p class="overline">Valor em estoque</p>
        <p class="numero">R$ <?= Moeda::brl($resumo['venda'] ?? 0) ?></p>
        <p class="rotulo">
            custo R$ <?= Moeda::brl($resumo['custo'] ?? 0) ?>,
            lucro R$ <?= Moeda::brl($resumo['lucro'] ?? 0) ?>
        </p>
    </div>

</div>

<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <?php

    $view->addParam('filtro_extra', <<<'HTML'
    <div class="campo cresce">
        <label for="busca-lista">Busca rapida</label>
        <input id="busca-lista" class="busca" type="search" x-model="busca"
            @keydown.enter.prevent placeholder="Nome, codigo ou categoria">
    </div>
    HTML);

    include __DIR__ . '/../componentes/filtro.php';
    ?>

    <?php if (count($produtos) === 0): ?>

        <div class="cartao vazio">
            <?php if ($filtrado): ?>
                <p>Nada no estoque com esses filtros.</p>
                <p><a class="botao botao-contorno" href="/estoque">Ver tudo</a></p>
            <?php else: ?>
                <p>Nenhuma unidade em estoque. Elas entram pelo lancamento de produtos no pedido.</p>
                <p><a class="botao botao-contorno" href="/pedido">Ir para os pedidos</a></p>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela estoque">
                <thead>
                    <tr>
                        <th class="estoque-coluna-abrir"></th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Unid.</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Lucro</th>
                        <th>Validade</th>
                    </tr>
                </thead>

                <?php foreach ($produtos as $produto): ?>
                    <tbody x-data="{ aberto: <?= $abertos ? 'true' : 'false' ?> }"
                        data-busca="<?= Security::escape($termos[$produto['id']]) ?>" x-show="casa($el)">

                        <tr class="estoque-produto" @click="aberto = !aberto">
                            <td>
                                <button type="button" class="botao botao-fantasma estoque-abrir"
                                    :aria-expanded="(aberto || alvo !== '') ? 'true' : 'false'" aria-label="Ver os lotes">
                                    <i class="bi" :class="(aberto || alvo !== '') ? 'bi-chevron-down' : 'bi-chevron-right'" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td>
                                <strong><?= Security::escape($produto['nome']) ?></strong>
                                <?php if ($produto['codigo'] !== ''): ?>
                                    <span class="dica"><?= Security::escape($produto['codigo']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= Security::escape($produto['categoria'] !== '' ? $produto['categoria'] : '-') ?></td>
                            <td><?= (int) $produto['quantidade'] ?></td>
                            <td>R$ <?= Moeda::brl($produto['custo']) ?></td>
                            <td>R$ <?= Moeda::brl($produto['venda']) ?></td>
                            <td>R$ <?= Moeda::brl($produto['lucro']) ?></td>
                            <td>
                                <?= $selo($produto['situacao'], $produto['dias']) ?>
                                <span class="dica"><?= $data($produto['validade']) ?></span>
                            </td>
                        </tr>

                        <?php foreach ($produto['lotes'] as $lote): ?>
                            <tr class="estoque-lote" x-show="aberto || alvo !== ''" <?= $abertos ? '' : 'x-cloak' ?>>
                                <td></td>
                                <td>Pedido <?= Security::escape($lote['pedido']) ?></td>
                                <td><?= $lote['validade'] === null ? '-' : 'validade ' . $data($lote['validade']) ?></td>
                                <td><?= (int) $lote['quantidade'] ?></td>
                                <td>R$ <?= Moeda::brl($lote['custo']) ?> <span class="dica">cada</span></td>
                                <td>R$ <?= Moeda::brl($lote['venda']) ?> <span class="dica">cada</span></td>
                                <td>R$ <?= Moeda::brl($lote['venda'] - $lote['custo']) ?> <span class="dica">cada</span></td>
                                <td><?= $selo($lote['situacao'], $lote['dias']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>

        <p class="cartao vazio" x-show="!achou" x-cloak>Nada no estoque com essa busca.</p>

    <?php endif; ?>

</div>
