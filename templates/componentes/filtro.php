<?php

/**
 * A barra de filtros, desenhada a partir da Definicao da tela.
 *
 * Nenhuma tela escreve campo de filtro na mao: quem manda e o tipo do Campo.
 * O metodo e GET de proposito -- a tela filtrada vira um endereco que ela pode
 * favoritar, o voltar funciona e nao ha nada para o token de CSRF proteger.
 *
 * Espera os params filtro_definicao (Definicao) e filtro_valores (Valores).
 *
 * @var Cubo\View\View $view
 */

use Controla\Filtro\Campo;
use Controla\Filtro\Faixa;
use Controla\Filtro\Tipo;
use Cubo\Security;

$definicao = $view->getParam('filtro_definicao');
$valores = $view->getParam('filtro_valores');

if ($definicao === null || $definicao->vazia()) {
    return;
}

/** O que a usuaria escolheu, pronto para o atributo value. */
$escolhido = static function (Campo $campo) use ($valores): string {
    $valor = $valores?->para($campo);

    return is_string($valor) ? Security::escape($valor) : '';
};

$lado = static function (Campo $campo, string $qual) use ($valores): string {
    $faixa = $valores?->para($campo);

    if (!$faixa instanceof Faixa) {
        return '';
    }

    return Security::escape($qual === 'de' ? $faixa->de : $faixa->ate);
};

$marcado = static function (Campo $campo, int|string $chave) use ($valores): string {
    $valor = $valores?->para($campo);
    $lista = is_array($valor) ? $valor : [$valor];

    return in_array((string) $chave, array_map('strval', $lista), true) ? 'selected' : '';
};

?>
<form class="filtros" method="get">

    <?php foreach ($definicao->campos() as $campo): ?>
        <div class="campo">
            <label for="filtro-<?= Security::escape($campo->chave) ?>"><?= Security::escape($campo->rotulo) ?></label>

            <?php if ($campo->listaDeOpcoes() !== []): ?>

                <select id="filtro-<?= Security::escape($campo->chave) ?>"
                    name="<?= Security::escape($campo->chave) ?><?= $campo->multiplo ? '[]' : '' ?>"
                    <?= $campo->multiplo ? 'multiple' : '' ?>>
                    <?php if (!$campo->multiplo): ?>
                        <option value="">Todos</option>
                    <?php endif; ?>
                    <?php foreach ($campo->listaDeOpcoes() as $chave => $rotulo): ?>
                        <option value="<?= Security::escape((string) $chave) ?>" <?= $marcado($campo, $chave) ?>>
                            <?= Security::escape($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($campo->tipo->ehFaixa()): ?>

                <?php $tipoHtml = $campo->tipo === Tipo::Data ? 'date' : 'text'; ?>
                <div class="filtro-faixa">
                    <input id="filtro-<?= Security::escape($campo->chave) ?>" type="<?= $tipoHtml ?>"
                        name="<?= Security::escape($campo->chaveDe()) ?>" value="<?= $lado($campo, 'de') ?>"
                        <?= $campo->tipo === Tipo::Moeda ? 'x-moeda placeholder="0,00"' : '' ?>>
                    <span class="dica">ate</span>
                    <input type="<?= $tipoHtml ?>"
                        name="<?= Security::escape($campo->chaveAte()) ?>" value="<?= $lado($campo, 'ate') ?>"
                        <?= $campo->tipo === Tipo::Moeda ? 'x-moeda placeholder="0,00"' : '' ?>>
                </div>

            <?php else: ?>

                <input id="filtro-<?= Security::escape($campo->chave) ?>"
                    type="<?= $campo->tipo === Tipo::Numero ? 'number' : 'search' ?>"
                    name="<?= Security::escape($campo->chave) ?>" value="<?= $escolhido($campo) ?>">

            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="filtro-acoes">
        <button class="botao botao-primario" type="submit">Filtrar</button>

        <?php if ($valores !== null && !$valores->vazio()): ?>
            <!-- limpar e um link para a mesma tela sem query string -->
            <a class="botao botao-contorno" href="<?= Security::escape((string) $view->getParam('rota', '/')) ?>">Limpar</a>
        <?php endif; ?>
    </div>

</form>
