<?php

/**
 * Modal de apoio, por cima de outro. Quem inclui define antes:
 *
 *   $rapidoUrl      endpoint que responde JSON  (ex.: /cliente/rapido)
 *   $rapidoAlvo     id do <select> que recebe a opcao nova
 *   $rapidoTitulo   texto do cabecalho
 *   $rapidoRotulo   label do unico campo
 *   $rapidoDica     linha de apoio embaixo do campo
 *
 * Nao e <form>: submeter recarregaria a pagina e a tela de tras perderia o que
 * ela ja montou. Quem salva e o fetch do componente.
 */

use Cubo\Security;

?>
<span x-data="cadastroRapido('<?= $rapidoUrl ?>', '<?= $rapidoAlvo ?>')" @keydown.escape.window.stop="aberto && fechar()">

    <button type="button" class="botao botao-contorno" @click="abrir()">+ Novo</button>

    <div class="scrim scrim-empilhado" x-show="aberto" x-cloak @click.self="fechar()">

        <div class="modal modal-sm" x-ref="caixa" @keydown.tab="prender($event)"
            role="dialog" aria-modal="true">

            <div class="modal-topo">
                <p class="modal-titulo"><?= Security::escape((string) $rapidoTitulo) ?></p>
                <button type="button" class="botao botao-primario botao-icone" @click="fechar()"
                    title="Fechar" aria-label="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-corpo">
                <div class="campo" :class="erro !== '' ? 'campo-invalido' : ''">
                    <label><?= Security::escape((string) $rapidoRotulo) ?></label>
                    <input type="text" maxlength="160" x-ref="campo" x-model="nome"
                        @keydown.enter.prevent="salvar()">
                    <p class="dica"><?= Security::escape((string) $rapidoDica) ?></p>
                    <p class="erro" x-show="erro !== ''" x-cloak x-text="erro"></p>
                </div>
            </div>

            <div class="modal-rodape">
                <button type="button" class="botao botao-contorno" @click="fechar()">Cancelar</button>
                <button type="button" class="botao botao-primario" @click="salvar()"
                    :disabled="salvando || nome.trim() === ''"
                    x-text="salvando ? 'Salvando...' : 'Salvar'">Salvar</button>
            </div>

        </div>

    </div>

</span>
