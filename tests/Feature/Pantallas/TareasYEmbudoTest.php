<?php

namespace Tests\Feature\Pantallas;

use App\Models\Oportunidad;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class TareasYEmbudoTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_tareas_agrupadas_y_marcar_hecha_por_ajax(): void
    {
        Tarea::factory()->create(['titulo' => 'Cobrar factura', 'vence_el' => '2026-11-03']);
        $hoy = Tarea::factory()->create(['titulo' => 'Llamar a Rosa', 'vence_el' => '2026-11-06']);
        Tarea::factory()->create(['titulo' => 'Visitar Almacenes', 'vence_el' => '2026-11-12']);

        $this->get('/tareas')
            ->assertOk()
            ->assertSeeInOrder(['Vencidas', 'Cobrar factura', 'Para hoy', 'Llamar a Rosa', 'Próximas', 'Visitar Almacenes']);

        $this->patchJson("/tareas/{$hoy->id}/hecha")
            ->assertOk()
            ->assertJsonPath('id', $hoy->id)
            ->assertJsonPath('vencidas', 1);
        $this->assertNotNull($hoy->fresh()->hecha_en);
    }

    public function test_crea_tarea_con_cliente_opcional(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium');

        $this->post('/tareas', ['titulo' => 'Revisar stock', 'vence_el' => '2026-11-07'])
            ->assertRedirect('/tareas')
            ->assertSessionHas('success');

        $this->post('/tareas', ['titulo' => 'Llamar', 'vence_el' => '2026-11-07', 'cliente_ref' => "web-{$web}"])
            ->assertRedirect(route('clientes.show', ['ref' => "web-{$web}", 'pestana' => 'tareas']));
        $this->assertSame('web', Tarea::where('titulo', 'Llamar')->value('cliente_tipo'));

        $this->post('/tareas', ['titulo' => 'X', 'vence_el' => '2026-11-07', 'cliente_ref' => 'web-999'])
            ->assertSessionHasErrors('cliente_ref');
        $this->post('/tareas', ['titulo' => '', 'vence_el' => 'mañana'])
            ->assertSessionHasErrors(['titulo', 'vence_el']);
    }

    public function test_el_embudo_muestra_columnas_y_cotizaciones_de_pclap(): void
    {
        $id = $this->crearProforma('Rosa Quispe', '912345678', 302.00);
        Oportunidad::factory()->etapa('nuevo')->create(['titulo' => 'Laptops para colegio', 'monto' => 12000]);

        $this->get('/embudo')
            ->assertOk()
            ->assertSeeInOrder(['Nuevo', 'Contactado', 'Cotizado', 'Ganado', 'Perdido'])
            ->assertSee('Laptops para colegio')
            ->assertSee("Cotización N° {$id}")
            ->assertSee('S/ 12,000.00');
    }

    public function test_mover_responde_totales_actualizados(): void
    {
        $oportunidad = Oportunidad::factory()->etapa('nuevo')->create(['monto' => 1500]);

        $this->patchJson("/oportunidades/{$oportunidad->id}/mover", ['etapa' => 'ganado', 'posicion' => 0])
            ->assertOk()
            ->assertJsonPath('etapa', 'ganado')
            ->assertJsonPath('columnas.ganado.cantidad', 1)
            ->assertJsonPath('columnas.ganado.total', 1500)
            ->assertJsonPath('columnas.nuevo.cantidad', 0)
            ->assertJsonPath('valor_abierto', 0);
    }

    public function test_mover_a_etapa_invalida_da_422(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->patchJson("/oportunidades/{$oportunidad->id}/mover", ['etapa' => 'archivado', 'posicion' => 0])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn ($mensaje) => str_contains($mensaje, 'no existe'));
    }

    public function test_mover_a_perdido_sin_motivo_da_422(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->patchJson("/oportunidades/{$oportunidad->id}/mover", ['etapa' => 'perdido', 'posicion' => 0])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Escribe por qué se perdió la oportunidad.');

        $this->patchJson("/oportunidades/{$oportunidad->id}/mover", ['etapa' => 'perdido', 'posicion' => 0, 'motivo' => 'Precio'])
            ->assertOk();
    }

    public function test_crea_oportunidad_en_nuevo(): void
    {
        Oportunidad::factory()->etapa('nuevo', 0)->create();
        $web = $this->crearClienteWeb('Almacenes Premium', '987654321');

        $this->post('/oportunidades', ['titulo' => '10 monitores', 'monto' => '4500', 'cliente_ref' => "web-{$web}"])
            ->assertRedirect('/embudo')
            ->assertSessionHas('success');

        $nueva = Oportunidad::where('titulo', '10 monitores')->sole();
        $this->assertSame('nuevo', $nueva->etapa);
        $this->assertSame(1, $nueva->orden);
        $this->assertSame('Almacenes Premium', $nueva->contacto_nombre);
        $this->assertSame('987654321', $nueva->contacto_telefono);

        $this->post('/oportunidades', ['titulo' => '', 'monto' => '-5'])
            ->assertSessionHasErrors(['titulo', 'monto', 'contacto_nombre']);
    }
}
