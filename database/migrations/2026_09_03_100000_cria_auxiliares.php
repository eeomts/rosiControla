<?php

declare(strict_types=1);

use Cubo\Database\Migrations\Migration;
use Cubo\Database\Migrations\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /** @var array<string, list<string>> tabela => valores, na ordem dos ids */
    private const CARGA = [
        'status_pagamento_aux' => ['Nao pago', 'Pago'],
        'status_entrega_aux' => ['Nao entregue', 'Entregue'],
        'genero_aux' => ['Feminino', 'Masculino', 'Unissex'],
    ];

    public function up(Schema $schema): void
    {
        foreach (array_keys(self::CARGA) as $tabela) {
            $schema->create($tabela, function (Blueprint $t): void {
                $t->tinyIncrements('id');
                $t->string('nome', 30);
                $t->dateTime('created')->nullable();
                $t->dateTime('updated')->nullable();
                $t->boolean('deleted')->default(0);
            });
        }

        foreach (self::CARGA as $tabela => $valores) {
            $schema->getConnection()->table($tabela)->insert(
                array_map(static fn (string $nome): array => ['nome' => $nome], $valores)
            );
        }
    }

    public function down(Schema $schema): void
    {
        // ordem inversa da criacao, por simetria; nenhuma delas depende da outra
        foreach (array_reverse(array_keys(self::CARGA)) as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
