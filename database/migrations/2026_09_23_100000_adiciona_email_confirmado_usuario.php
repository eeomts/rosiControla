<?php


declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->table('usuario', function (Blueprint $t): void {
            $t->boolean('email_confirmado')->default(0)->after('email');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->table('usuario', function (Blueprint $t): void {
            $t->dropColumn('email_confirmado');
        });
    }
};
