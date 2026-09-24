<?php


declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->table('usuario', function (Blueprint $t): void {
            // hash dos 6 digitos, nunca o texto
            $t->string('codigo', 255)->nullable()->after('email_confirmado');
            $t->dateTime('data_codigo_expira')->nullable()->after('codigo');
            $t->integer('num_tentativas')->default(0)->after('data_codigo_expira');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->table('usuario', function (Blueprint $t): void {
            $t->dropColumn(['codigo', 'data_codigo_expira', 'num_tentativas']);
        });
    }
};
