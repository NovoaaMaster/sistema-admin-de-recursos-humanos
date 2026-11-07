<?php

namespace Tests\Unit;

use App\Crm\ClienteRef;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClienteRefTest extends TestCase
{
    public function test_lee_y_escribe_el_formato_tipo_guion_id(): void
    {
        $ref = ClienteRef::fromString('web-2');

        $this->assertSame('web', $ref->tipo);
        $this->assertSame(2, $ref->id);
        $this->assertSame('web-2', (string) $ref);
        $this->assertSame('prospecto-7', (string) new ClienteRef('prospecto', 7));
    }

    #[DataProvider('invalidas')]
    public function test_rechaza_referencias_invalidas(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);

        ClienteRef::fromString($valor);
    }

    public static function invalidas(): array
    {
        return [['tienda-abc'], ['otro-1'], [''], ['tienda-0'], ['web-']];
    }
}
