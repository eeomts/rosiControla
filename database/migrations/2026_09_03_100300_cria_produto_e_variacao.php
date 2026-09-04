<?php

/**
 * O catalogo e o estoque.
 *
 * `produto` e a BASE: catalogo estavel, nao muda entre ciclos.
 * `variacao_produto` e UMA LINHA POR UNIDADE FISICA -- tres batons iguais no
 * mesmo pedido sao tres linhas. E o que permite vender uma e manter as outras
 * disponiveis sem controlar quantidade em coluna.
 */

declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('produto', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('nome', 160);
            $t->string('codigo_produto', 30)->nullable();
            $t->unsignedTinyInteger('fk_genero')->nullable();
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('nome', 'ix_produto_nome');
            $t->index('codigo_produto', 'ix_produto_codigo');
            $t->foreign('fk_genero', 'fk_produto_genero')->references('id')->on('genero_aux');
        });

        $schema->create('variacao_produto', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedInteger('fk_produto');
            $t->unsignedInteger('fk_pedido');
            $t->unsignedInteger('fk_ciclo');
            $t->date('data_validade')->nullable();
            $t->decimal('mon_custo', 10, 2);
            $t->decimal('mon_venda', 10, 2);
            $t->boolean('vendido')->default(0);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            // o indice de disponibilidade: e a pergunta que a tela de venda faz
            $t->index(['fk_produto', 'vendido', 'deleted'], 'ix_variacao_disponivel');
            // e o de agrupamento: a lista de pedido junta unidades identicas
            $t->index(
                ['fk_produto', 'fk_pedido', 'mon_custo', 'mon_venda', 'data_validade'],
                'ix_variacao_agrupamento'
            );
            $t->index('fk_pedido', 'ix_variacao_pedido');
            $t->index('fk_ciclo', 'ix_variacao_ciclo');

            $t->foreign('fk_produto', 'fk_variacao_produto_produto')->references('id')->on('produto');
            $t->foreign('fk_pedido', 'fk_variacao_produto_pedido')->references('id')->on('pedido');
            $t->foreign('fk_ciclo', 'fk_variacao_produto_ciclo')->references('id')->on('ciclo');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('variacao_produto');
        $schema->dropIfExists('produto');
    }
};
