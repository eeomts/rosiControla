<?php
/**
 * Layout de fixture: imprime o miolo do mesmo jeito que o layout.php real.
 *
 * @var \Cubo\View\View $view
 */
echo '<main>' . $view->getParam('conteudo') . '</main>';
