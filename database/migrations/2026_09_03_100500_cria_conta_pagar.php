<?php

/**
 * A aba Contas. A tabela existe desde o schema.sql; a feature ainda nao, entao
 * hoje o link /conta do menu cai no 404 de proposito.
 */

declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('conta_pagar', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedTinyInteger('fk_status_pagamento');
            $t->string('descricao', 160);
            $t->decimal('mon_valor', 10, 2);
            $t->date('data_vencimento');
            $t->date('data_pagamento')->nullable();
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('data_vencimento', 'ix_conta_pagar_vencimento');
            $t->foreign('fk_status_pagamento', 'fk_conta_pagar_status_pagamento')
                ->references('id')->on('status_pagamento_aux');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('conta_pagar');
    }
};
