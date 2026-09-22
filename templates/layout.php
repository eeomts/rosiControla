<?php

/**
 * @var Cubo\View\View $view
 */

use Cubo\Security;
use Cubo\Tools\Date;

$rota = '/' . trim((string) $view->getParam('rota', '/'), '/');
$usuario = (string) $view->getParam('usuario', '');

// o inicio so casa exato; o resto casa o comeco do caminho, para /ciclo/form
// manter "Ciclos" aceso
$itens = [
    '/' => 'Inicio',
    '/ciclo' => 'Ciclos',
    '/pedido' => 'Pedidos',
    '/produto' => 'Produtos',
    '/venda' => 'Vendas',
    '/cliente' => 'Clientes',
    '/conta' => 'Contas',
];

$ativo = static function (string $item) use ($rota): bool {
    return $item === '/' ? $rota === '/' : str_starts_with($rota, $item);
};

$meses = [
    1 => 'janeiro', 'fevereiro', 'marco', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
];

$hora = (int) Date::now('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $view->escape('titulo') ?> - <?= $view->escape('sistema') ?></title>
    <link rel="icon" href="/favicon.ico" sizes="16x16">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Climate+Crisis&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">

    <script defer src="/assets/js/controla.js"></script>
    <script defer src="/assets/js/venda-form.js"></script>

    <script defer src="/assets/js/lib/alpine-3.16.2.min.js"></script>
</head>

<body>

    <div class="app">

        <aside class="lateral">
            <a class="marca" href="/"><?= $view->escape('sistema') ?></a>

            <nav class="menu">
                <?php foreach ($itens as $caminho => $nome): ?>
                    <a href="<?= $caminho ?>" class="<?= $ativo($caminho) ? 'ativo' : '' ?>">
                        <?= Security::escape($nome) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="principal">

            <!-- PROVISORIO: nome e papel sao fixos ate o login existir -->
            <header class="topo">
                <div>
                    <p class="saudacao"><?= $saudacao ?>, <?= $view->escape('usuario') ?></p>
                    <p class="dica">
                        <?= (int) Date::now('d') ?> de <?= $meses[(int) Date::now('m')] ?>
                        de <?= (int) Date::now('Y') ?>
                    </p>
                </div>

                <div class="usuario">
                    <div class="usuario-dados">
                        <p class="usuario-nome"><?= $view->escape('usuario') ?></p>
                        <p class="dica"><?= $view->escape('usuario_papel') ?></p>
                    </div>
                    <span class="avatar"><?= Security::escape(mb_strtoupper(mb_substr($usuario, 0, 1))) ?></span>
                </div>
            </header>

            <main class="conteudo">
                <h1><?= $view->escape('titulo') ?></h1>

                <?php if ($view->getParam('flash') !== null): ?>
                    <p class="aviso aviso-<?= $view->escape('flash_tipo') ?>"><?= $view->escape('flash') ?></p>
                <?php endif; ?>

                <?= $view->getParam('conteudo') ?>
            </main>

        </div>

    </div>

</body>

</html>
