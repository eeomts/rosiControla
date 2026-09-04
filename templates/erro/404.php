<?php

/**
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$caminho = (string) $view->getParam('caminho');
?>

<div class="cartao">
    <p class="vazio">
        <?php if ($caminho !== ''): ?>
            Nao existe nenhuma tela chamada <strong><?= Security::escape($caminho) ?></strong>.
        <?php else: ?>
            Essa pagina nao existe.
        <?php endif; ?>
    </p>

    <p class="dica">Talvez o endereco tenha sido digitado errado, ou a tela ainda nao foi feita.</p>

    <div class="acoes">
        <a class="botao botao-primario" href="/">Voltar para o inicio</a>
    </div>
</div>
