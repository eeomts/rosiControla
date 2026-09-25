<?php


use Cubo\Security;

$categorias = (array) ($categorias ?? []);
$generos = (array) ($generos ?? []);

?>

<span x-data="cadastroEmpilhado('/produto/criar', 'produto-criado', { nome: '', codigo_produto: '', fk_categoria: '', fk_genero: '' })"
    @keydown.escape.window.stop="aberto && fechar()">

    <button type="button" class="botao botao-contorno" @click="abrir()">+ Novo</button>

    <div class="scrim scrim-empilhado" x-show="aberto" x-cloak @click.self="fechar()">

        <div class="modal modal-md" x-ref="caixa" @keydown.tab="prender($event)"
            role="dialog" aria-modal="true">

            <div class="modal-topo">
                <p class="modal-titulo">Novo produto</p>
                <button type="button" class="botao botao-primario botao-icone" @click="fechar()"
                    title="Fechar" aria-label="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            
            <div class="modal-corpo" @keydown.enter.prevent="salvar()">

                <p class="erro" x-show="erro !== ''" x-cloak x-text="erro"></p>

                <div class="campo" :class="erros.nome ? 'campo-invalido' : ''">
                    <label for="empilhado-nome">Nome</label>
                    <input id="empilhado-nome" type="text" maxlength="160" x-ref="primeiro" x-model="dados.nome">
                    <p class="erro" x-show="erros.nome" x-cloak x-text="erros.nome"></p>
                </div>

                <div class="campo" :class="erros.fk_categoria ? 'campo-invalido' : ''">
                    <label for="empilhado-categoria">Categoria</label>
                    <select id="empilhado-categoria" x-model="dados.fk_categoria">
                        <option value="">Escolha a categoria</option>
                        <?php foreach ($categorias as $chave => $nome): ?>
                            <option value="<?= (int) $chave ?>"><?= Security::escape((string) $nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="erro" x-show="erros.fk_categoria" x-cloak x-text="erros.fk_categoria"></p>
                </div>

                <div class="dupla">
                    <div class="campo" :class="erros.codigo_produto ? 'campo-invalido' : ''">
                        <label for="empilhado-codigo">Codigo</label>
                        <input id="empilhado-codigo" type="text" maxlength="30" x-model="dados.codigo_produto">
                        <p class="dica">Opcional. O que vem na revista da Natura.</p>
                        <p class="erro" x-show="erros.codigo_produto" x-cloak x-text="erros.codigo_produto"></p>
                    </div>

                    <div class="campo" :class="erros.fk_genero ? 'campo-invalido' : ''">
                        <label for="empilhado-genero">Genero</label>
                        <select id="empilhado-genero" x-model="dados.fk_genero">
                            <option value="">Nao definido</option>
                            <?php foreach ($generos as $chave => $nome): ?>
                                <option value="<?= (int) $chave ?>"><?= Security::escape((string) $nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="erro" x-show="erros.fk_genero" x-cloak x-text="erros.fk_genero"></p>
                    </div>
                </div>

            </div>

            <div class="modal-rodape">
                <button type="button" class="botao botao-contorno" @click="fechar()">Cancelar</button>
                <button type="button" class="botao botao-primario" @click="salvar()"
                    :disabled="salvando || dados.nome.trim() === ''"
                    x-text="salvando ? 'Salvando...' : 'Salvar'">Salvar</button>
            </div>

        </div>

    </div>

</span>
