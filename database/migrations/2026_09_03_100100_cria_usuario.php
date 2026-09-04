<?php


declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('usuario', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('nome', 120);
            $t->string('email', 160);
            $t->string('senha', 255);
            $t->boolean('ativo')->default(1);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index('email', 'ix_usuario_email');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('usuario');
    }
};
