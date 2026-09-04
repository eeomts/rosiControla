<?php



declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('cliente', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('nome', 120);
            $t->string('telefone', 20)->nullable();
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('nome', 'ix_cliente_nome');
        });

        $schema->create('venda', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedInteger('fk_cliente');
            $t->unsignedTinyInteger('fk_status_pagamento');
            $t->unsignedTinyInteger('fk_status_entrega');
            $t->date('data_venda');
            $t->decimal('mon_total', 10, 2)->default(0);
            $t->decimal('mon_desconto', 10, 2)->default(0);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('fk_cliente', 'ix_venda_cliente');
            $t->index('data_venda', 'ix_venda_data');

            $t->foreign('fk_cliente', 'fk_venda_cliente')->references('id')->on('cliente');
            $t->foreign('fk_status_pagamento', 'fk_venda_status_pagamento')
                ->references('id')->on('status_pagamento_aux');
            $t->foreign('fk_status_entrega', 'fk_venda_status_entrega')
                ->references('id')->on('status_entrega_aux');
        });

        $schema->create('venda_variacao_rel', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedInteger('fk_venda');
            $t->unsignedInteger('fk_variacao_produto');
            $t->decimal('mon_venda', 10, 2);
            $t->decimal('mon_desconto', 10, 2)->default(0);
            $t->decimal('mon_desconto_rateio', 10, 2)->default(0);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('fk_venda', 'ix_venda_variacao_venda');
            $t->index(['fk_variacao_produto', 'deleted'], 'ix_venda_variacao_unidade');

            $t->foreign('fk_venda', 'fk_venda_variacao_venda')->references('id')->on('venda');
            $t->foreign('fk_variacao_produto', 'fk_venda_variacao_variacao_produto')
                ->references('id')->on('variacao_produto');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('venda_variacao_rel');
        $schema->dropIfExists('venda');
        $schema->dropIfExists('cliente');
    }
};
