<?php

/**
 * Corpo do modal de produto. Traz o <form> inteiro -- corpo e rodape.
 *
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);
$generos = (array) $view->getParam('generos', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

$selecionado = static fn(string $campo, $chave): string
    => (string) ($valores[$campo] ?? '') === (string) $chave ? 'selected' : '';

?>
<form method="post" action="/produto/salvar">
       <?= Csrf::daGlobal()->campo() ?>

       <div class="modal-corpo">

              <?php if ($id !== null): ?>
                     <input type="hidden" name="id" value="<?= (int) $id ?>">
              <?php endif; ?>

              <div class="campo <?= $erro('nome') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="nome">Nome</label>
                     <input id="nome" name="nome" type="text" maxlength="160"
                            value="<?= $valor('nome') ?>" required>
                     <?php if ($erro('nome') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('nome')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="dupla">
                     <div class="campo <?= $erro('codigo_produto') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="codigo_produto">Codigo</label>
                            <input id="codigo_produto" name="codigo_produto" type="text" maxlength="30"
                                   value="<?= $valor('codigo_produto') ?>">
                            <p class="dica">Opcional. O que vem na revista da Natura.</p>
                            <?php if ($erro('codigo_produto') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('codigo_produto')) ?></p>
                            <?php endif; ?>
                     </div>

                     <div class="campo <?= $erro('fk_genero') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="fk_genero">Genero</label>
                            <select id="fk_genero" name="fk_genero">
                                   <option value="">Nao definido</option>
                                   <?php foreach ($generos as $chave => $nome): ?>
                                          <option value="<?= (int) $chave ?>" <?= $selecionado('fk_genero', $chave) ?>>
                                                 <?= Security::escape((string) $nome) ?>
                                          </option>
                                   <?php endforeach; ?>
                            </select>
                            <?php if ($erro('fk_genero') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('fk_genero')) ?></p>
                            <?php endif; ?>
                     </div>
              </div>

       </div>

       <div class="modal-rodape">
              <button type="button" class="botao botao-contorno" @click="fechar()">Cancelar</button>
              <button class="botao botao-primario" type="submit">Salvar</button>
       </div>

</form>
