<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $view->escape('titulo') ?> - <?= $view->escape('sistema') ?></title>
    <link rel="icon" href="/favicon.ico" sizes="16x16">
    <link rel="stylesheet" href="/assets/css/app.css">
    <!-- os componentes ANTES do alpine: eles se registram no alpine:init, que o
         proprio alpine dispara ao rodar. defer roda na ordem do documento. -->
    <script defer src="/assets/js/controla.js"></script>
    <script defer src="/assets/js/venda-form.js"></script>
    <!-- defer e obrigatorio: o Alpine varre o DOM no load -->
    <script defer src="/assets/js/lib/alpine-3.16.2.min.js"></script>
</head>

<body>

    <header class="topo">
        <a class="marca" href="/"><?= $view->escape('sistema') ?></a>
        <nav class="menu">
            <a href="/ciclo">Ciclos</a>
            <a href="/pedido">Pedidos</a>
            <a href="/produto">Produtos</a>
            <a href="/venda">Vendas</a>
            <a href="/cliente">Clientes</a>
            <a href="/conta">Contas</a>
        </nav>
    </header>

    <main class="conteudo">
        <h1><?= $view->escape('titulo') ?></h1>

        <?php if ($view->getParam('flash') !== null): ?>
            <p class="aviso aviso-<?= $view->escape('flash_tipo') ?>"><?= $view->escape('flash') ?></p>
        <?php endif; ?>

        <?= $view->getParam('conteudo') ?>
    </main>

</body>

</html>