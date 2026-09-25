<?php


declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /** @var list<string> as categorias da revista, na ordem dos ids */
    private const CATEGORIAS = [
        'Perfumaria',
        'Rosto e maquiagem',
        'Cuidados para o corpo',
        'Cabelos',
        'Infantis',
    ];

    public function up(Schema $schema): void
    {
        $schema->create('categoria_aux', function (Blueprint $t): void {
            $t->tinyIncrements('id');
            $t->string('nome', 30);
            $t->dateTime('created')->nullable();
            $t->dateTime('updated')->nullable();
            $t->boolean('deleted')->default(0);
        });

        $schema->getConnection()->table('categoria_aux')->insert(
            array_map(static fn (string $nome): array => ['nome' => $nome], self::CATEGORIAS)
        );

        // nullable so por causa dos produtos que ja existem; quem obriga e o ProdutoService
        $schema->table('produto', function (Blueprint $t): void {
            $t->unsignedTinyInteger('fk_categoria')->nullable()->after('codigo_produto');

            $t->foreign('fk_categoria', 'fk_produto_categoria')->references('id')->on('categoria_aux');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->table('produto', function (Blueprint $t): void {
            $t->dropForeign('fk_produto_categoria');
            $t->dropColumn('fk_categoria');
        });

        $schema->dropIfExists('categoria_aux');
    }
};
