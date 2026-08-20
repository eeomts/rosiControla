<?php

/**
 * Formulario de ciclo: mesmo template para novo e para edicao.
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$id = $view->getParam('id');
$valores = (array) $view->getParam('valores', []);
$erros = (array) $view->getParam('erros', []);

$valor = static fn(string $campo): string => Security::escape((string) ($valores[$campo] ?? ''));
$erro = static fn(string $campo): string => (string) ($erros[$campo] ?? '');

// o JSON vai dentro de um atributo HTML: aspas, & e <> escapados, senao o
// navegador decodifica entidade antes de o Alpine ler
$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
$json = static fn(string $campo): string => json_encode((string) ($valores[$campo] ?? ''), $emAtributo);

?>
<form class="cartao form" method="post" action="/ciclo/salvar" x-data='cicloForm(
       <?= $json('num_ciclo') ?>, <?= $json('data_inicio') ?>, <?= $json('data_termino') ?>
)'>

       <?php if ($id !== null): ?>
              <input type="hidden" name="id" value="<?= (int) $id ?>">
       <?php endif; ?>

       <div class="dupla">
              <div class="campo <?= $erro('num_ciclo') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="num_ciclo">Numero do ciclo</label>
                     <input id="num_ciclo" name="num_ciclo" type="number" min="1" max="255" inputmode="numeric"
                            value="<?= $valor('num_ciclo') ?>" x-model="numero" required>
                     <?php if ($erro('num_ciclo') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('num_ciclo')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('num_ano') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="num_ano">Ano</label>
                     <input id="num_ano" name="num_ano" type="number" min="2000" max="2100" inputmode="numeric"
                            value="<?= $valor('num_ano') ?>" required>
                     <?php if ($erro('num_ano') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('num_ano')) ?></p>
                     <?php endif; ?>
              </div>
       </div>

       <div class="campo <?= $erro('nome') !== '' ? 'campo-invalido' : '' ?>">
              <label for="nome">Nome</label>
              <!-- so o placeholder: quem decide o nome padrao continua sendo o CicloService -->
              <input id="nome" name="nome" type="text" maxlength="60"
                     value="<?= $valor('nome') ?>" placeholder="Ciclo" :placeholder="sugestao">
              <p class="dica">Deixe em branco para o ciclo se chamar <span x-text="sugestao">Ciclo</span>.</p>
              <?php if ($erro('nome') !== ''): ?>
                     <p class="erro"><?= Security::escape($erro('nome')) ?></p>
              <?php endif; ?>
       </div>

       <div class="dupla">
              <div class="campo <?= $erro('data_inicio') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="data_inicio">Inicio</label>
                     <input id="data_inicio" name="data_inicio" type="date"
                            value="<?= $valor('data_inicio') ?>" x-model="inicio" required>
                     <?php if ($erro('data_inicio') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('data_inicio')) ?></p>
                     <?php endif; ?>
              </div>

              <div class="campo <?= $erro('data_termino') !== '' ? 'campo-invalido' : '' ?>">
                     <label for="data_termino">Termino</label>
                     <input id="data_termino" name="data_termino" type="date"
                            value="<?= $valor('data_termino') ?>" x-model="termino" required>
                     <?php if ($erro('data_termino') !== ''): ?>
                            <p class="erro"><?= Security::escape($erro('data_termino')) ?></p>
                     <?php else: ?>
                            <!-- espelho do CicloService::validar(); o else evita dois avisos iguais -->
                            <p class="erro" x-show="terminoAntes" x-cloak>O termino esta antes do inicio.</p>
                     <?php endif; ?>
              </div>
       </div>

       <div class="barra">
              <button class="botao botao-primario" type="submit">Salvar</button>
              <a class="botao botao-contorno" href="/ciclo">Cancelar</a>
       </div>

</form>