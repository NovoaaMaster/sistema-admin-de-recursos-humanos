<?php

namespace Tests\Feature;

use App\Models\Nota;
use App\Models\Oportunidad;
use App\Models\Prospecto;
use App\Models\Tarea;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_demo_crea_los_datos_marcados(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');

        $this->seed(DemoSeeder::class);

        $this->assertSame(12, Prospecto::where('es_demo', true)->count());
        $this->assertSame(
            ['contactado' => 2, 'cotizado' => 2, 'ganado' => 2, 'nuevo' => 3, 'perdido' => 1],
            Oportunidad::where('es_demo', true)->get()->countBy('etapa')->sortKeys()->all(),
        );
        $this->assertNotNull(Oportunidad::where('etapa', 'perdido')->value('motivo_perdida'));
        $this->assertTrue(Oportunidad::all()->every(fn ($o) => $o->monto >= 300 && $o->monto <= 12000));

        $tareas = Tarea::where('es_demo', true)->get();
        $this->assertCount(8, $tareas);
        $this->assertSame(2, $tareas->filter(fn ($t) => $t->vence_el->isBefore(today()))->count());
        $this->assertSame(3, $tareas->filter(fn ($t) => $t->vence_el->isToday())->count());
        $this->assertSame(3, $tareas->filter(fn ($t) => $t->vence_el->isAfter(today()))->count());

        $this->assertSame(6, Nota::where('es_demo', true)->count());
    }

    public function test_volver_a_sembrar_reemplaza_el_demo_y_no_toca_lo_real(): void
    {
        Prospecto::factory()->create(['nombre' => 'Cliente real', 'es_demo' => false]);

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(12, Prospecto::where('es_demo', true)->count());
        $this->assertSame(10, Oportunidad::count());
        $this->assertTrue(Prospecto::where('nombre', 'Cliente real')->exists());
    }
}
