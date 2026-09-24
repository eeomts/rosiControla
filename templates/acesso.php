<?php

/**
 * Layout das telas de entrar: um cartao no meio, sem menu e sem cabecalho.
 *
 * @var Cubo\View\View $view
 */

$versao = static function (string $caminho): string {
    $arquivo = __DIR__ . '/../public' . $caminho;

    return $caminho . '?v=' . (is_file($arquivo) ? filemtime($arquivo) : '0');
};

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $view->escape('titulo') ?> - <?= $view->escape('sistema') ?></title>

    <link rel="icon" href="/favicon.ico" sizes="16x16">
    <link rel="icon" type="image/svg+xml" href="<?= $versao('/assets/img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Climate+Crisis&display=swap">
    <link rel="stylesheet" href="<?= $versao('/assets/icons/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= $versao('/assets/css/app.css') ?>">
</head>

<body>

    <main class="acesso">

        <div class="cartao acesso-cartao">
            <h1><?= $view->escape('titulo') ?></h1>

            <?php if ($view->getParam('flash') !== null): ?>
                <p class="aviso aviso-<?= $view->escape('flash_tipo') ?>"><?= $view->escape('flash') ?></p>
            <?php endif; ?>

            <?= $view->getParam('conteudo') ?>
        </div>

    </main>

</body>

</html>
