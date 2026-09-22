<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$produtos = $view->getParam('produtos', []);
$id = $view->getParam('id');
$modalAberto = (bool) $view->getParam('modal_aberto', false);

$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

$termos = [];

foreach ($produtos as $produto) {
    $termos[$produto->id] = mb_strtolower(
        $produto->nome . ' ' . $produto->codigo_produto . ' ' . $produto->genero?->nome
    );
}

?>
<?php $view->addParam('titulo_acao', <<<'HTML'
<button type="button" class="botao botao-primario" x-data @click="$dispatch('abrir-modal')">Novo produto</button>
HTML); ?>
<div x-data="modal(<?= $modalAberto ? 'true' : 'false' ?>)" @abrir-modal.window="abrir()">

<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <?php include __DIR__ . '/../componentes/filtro.php'; ?>

    <div class="barra">
        <input class="busca cresce" type="search" x-model="busca" placeholder="Filtrar por nome, codigo ou genero">
    </div>

    <?php if (count($produtos) === 0): ?>

        <div class="cartao vazio">
            <p>Nenhum produto cadastrado ainda.</p>
            <p><button type="button" class="botao botao-contorno" @click="abrir()">Cadastrar o primeiro</button></p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Codigo</th>
                        <th>Genero</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr data-busca="<?= Security::escape($termos[$produto->id]) ?>" x-show="casa($el)">
                            <td><?= Security::escape((string) $produto->nome) ?></td>
                            <td>
                                <?php if ($produto->codigo_produto !== null): ?>
                                    <?= Security::escape((string) $produto->codigo_produto) ?>
                                <?php else: ?>
                                    <span class="dica">sem codigo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= Security::escape((string) $produto->genero?->nome) ?></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/produto/form/<?= (int) $produto->id ?>">Editar</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/produto/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <?= Csrf::daGlobal()->campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $produto->id ?>">
                                        <button class="botao botao-perigo" x-text="confirmando ? 'Excluir mesmo?' : 'Excluir'">Excluir</button>
                                        <button type="button" class="botao botao-fantasma" x-show="confirmando" x-cloak @click="cancelar()">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="vazio" x-show="!achou" x-cloak>Nenhum produto bate com esse filtro.</p>
        </div>

    <?php endif; ?>

</div>

<?php

$modalTitulo = $id === null ? 'Novo produto' : 'Editar produto';
$modalCorpo = 'produto/form.php';
$modalTamanho = 'md';

include __DIR__ . '/../componentes/modal.php';
?>

</div>
