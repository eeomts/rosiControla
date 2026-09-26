<?php


declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // so as falhas: o login certo apaga as do ip
        $schema->create('tentativa_login', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('ip', 45);
            $t->string('email', 160);
            $t->dateTime('data_tentativa');
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);

            $t->index(['ip', 'data_tentativa'], 'ix_tentativa_login_ip');
            $t->index(['email', 'data_tentativa'], 'ix_tentativa_login_email');
        });

        $schema->table('usuario', function (Blueprint $t): void {
            $t->integer('num_envios_codigo')->default(0)->after('num_tentativas');
            $t->dateTime('data_ultimo_envio')->nullable()->after('num_envios_codigo');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->table('usuario', function (Blueprint $t): void {
            $t->dropColumn(['num_envios_codigo', 'data_ultimo_envio']);
        });

        $schema->dropIfExists('tentativa_login');
    }
};
