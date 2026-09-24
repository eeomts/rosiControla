<?php

/**
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);
$minSenha = (int) $view->getParam('min_senha', 8);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');
$classe = static fn(string $campo): string => $erro($campo) !== '' ? 'campo-invalido' : '';

?>
<p class="dica acesso-intro">
    Primeiro acesso: crie a conta que vai usar o sistema. Depois dela, esta tela nao abre mais.
</p>

<form method="post" action="/cadastro">

    <?= Csrf::daGlobal()->campo() ?>

    <div class="campo <?= $classe('nome') ?>">
        <label for="nome">Seu nome</label>
        <input id="nome" name="nome" type="text" maxlength="120" autocomplete="name"
               value="<?= $valor('nome') ?>" required autofocus>
        <?php if ($erro('nome') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('nome')) ?></p>
        <?php endif; ?>
    </div>

    <div class="campo <?= $classe('email') ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="160" autocomplete="email"
               value="<?= $valor('email') ?>" required>
        <p class="dica">O codigo de confirmacao chega nele.</p>
        <?php if ($erro('email') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('email')) ?></p>
        <?php endif; ?>
    </div>

    <div class="campo <?= $classe('senha') ?>">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" minlength="<?= $minSenha ?>" autocomplete="new-password" required>
        <p class="dica">Pelo menos <?= $minSenha ?> caracteres.</p>
        <?php if ($erro('senha') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('senha')) ?></p>
        <?php endif; ?>
    </div>

    <div class="campo <?= $classe('senha_confirmacao') ?>">
        <label for="senha_confirmacao">Repita a senha</label>
        <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required>
        <?php if ($erro('senha_confirmacao') !== ''): ?>
            <p class="erro"><?= Security::escape($erro('senha_confirmacao')) ?></p>
        <?php endif; ?>
    </div>

    <button class="botao botao-primario acesso-enviar" type="submit">Criar a conta</button>

</form>

<div class="acesso-rodape">
    <span class="dica">Ja tem conta?</span>
    <a class="botao botao-fantasma" href="/login">Entrar</a>
</div>
