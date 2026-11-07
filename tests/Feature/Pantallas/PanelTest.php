<?php

namespace Tests\Feature\Pantallas;

use App\Models\Oportunidad;
use App\Models\Prospecto;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_panel_muestra_indicadores(): void
    {
        $tienda = $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearClienteWeb('Almacenes Premium');
        $this->crearVentaPclap(['cliente_id' => $tienda, 'fecha_hora' => '2026-11-02 10:00:00']);
        Prospecto::factory()->create();
        Tarea::factory()->create(['vence_el' => '2026-11-05']);
        Tarea::factory()->create(['vence_el' => '2026-11-06']);
        Oportunidad::factory()->etapa('nuevo')->create(['monto' => 1500]);
        Oportunidad::factory()->etapa('ganado')->create(['monto' => 9000]);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('clientes', 2)
            ->assertViewHas('nuevosDelMes', 1)
            ->assertViewHas('inactivos', 0)
            ->assertViewHas('tareasHoy', 2)
            ->assertViewHas('valorEmbudo', 1500.0)
            ->assertSee('S/ 1,500.00');
    }

    public function test_panel_importa_las_cotizaciones_de_pclap(): void
    {
        $this->crearProforma('Rosa Quispe', '912345678', 302.00);

        $this->get('/')->assertOk();

        $this->assertSame(1, Oportunidad::whereNotNull('pclap_proforma_id')->count());
    }

    public function test_panel_avisa_si_pclap_no_responde(): void
    {
        Tarea::factory()->create(['titulo' => 'Llamar a Rosa', 'vence_el' => '2026-11-06']);
        config(['database.connections.pclap.database' => '/ruta/que/no/existe.sqlite']);
        app('db')->purge('pclap');

        $this->get('/')
            ->assertOk()
            ->assertSee('No se pudo leer la base de PCLAP')
            ->assertSee('Llamar a Rosa');
    }
}
