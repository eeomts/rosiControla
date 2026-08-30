<?php

/**
 * Formulario de pedido, em dois passos.
 *
 * O cabecalho vem primeiro porque a unidade precisa saber a que pedido e ciclo
 * pertence; so depois de gravado e que o bloco de produtos aparece.
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);
$pedido = $view->getParam('pedido');
$unidades = (array) $view->getParam('unidades', []);
$ciclos = (array) $view->getParam('ciclos', []);
$produtos = (array) $view->getParam('produtos', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

$selecionado = static fn(string $campo, $chave): string
    => (string) ($valores[$campo] ?? '') === (string) $chave ? 'selected' : '';

?>
<form class="cartao form" method="post" action="/pedido/salvar">

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
              <button class="botao botao-primario" type="submit">Salvar</button>
              <a class="botao botao-contorno" href="/pedido">Voltar</a>
       </div>

</form>

<?php if ($pedido === null): ?>

       <p class="dica">Salve o cabecalho para comecar a lancar os produtos deste pedido.</p>

<?php else: ?>

       <!-- ------------------------------------------------- os produtos -->

       <h2>Lancar produto</h2>

       <form class="cartao form" method="post" action="/pedido/adicionar">
              <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">

              <div class="campo <?= $erro('fk_produto') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_produto">Produto</label>
                     <select id="fk_produto" name="fk_produto" required>
                            <option value="">Escolha o produto</option>
                            <?php foreach ($produtos as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>"><?= Security::escape((string) $nome) ?></option>
                            <?php endforeach; ?>
                     </select>
                     <p class="dica">Nao achou? <a href="/produto/form">Cadastre o produto</a> e volte.</p>
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
                            <input id="mon_custo" name="mon_custo" type="text" inputmode="decimal" required>
                            <?php if ($erro('mon_custo') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('mon_custo')) ?></p>
                            <?php endif; ?>
                     </div>

                     <div class="campo <?= $erro('mon_venda') !== '' ? 'campo-invalido' : '' ?>">
                            <label for="mon_venda">Venda (o que vai cobrar)</label>
                            <input id="mon_venda" name="mon_venda" type="text" inputmode="decimal" required>
                            <?php if ($erro('mon_venda') !== ''): ?>
                                   <p class="erro"><?= Security::escape($erro('mon_venda')) ?></p>
                            <?php endif; ?>
                     </div>
              </div>

              <div class="barra">
                     <button class="botao botao-primario" type="submit">Adicionar ao pedido</button>
              </div>
       </form>

       <!-- ---------------------------------------------- o que ja entrou -->

       <h2>No pedido</h2>

       <?php if ($unidades === []): ?>

              <div class="cartao vazio">
                     <p>Nenhum produto lancado neste pedido ainda.</p>
              </div>

       <?php else: ?>

              <div class="cartao rolagem">
                     <table class="tabela">
                            <thead>
                                   <tr>
                                          <th>Produto</th>
                                          <th>Qtd</th>
                                          <th>Validade</th>
                                          <th>Custo</th>
                                          <th>Venda</th>
                                          <th>Vendidas</th>
                                          <th></th>
                                   </tr>
                            </thead>
                            <tbody>
                                   <?php foreach ($unidades as $grupo): ?>
                                          <tr>
                                                 <td><?= Security::escape((string) $grupo['produto']) ?></td>
                                                 <td><?= count($grupo['ids']) ?></td>
                                                 <td><?= Security::escape((string) $grupo['validade']) ?: '-' ?></td>
                                                 <td>R$ <?= Security::escape((string) $grupo['custo']) ?></td>
                                                 <td>R$ <?= Security::escape((string) $grupo['venda']) ?></td>
                                                 <td><?= (int) $grupo['vendidas'] ?></td>
                                                 <td>
                                                        <?php if ($grupo['disponiveis'] !== []): ?>
                                                               <!-- tira UMA unidade do grupo, a primeira que ainda nao saiu -->
                                                               <form method="post" action="/pedido/remover">
                                                                      <input type="hidden" name="id" value="<?= (int) $pedido->id ?>">
                                                                      <input type="hidden" name="unidade" value="<?= (int) $grupo['disponiveis'][0] ?>">
                                                                      <button class="botao botao-fantasma">Tirar uma</button>
                                                               </form>
                                                        <?php else: ?>
                                                               <span class="dica">todas vendidas</span>
                                                        <?php endif; ?>
                                                 </td>
                                          </tr>
                                   <?php endforeach; ?>
                            </tbody>
                     </table>
              </div>

              <div class="cartao total">
                     <p>Custo do pedido: <strong>R$ <?= Security::escape((string) $pedido->mon_total) ?></strong></p>
                     <p>Lucro estimado: <strong>R$ <?= Security::escape((string) $pedido->mon_lucro_estimado) ?></strong></p>
                     <p>Lucro real ate agora: <strong>R$ <?= Security::escape((string) $pedido->mon_lucro_real) ?></strong></p>
              </div>

       <?php endif; ?>

<?php endif; ?>
