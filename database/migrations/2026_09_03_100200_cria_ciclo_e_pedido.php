<?php



declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('ciclo', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('nome', 60);
            $t->unsignedTinyInteger('num_ciclo');
            $t->unsignedSmallInteger('num_ano');
            $t->date('data_inicio');
            $t->date('data_termino');
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index(['num_ciclo', 'num_ano'], 'ix_ciclo_num_ano');
        });

        $schema->create('pedido', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedInteger('fk_ciclo');
            $t->string('nome', 40);
            $t->date('data_pedido');
            $t->decimal('mon_total', 10, 2)->default(0);
            $t->decimal('mon_lucro_estimado', 10, 2)->default(0);
            $t->decimal('mon_lucro_real', 10, 2)->default(0);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('nome', 'ix_pedido_nome');
            $t->index('fk_ciclo', 'ix_pedido_ciclo');
            $t->foreign('fk_ciclo', 'fk_pedido_ciclo')->references('id')->on('ciclo');
        });
    }

    public function down(Schema $schema): void
    {
        // o filho primeiro: a FK do pedido impede derrubar o ciclo antes
        $schema->dropIfExists('pedido');
        $schema->dropIfExists('ciclo');
    }
};
