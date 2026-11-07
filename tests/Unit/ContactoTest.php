<?php

namespace Tests\Unit;

use App\Crm\Contacto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContactoTest extends TestCase
{
    #[DataProvider('telefonos')]
    public function test_normaliza_el_telefono_para_whatsapp(?string $telefono, ?string $esperado): void
    {
        $this->assertSame($esperado, Contacto::whatsapp($telefono));
    }

    public static function telefonos(): array
    {
        return [
            ['987 654 321', 'https://wa.me/51987654321'],
            ['987-654-321', 'https://wa.me/51987654321'],
            ['+51 987654321', 'https://wa.me/51987654321'],
            ['12345', null],
            [null, null],
            ['', null],
        ];
    }

    public function test_agrega_el_mensaje_codificado(): void
    {
        $this->assertSame(
            'https://wa.me/51987654321?text=Hola%20Rosa%2C%20%C2%BFc%C3%B3mo%20est%C3%A1s%3F',
            Contacto::whatsapp('987654321', 'Hola Rosa, ¿cómo estás?'),
        );
    }
}
