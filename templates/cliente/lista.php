<?php

/**
 * Lista de clientes.
 *
 * @var Cubo\View\View $view
 */

use Cubo\Security;

$clientes = $view->getParam('clientes', []);

// o JSON vai dentro de um atributo HTML: aspas, & e <> escapados, senao o
// navegador decodifica entidade antes de o Alpine ler
$emAtributo = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;

// indice do filtro: uma string por linha, ja em minusculas, para o Alpine so
// comparar com includes(). O telefone entra duas vezes -- cru e com mascara --
// para achar tanto quem digita 11999 quanto quem digita (11) 99999
$termos = [];

foreach ($clientes as $cliente) {
    $termos[$cliente->id] = mb_strtolower(
        $cliente->nome . ' ' . $cliente->telefone . ' ' . $cliente->telefoneFormatado()
    );
}

?>
<div x-data='listaFiltravel(<?= json_encode(array_values($termos), $emAtributo) ?>)'>

    <div class="barra">
        <input class="busca cresce" type="search" x-model="busca" placeholder="Filtrar por nome ou telefone">
        <a class="botao botao-primario" href="/cliente/form">Nova cliente</a>
    </div>

    <?php if (count($clientes) === 0): ?>

        <div class="cartao vazio">
            <p>Nenhuma cliente cadastrada ainda.</p>
            <p><a class="botao botao-contorno" href="/cliente/form">Cadastrar a primeira</a></p>
        </div>

    <?php else: ?>

        <div class="cartao rolagem">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr data-busca="<?= Security::escape($termos[$cliente->id]) ?>" x-show="casa($el)">
                            <td><?= Security::escape((string) $cliente->nome) ?></td>
                            <td>
                                <?php if ($cliente->telefone !== null): ?>
                                    <?= Security::escape($cliente->telefoneFormatado()) ?>
                                <?php else: ?>
                                    <span class="dica">sem telefone</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-contorno" href="/cliente/form/<?= (int) $cliente->id ?>">Editar</a>

                                    <!-- sem JS o form posta de primeira; com Alpine o 1o clique arma a confirmacao -->
                                    <form method="post" action="/cliente/excluir"
                                        x-data="confirmacao"
                                        @submit="armar($event)"
                                        @click.outside="cancelar()">
                                        <input type="hidden" name="id" value="<?= (int) $cliente->id ?>">
                                        <button class="botao botao-perigo" x-text="confirmando ? 'Excluir mesmo?' : 'Excluir'">Excluir</button>
                                        <button type="button" class="botao botao-fantasma" x-show="confirmando" x-cloak @click="cancelar()">Cancelar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="vazio" x-show="!achou" x-cloak>Nenhuma cliente bate com esse filtro.</p>
        </div>

    <?php endif; ?>

</div>
