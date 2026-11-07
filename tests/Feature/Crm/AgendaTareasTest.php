<?php

namespace Tests\Feature\Crm;

use App\Crm\AgendaTareas;
use App\Models\Tarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaTareasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    private function crear(string $titulo, string $vence, ?string $hecha = null): Tarea
    {
        return Tarea::factory()->create(['titulo' => $titulo, 'vence_el' => $vence, 'hecha_en' => $hecha]);
    }

    public function test_agrupa_vencidas_hoy_y_proximas(): void
    {
        $this->crear('Vencida reciente', '2026-11-04');
        $this->crear('Vencida antigua', '2026-11-03');
        $this->crear('De hoy', '2026-11-06');
        $this->crear('Próxima', '2026-11-10');

        $grupos = app(AgendaTareas::class)->grupos();

        $this->assertSame(['Vencida antigua', 'Vencida reciente'], $grupos['vencidas']->pluck('titulo')->all());
        $this->assertSame(['De hoy'], $grupos['hoy']->pluck('titulo')->all());
        $this->assertSame(['Próxima'], $grupos['proximas']->pluck('titulo')->all());
    }

    public function test_las_hechas_no_aparecen(): void
    {
        $this->crear('Hecha', '2026-11-04', '2026-11-05 09:00:00');

        $grupos = app(AgendaTareas::class)->grupos();

        $this->assertSame(0, $grupos['vencidas']->count() + $grupos['hoy']->count() + $grupos['proximas']->count());
    }

    public function test_cuenta_vencidas_y_las_que_tocan_hoy(): void
    {
        $this->crear('A', '2026-11-04');
        $this->crear('B', '2026-11-05');
        $this->crear('C', '2026-11-06');
        $this->crear('D', '2026-11-09');

        $this->assertSame(2, app(AgendaTareas::class)->vencidas());
        $this->assertSame(3, app(AgendaTareas::class)->paraHoy());
    }

    public function test_marcar_hecha_guarda_la_fecha(): void
    {
        $tarea = $this->crear('Llamar', '2026-11-06');

        $hecha = app(AgendaTareas::class)->marcarHecha($tarea);

        $this->assertSame('2026-11-06 10:00:00', $hecha->hecha_en->format('Y-m-d H:i:s'));
        $this->assertNotNull($tarea->fresh()->hecha_en);
    }
}
