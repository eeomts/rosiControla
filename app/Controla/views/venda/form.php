<?php

/**
 * Formulario de venda
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);
$itens = (array) $view->getParam('itens', []);
$estoque = (array) $view->getParam('estoque', []);
$clientes = (array) $view->getParam('clientes', []);
$statusPagamento = (array) $view->getParam('statusPagamento', []);
$statusEntrega = (array) $view->getParam('statusEntrega', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

// erro de item vem com a chave itens.<indice>; na tela viram um aviso so
$errosDeItem = [];

foreach ($erros as $campo => $mensagem) {
    if (str_starts_with((string) $campo, 'itens.')) {
        $errosDeItem[] = $mensagem;
    }
}

// o JSON vai dentro de um atributo HTML: aspas, & e <> escapados, senao o
// navegador decodifica entidade antes de o Alpine ler
$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

$selecionado = static fn(string $campo, $chave): string
    => (string) ($valores[$campo] ?? '') === (string) $chave ? 'selected' : '';

?>
<!-- o componente mora em /js/venda-form.js -->
<form class="cartao form" method="post" action="/venda/salvar" x-data='vendaForm(
       <?= json_encode($estoque, $emAtributo) ?>,
       <?= json_encode($itens, $emAtributo) ?>,
       <?= json_encode((string) ($valores['mon_desconto'] ?? '0,00'), $emAtributo) ?>
)'>

       <?php if ($id !== null): ?>
              <input type="hidden" name="id" value="<?= (int) $id ?>">
       <?php endif; ?>

       <div class="dupla">
              <div class="campo <?= $erro('fk_cliente') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_cliente">Cliente</label>
                     <select id="fk_cliente" name="fk_cliente" required>
                            <option value="">Escolha a cliente</option>
                            <?php foreach ($clientes as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>" <?= $selecionado('fk_cliente', $chave) ?>>
                                          <?= Security::escape((string) $nome) ?>
                                   </option>
                            <?php endforeach; ?>
                     </select>
                     <p class="dica">Cliente nova? <a href="/cliente/form">Cadastre aqui</a> e volte.</p>
                     <?php if ($erro('fk_cliente') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('fk_cliente')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('data_venda') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="data_venda">Data da venda</label>
                     <input id="data_venda" name="data_venda" type="date" value="<?= $valor('data_venda') ?>" required>
                     <?php if ($erro('data_venda') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('data_venda')) ?></p>
                     <?php endif; ?>
              </div>
       </div>

       <div class="dupla">
              <div class="campo <?= $erro('fk_status_pagamento') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_status_pagamento">Pagamento</label>
                     <select id="fk_status_pagamento" name="fk_status_pagamento" required>
                            <?php foreach ($statusPagamento as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>" <?= $selecionado('fk_status_pagamento', $chave) ?>>
                                          <?= Security::escape((string) $nome) ?>
                                   </option>
                            <?php endforeach; ?>
                     </select>
                     <?php if ($erro('fk_status_pagamento') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('fk_status_pagamento')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('fk_status_entrega') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="fk_status_entrega">Entrega</label>
                     <select id="fk_status_entrega" name="fk_status_entrega" required>
                            <?php foreach ($statusEntrega as $chave => $nome): ?>
                                   <option value="<?= (int) $chave ?>" <?= $selecionado('fk_status_entrega', $chave) ?>>
                                          <?= Security::escape((string) $nome) ?>
                                   </option>
                            <?php endforeach; ?>
                     </select>
                     <?php if ($erro('fk_status_entrega') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('fk_status_entrega')) ?></p>
                     <?php endif; ?>
              </div>
       </div>

       <!-- ---------------------------------------------------- o estoque -->

       <h2>Produtos</h2>

       <div class="campo">
              <label for="busca_produto">Buscar no estoque</label>
              <input id="busca_produto" type="search" x-model="busca" placeholder="Nome do produto">
       </div>

       <div class="rolagem">
              <table class="tabela">
                     <thead>
                            <tr>
                                   <th>Produto</th>
                                   <th>Ciclo</th>
                                   <th>Validade</th>
                                   <th>Preco</th>
                                   <th>Disponivel</th>
                                   <th></th>
                            </tr>
                     </thead>
                     <tbody>
                            <template x-for="grupo in grupos" :key="grupo.ids[0]">
                                   <tr>
                                          <td x-text="grupo.produto"></td>
                                          <td x-text="grupo.ciclo"></td>
                                          <td x-text="grupo.validade || '-'"></td>
                                          <td x-text="'R$ ' + grupo.preco"></td>
                                          <td x-text="sobram(grupo) + ' de ' + grupo.ids.length"></td>
                                          <td>
                                                 <button type="button" class="botao botao-contorno"
                                                        :disabled="sobram(grupo) === 0"
                                                        @click="adicionar(grupo)">Adicionar</button>
                                          </td>
                                   </tr>
                            </template>
                     </tbody>
              </table>

              <p class="vazio" x-show="grupos.length === 0" x-cloak>Nada no estoque bate com essa busca.</p>
       </div>

       <!-- ------------------------------------------------ itens da venda -->

       <h2>Itens da venda</h2>

       <?php if ($errosDeItem !== []): ?>
              <?php foreach ($errosDeItem as $mensagem): ?>
                     <p class="erro"><?= Security::escape((string) $mensagem) ?></p>
              <?php endforeach; ?>
       <?php endif; ?>

       <?php if ($erro('itens') !== ''): ?>
              <p class="erro"><?= Security::escape($erro('itens')) ?></p>
       <?php endif; ?>

       <div class="rolagem">
              <table class="tabela">
                     <thead>
                            <tr>
                                   <th>Unidade</th>
                                   <th>Preco</th>
                                   <th>Desconto</th>
                                   <th>Subtotal</th>
                                   <th></th>
                            </tr>
                     </thead>
                     <tbody>
                            <template x-for="(item, i) in itens" :key="item.fk_variacao_produto">
                                   <tr>
                                          <td>
                                                 <span x-text="rotulo(item.fk_variacao_produto)"></span>
                                                 <input type="hidden" :name="'itens[' + i + '][fk_variacao_produto]'"
                                                        :value="item.fk_variacao_produto">
                                          </td>
                                          <td>
                                                 <input class="curto" type="text" inputmode="decimal"
                                                        :name="'itens[' + i + '][mon_venda]'" x-model="item.mon_venda">
                                          </td>
                                          <td>
                                                 <input class="curto" type="text" inputmode="decimal"
                                                        :name="'itens[' + i + '][mon_desconto]'" x-model="item.mon_desconto">
                                          </td>
                                          <td x-text="'R$ ' + moeda(liquido(item))"></td>
                                          <td>
                                                 <button type="button" class="botao botao-fantasma"
                                                        @click="remover(i)">Tirar</button>
                                          </td>
                                   </tr>
                            </template>
                     </tbody>
              </table>

              <p class="vazio" x-show="itens.length === 0" x-cloak>
                     Nenhum produto na venda ainda. Busque no estoque acima e clique em Adicionar.
              </p>
       </div>

       <!-- ------------------------------------------------------- fechar -->

       <div class="campo <?= $erro('mon_desconto') !== '' ? 'campo-invalido' : '' ?>">
              <label for="mon_desconto">Desconto na venda inteira</label>
              <input id="mon_desconto" name="mon_desconto" type="text" inputmode="decimal"
                     value="<?= $valor('mon_desconto') ?>" x-model="desconto">
              <p class="dica">E dividido entre os itens na proporcao do valor de cada um.</p>
              <?php if ($erro('mon_desconto') !== ''): ?>
                     <p class="erro"><?= Security::escape($erro('mon_desconto')) ?></p>
              <?php else: ?>
                     <!-- espelho do VendaService::validarItens() -->
                     <p class="erro" x-show="descontoPassaDoTotal" x-cloak>O desconto passa do total da venda.</p>
              <?php endif; ?>
       </div>

       <div class="cartao total">
              <p>Itens: <span x-text="'R$ ' + moeda(bruto)">R$ 0,00</span></p>
              <p>Desconto da venda: <span x-text="'- R$ ' + moeda(descontoDaVenda)">- R$ 0,00</span></p>
              <p><strong>Total: <span x-text="'R$ ' + moeda(total)">R$ 0,00</span></strong></p>
       </div>

       <div class="barra">
              <button class="botao botao-primario" type="submit">Salvar venda</button>
              <a class="botao botao-contorno" href="/venda">Cancelar</a>
       </div>

</form>
