<?php

/**
 * Corpo do modal de cliente. Traz o <form> inteiro -- corpo e rodape.
 *
 * @var Cubo\View\View $view
 */

use Controla\Utils\Csrf;
use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
$json = static fn(string $campo): string => json_encode((string) ($valores[$campo] ?? ''), $emAtributo);

?>
<!--
  A mascara e SO da tela: o que trafega no POST pode ir com parenteses e traco,
  porque o ClienteService joga fora tudo que nao e digito antes de gravar.
-->
<form method="post" action="/cliente/salvar" x-data='telefone(<?= $json('telefone') ?>)'>

       <?= Csrf::daGlobal()->campo() ?>

       <div class="modal-corpo">

              <?php if ($id !== null): ?>
                     <input type="hidden" name="id" value="<?= (int) $id ?>">
              <?php endif; ?>

              <div class="campo <?= $erro('nome') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="nome">Nome</label>
                     <input id="nome" name="nome" type="text" maxlength="120"
                            value="<?= $valor('nome') ?>" required>
                     <?php if ($erro('nome') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('nome')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('telefone') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="telefone">Telefone</label>
                     <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="15"
                            placeholder="(11) 99999-8888"
                            value="<?= $valor('telefone') ?>" x-model="telefone" @input="mascarar()">
                     <p class="dica">Opcional. Fixo ou celular, sempre com DDD.</p>
                     <?php if ($erro('telefone') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('telefone')) ?></p>
                     <?php else: ?>
                            <!-- espelho do ClienteService::validar(); o else evita dois avisos iguais -->
                            <p class="erro" x-show="curto" x-cloak>Faltam digitos nesse telefone.</p>
                     <?php endif; ?>
              </div>

       </div>

       <div class="modal-rodape">
              <button type="button" class="botao botao-contorno" @click="fechar()">Cancelar</button>
              <button class="botao botao-primario" type="submit">Salvar</button>
       </div>

</form>
