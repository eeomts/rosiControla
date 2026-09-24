<?php

namespace Controla\Tests\Models;

use Controla\Models\Usuario;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @package Controla\Tests
 * @author Mateus - github.com/eeomts
 */
#[CoversClass(Usuario::class)]
final class UsuarioTest extends TestCase
{
    #[DataProvider('emails')]
    public function testMascaraAMetadeDoEmail(string $email, string $esperado): void
    {
        $this->assertSame($esperado, (new Usuario(['email' => $email]))->emailMascarado());
    }

    /** @return array<string,array{string,string}> */
    public static function emails(): array
    {
        return [
            'par' => ['rosi@gmail.com', 'ro******@gmail.com'],
            'impar' => ['mateus@gmail.com', 'mat******@gmail.com'],
            'uma letra mostra a letra' => ['a@gmail.com', 'a******@gmail.com'],
            'o tamanho nao aparece' => ['mailtestemateus@gmail.com', 'mailtes******@gmail.com'],
            'sem arroba nao mostra nada' => ['rosi', '******'],
        ];
    }
}
