<?php

/**
 * Casca do modal. Quem inclui define antes:
 *
 *   $modalTitulo   texto do cabecalho
 *   $modalCorpo    template do corpo, relativo a templates/ (traz o <form>)
 *   $modalTamanho  'sm' | 'md' | 'lg'   (padrao: md)
 *
 * O x-data="modal(...)" fica no elemento de FORA, na tela que inclui: assim o
 * botao que abre pode viver na barra da lista, fora do scrim.
 *
 * O corpo traz o <form> inteiro porque o rodape com o Salvar precisa estar
 * DENTRO dele para submeter.
 */

use Cubo\Security;

$modalTamanho = $modalTamanho ?? 'md';

?>
<div class="scrim" x-show="aberto" x-cloak
    @modal-travar="travado = $event.detail"
    @keydown.escape.window="fechar()"
    @click.self="fechar()">

    <div class="modal modal-<?= $modalTamanho ?>" x-ref="caixa" @keydown.tab="prender($event)"
        role="dialog" aria-modal="true">

        <div class="modal-topo">
            <p class="modal-titulo"><?= Security::escape((string) $modalTitulo) ?></p>
            <button type="button" class="modal-fechar" @click="fechar()" aria-label="Fechar">&times;</button>
        </div>

        <?php include __DIR__ . '/../' . $modalCorpo; ?>

    </div>

</div>
