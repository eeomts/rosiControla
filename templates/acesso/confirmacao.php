<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$erros = (array) $view->getParam('erros', []);
$erro = (string) ($erros['codigo'] ?? '');

?>
<p class="dica acesso-intro">
    Digite o codigo de 6 digitos que chegou em <strong><?= $view->escape('email') ?></strong>.
    Ele vale por <?= (int) $view->getParam('minutos', 15) ?> minutos.
</p>

<form method="post" action="/confirmacao">

    <?= Csrf::daGlobal()->campo() ?>

    <div class="campo <?= $erro !== '' ? 'campo-invalido' : '' ?>">
        <label for="codigo">Codigo</label>
        <input id="codigo" name="codigo" class="acesso-codigo" type="text" inputmode="numeric"
               maxlength="7" autocomplete="one-time-code" placeholder="000000" required autofocus>
        <?php if ($erro !== ''): ?>
            <p class="erro"><?= Security::escape($erro) ?></p>
        <?php endif; ?>
    </div>

    <button class="botao botao-primario acesso-enviar" type="submit">Confirmar</button>

</form>

<form method="post" action="/confirmacao/reenviar" class="acesso-rodape">
    <?= Csrf::daGlobal()->campo() ?>
    <span class="dica">Nao chegou? Olhe o spam ou</span>
    <button class="botao botao-fantasma" type="submit">mande outro codigo</button>
</form>
