<?php

/**
 * Caixa + botao que abre um modal com uma grade para escolher UM item. O
 * escolhido vai num hidden: o form de volta recebe o mesmo campo que um select
 * mandaria. Quem inclui define antes:
 *
 *   $escolhaCampo    name do hidden                      (ex.: fk_produto)
 *   $escolhaTitulo   texto do botao e cabecalho do modal (ex.: Escolher produto)
 *   $escolhaVazio    o que a caixa mostra sem escolha
 *   $escolhaItens    lista de ['id' => ..., coluna => valor]
 *   $escolhaColunas  coluna => rotulo, na ordem da grade
 *   $escolhaLegenda  colunas que formam o texto do escolhido na caixa
 *   $escolhaBusca    placeholder da busca
 *   $escolhaEvento   evento de janela que traz um item novo ja escolhido (opcional)
 *   $escolhaValor    id que ja nasce escolhido (opcional)
 *
 * Nada dentro do modal tem name: ele esta dentro do <form> de volta e iria junto.
 */

use Cubo\Security;

$config = [
    'campo' => (string) $escolhaCampo,
    'vazio' => (string) $escolhaVazio,
    'itens' => array_values((array) $escolhaItens),
    'colunas' => array_map(
        static fn (string $chave, string $rotulo): array => ['chave' => $chave, 'rotulo' => $rotulo],
        array_keys((array) $escolhaColunas),
        array_values((array) $escolhaColunas),
    ),
    'legenda' => array_values((array) $escolhaLegenda),
    'evento' => (string) ($escolhaEvento ?? ''),
    'valor' => $escolhaValor ?? null,
];

$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

?>
<div class="escolha" x-data='escolha(<?= json_encode($config, $emAtributo) ?>)'
    @keydown.escape.window.stop="aberto && fechar()">

    <input type="hidden" name="<?= Security::escape($config['campo']) ?>" :value="escolhido ?? ''">

    <div class="escolha-atual" :class="escolhido === null ? 'escolha-vazia' : ''" x-text="legenda || config.vazio">
        <?= Security::escape($config['vazio']) ?>
    </div>

    <!-- data-primeiro: depois de lancar, o foco volta pra ca -->
    <button type="button" id="escolha-<?= Security::escape($config['campo']) ?>" class="botao botao-contorno"
        data-primeiro @click="abrir()"><?= Security::escape((string) $escolhaTitulo) ?></button>

    <div class="scrim scrim-empilhado" x-show="aberto" x-cloak @click.self="fechar()">

        <div class="modal modal-md" x-ref="caixa" @keydown.tab="prender($event)"
            role="dialog" aria-modal="true">

            <div class="modal-topo">
                <p class="modal-titulo"><?= Security::escape((string) $escolhaTitulo) ?></p>
                <button type="button" class="botao botao-primario botao-icone" @click="fechar()"
                    title="Fechar" aria-label="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-corpo">

                <!-- o Enter nao pode submeter o form de volta; com um resultado so, ele escolhe -->
                <input type="search" class="busca escolha-busca" x-ref="busca" x-model="busca"
                    placeholder="<?= Security::escape((string) $escolhaBusca) ?>"
                    @keydown.enter.prevent="visiveis.length === 1 && escolherJa(visiveis[0].id)">

                <div class="escolha-lista">
                    <table class="tabela escolha-tabela">
                        <thead>
                            <tr>
                                <th class="escolha-coluna-marca"></th>
                                <template x-for="coluna in config.colunas" :key="coluna.chave">
                                    <th x-text="coluna.rotulo"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in visiveis" :key="item.id">
                                <tr :class="classe(item)" @click="marcar(item.id)" @dblclick="escolherJa(item.id)">
                                    <td>
                                        <!-- radio com cara de checkbox: a escolha e de um so -->
                                        <input type="radio" class="escolha-radio" :checked="ehMarcado(item)"
                                            :aria-label="item[config.legenda[0]]"
                                            @change="marcar(item.id)" @keydown.enter.prevent="escolherJa(item.id)">
                                    </td>
                                    <template x-for="coluna in config.colunas" :key="coluna.chave">
                                        <td x-text="item[coluna.chave] || '-'"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <p class="vazio" x-show="visiveis.length === 0" x-cloak
                        x-text="itens.length === 0 ? 'Nenhum cadastrado ainda.' : 'Nada encontrado com essa busca.'"></p>
                </div>

                <p class="dica">Clique para marcar; duplo clique ja escolhe.</p>

            </div>

            <div class="modal-rodape">
                <button type="button" class="botao botao-contorno" @click="fechar()">Cancelar</button>
                <button type="button" class="botao botao-primario" @click="confirmar()"
                    :disabled="marcado === null"><?= Security::escape((string) $escolhaTitulo) ?></button>
            </div>

        </div>

    </div>

</div>
