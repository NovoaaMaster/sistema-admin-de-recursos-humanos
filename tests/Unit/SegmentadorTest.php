<?php

namespace Tests\Unit;

use App\Crm\Segmentador;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class SegmentadorTest extends TestCase
{
    private function segmento(bool $prospecto, int $compras, ?string $primera, ?string $ultima): string
    {
        return Segmentador::segmento(
            $prospecto,
            $compras,
            $primera ? Carbon::parse($primera) : null,
            $ultima ? Carbon::parse($ultima) : null,
            Carbon::parse('2026-11-06 10:00:00'),
        );
    }

    public function test_aplica_las_reglas_en_orden_de_prioridad(): void
    {
        $this->assertSame('Prospecto', $this->segmento(true, 0, null, null));
        $this->assertSame('Sin compras', $this->segmento(false, 0, null, null));
        $this->assertSame('Nuevo', $this->segmento(false, 1, '2026-10-20', '2026-10-20'));
        $this->assertSame('Inactivo', $this->segmento(false, 5, '2026-01-10', '2026-07-01'));
        $this->assertSame('Frecuente', $this->segmento(false, 3, '2026-08-01', '2026-10-30'));
        $this->assertSame('Ocasional', $this->segmento(false, 1, '2026-09-01', '2026-09-01'));
    }

    public function test_lista_los_seis_segmentos(): void
    {
        $this->assertSame(['Prospecto', 'Sin compras', 'Nuevo', 'Inactivo', 'Frecuente', 'Ocasional'], Segmentador::SEGMENTOS);
    }
}
