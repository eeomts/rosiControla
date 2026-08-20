<?php

namespace Controla\Tests\Support;

use Controla\Models\StatusEntrega;
use Controla\Models\StatusPagamento;
use Cubo\Database\Db;
use Illuminate\Database\Schema\Blueprint;

/**
 * Tabelas montadas em memoria para os testes.
 *
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
final class ControlaSchema
{
    
    public static function preparar(): void
    {
        Db::getInstance()->addConnection(Db::DEFAULT_CONNECTION, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        self::criar();
    }

    public static function criar(): void
    {
        $schema = Db::getInstance()->getConnection()->getSchemaBuilder();

        $schema->dropIfExists('genero_aux');
        $schema->create('genero_aux', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome', 30);
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('produto');
        $schema->create('produto', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome', 160);
            $table->string('codigo_produto', 30)->nullable();
            $table->integer('fk_genero')->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('cliente');
        $schema->create('cliente', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome', 120);
            $table->string('telefone', 20)->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('ciclo');
        $schema->create('ciclo', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome', 60);
            $table->integer('num_ciclo');
            $table->integer('num_ano');
            $table->date('data_inicio')->nullable();
            $table->date('data_termino')->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('pedido');
        $schema->create('pedido', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('fk_ciclo');
            $table->string('nome', 40);
            $table->date('data_pedido')->nullable();
            $table->decimal('mon_total', 10, 2)->default(0);
            $table->decimal('mon_lucro_estimado', 10, 2)->default(0);
            $table->decimal('mon_lucro_real', 10, 2)->default(0);
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('variacao_produto');
        $schema->create('variacao_produto', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('fk_produto');
            $table->integer('fk_pedido');
            $table->integer('fk_ciclo');
            $table->date('data_validade')->nullable();
            $table->decimal('mon_custo', 10, 2);
            $table->decimal('mon_venda', 10, 2);
            $table->integer('vendido')->default(0);
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        foreach (['status_pagamento_aux', 'status_entrega_aux'] as $aux) {
            $schema->dropIfExists($aux);
            $schema->create($aux, function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nome', 30);
                $table->timestamp('created')->nullable();
                $table->timestamp('updated')->nullable();
                $table->integer('deleted')->default(0);
            });
        }

        $schema->dropIfExists('venda');
        $schema->create('venda', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('fk_cliente');
            $table->integer('fk_status_pagamento');
            $table->integer('fk_status_entrega');
            $table->date('data_venda')->nullable();
            $table->decimal('mon_total', 10, 2)->default(0);
            $table->decimal('mon_desconto', 10, 2)->default(0);
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        $schema->dropIfExists('venda_variacao_rel');
        $schema->create('venda_variacao_rel', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('fk_venda');
            $table->integer('fk_variacao_produto');
            $table->decimal('mon_venda', 10, 2);
            $table->decimal('mon_desconto', 10, 2)->default(0);
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->integer('deleted')->default(0);
        });

        self::semear();
    }

    
    private static function semear(): void
    {
        StatusPagamento::create(['nome' => 'Nao pago']);
        StatusPagamento::create(['nome' => 'Pago']);

        StatusEntrega::create(['nome' => 'Nao entregue']);
        StatusEntrega::create(['nome' => 'Entregue']);
    }
}
