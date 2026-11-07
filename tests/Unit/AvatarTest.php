<?php

namespace Tests\Unit;

use App\Crm\Avatar;
use PHPUnit\Framework\TestCase;

class AvatarTest extends TestCase
{
    public function test_iniciales_con_acentos_una_palabra_y_vacio(): void
    {
        $this->assertSame('ÑÁ', Avatar::iniciales('Ñuñez Álvarez'));
        $this->assertSame('AP', Avatar::iniciales('  almacenes   premium sac '));
        $this->assertSame('R', Avatar::iniciales('rosa'));
        $this->assertSame('?', Avatar::iniciales(''));
    }

    public function test_el_color_es_estable_y_de_la_paleta(): void
    {
        $color = Avatar::color('Rosa Quispe');

        $this->assertSame($color, Avatar::color('Rosa Quispe'));
        $this->assertContains($color, Avatar::COLORES);
        $this->assertCount(8, Avatar::COLORES);
    }
}
