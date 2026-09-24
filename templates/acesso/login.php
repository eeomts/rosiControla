<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);

$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

?>
<form method="post" action="/login">

    <?= Csrf::daGlobal()->campo() ?>

    <div class="campo <?= $erro('email') !== '' ? 'campo-invalido' : '' ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="160" autocomplete="username"
               value="<?= Security::escape((string) ($valores['email'] ?? '')) ?>" required autofocus>
        <?php if ($erro('email') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('email')) ?></p>
        <?php endif; ?>
    </div>

    <div class="campo <?= $erro('senha') !== '' ? 'campo-invalido' : '' ?>">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="current-password" required>
        <?php if ($erro('senha') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('senha')) ?></p>
        <?php endif; ?>
    </div>

    <button class="botao botao-primario acesso-enviar" type="submit">Entrar</button>

</form>

<?php if ($view->getParam('cadastro_aberto', false)): ?>
    <div class="acesso-rodape">
        <span class="dica">Primeiro acesso?</span>
        <a class="botao botao-contorno acesso-enviar" href="/cadastro">Criar conta</a>
    </div>
<?php endif; ?>
