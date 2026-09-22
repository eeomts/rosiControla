<?php

/**
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);
$pedido = $view->getParam('pedido');
$ciclos = (array) $view->getParam('ciclos', []);
$produtos = (array) $view->getParam('produtos', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

$selecionado = static fn(string $campo, $chave): string
    => (string) ($valores[$campo] ?? '') === (string) $chave ? 'selected' : '';

?>
<div class="modal-corpo">

<form method="post" action="/pedido/salvar">

       <?php if ($id !== null): ?>
              <input type="hidden" name="id" value="<?= (int) $id ?>">
       <?php endif; ?>

       <div class="dupla">
              <div class="campo <?= $erro('fk_ciclo') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_ciclo">Ciclo</label>
                     <select id="fk_ciclo" name="fk_ciclo" required>
                            <option value="">Escolha o ciclo</option>
                            <?php foreach ($ciclos as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>" <?= $selecionado('fk_ciclo', $chave) ?>>
                                          <?= Security::escape((string) $nome) ?>
                                   </option>
                            <?php endforeach; ?>
                     </select>
                     <?php if ($erro('fk_ciclo') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('fk_ciclo')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('data_pedido') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="data_pedido">Data do pedido</label>
                     <input id="data_pedido" name="data_pedido" type="date"
                            value="<?= $valor('data_pedido') ?>" required>
                     <?php if ($erro('data_pedido') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('data_pedido')) ?></p>
                     <?php endif; ?>
              </div>
       </div>

       <div class="campo <?= $erro('nome') !== '' ? 'campo-invalido' : '' ?>">
              <label for="nome">Nome</label>
              <!-- so o placeholder: quem monta o nome padrao e o PedidoService -->
              <input id="nome" name="nome" type="text" maxlength="40"
                     value="<?= $valor('nome') ?>" placeholder="C12-08-1">
              <p class="dica">Deixe em branco para o sistema montar (ciclo, mes e a ordem no ciclo).</p>
              <?php if ($erro('nome') !== ''): ?>
                     <p class="erro"><?= Security::escape($erro('nome')) ?></p>
              <?php endif; ?>
       </div>

       <div class="barra">
              <button class="botao botao-primario" type="submit">Salvar cabecalho</button>
       </div>

</form>

<?php if ($pedido === null): ?>

       <p class="dica">Salve o cabecalho para comecar a lancar os produtos deste pedido.</p>

<?php else: ?>

       <!-- ------------------------------------------------- os produtos -->

       <!-- lancar e o +/- postam por fetch; o servidor devolve so o unidades.php, trocado no x-ref=alvo -->
       <div x-data="unidadesPedido" @submit="enviar($event)">
       <p class="erro" x-show="falha" x-text="falha" x-cloak></p>

       <h2>Lancar produto</h2>

       <!-- data-limpar: deu certo, o form zera para o proximo produto -->
       <form method="post" action="/pedido/adicionar" data-fragmento data-limpar>
              <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">

              <div class="campo <?= $erro('fk_produto') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_produto">Produto</label>
                     <select id="fk_produto" name="fk_produto" required>
                            <option value="">Escolha o produto</option>
                            <?php foreach ($produtos as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>"><?= Security::escape((string) $nome) ?></option>
                            <?php endforeach; ?>
                     </select>
                     <?php
                     $rapidoUrl = '/produto/rapido';
                     $rapidoAlvo = 'fk_produto';
                     $rapidoTitulo = 'Novo produto';
                     $rapidoRotulo = 'Nome do produto';
                     $rapidoDica = 'So o nome agora. Codigo e genero entram depois, na tela de produtos.';

                     include __DIR__ . '/../componentes/cadastro-rapido.php';
                     ?>
                     <?php if ($erro('fk_produto') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('fk_produto')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="dupla">
                     <div class="campo <?= $erro('quantidade') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="quantidade">Quantidade</label>
                            <input id="quantidade" name="quantidade" type="number" min="1" max="999"
                                   inputmode="numeric" value="1" required>
                            <p class="dica">Cada unidade vira uma linha no estoque.</p>
                            <?php if ($erro('quantidade') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('quantidade')) ?></p>
                            <?php endif; ?>
                     </div>

                     <div class="campo <?= $erro('data_validade') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="data_validade">Validade</label>
                            <input id="data_validade" name="data_validade" type="date">
                            <?php if ($erro('data_validade') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('data_validade')) ?></p>
                            <?php endif; ?>
                     </div>
              </div>

              <div class="dupla">
                     <div class="campo <?= $erro('mon_custo') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="mon_custo">Custo (o que voce pagou)</label>
                            <input id="mon_custo" name="mon_custo" type="text" inputmode="numeric" placeholder="0,00" x-moeda required>
                            <?php if ($erro('mon_custo') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('mon_custo')) ?></p>
                            <?php endif; ?>
                     </div>

                     <div class="campo <?= $erro('mon_venda') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="mon_venda">Venda (o que vai cobrar)</label>
                            <input id="mon_venda" name="mon_venda" type="text" inputmode="numeric" placeholder="0,00" x-moeda required>
                            <?php if ($erro('mon_venda') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('mon_venda')) ?></p>
                            <?php endif; ?>
                     </div>
              </div>

              <div class="barra">
                     <button class="botao botao-primario" type="submit" :disabled="ocupado">Adicionar ao pedido</button>
                     <span class="dica" x-show="recado" x-text="recado" x-cloak></span>
              </div>
       </form>

       <!-- ---------------------------------------------- o que ja entrou -->

       <h2>No pedido</h2>

              <div x-ref="alvo">
                     <?php include __DIR__ . "/unidades.php"; ?>
              </div>
       </div>

<?php endif; ?>

</div>

<div class="modal-rodape">
       <button type="button" class="botao botao-contorno" @click="fechar()">Fechar</button>
</div>
