<?php

namespace Tests\Feature\Pantallas;

use App\Models\Nota;
use App\Models\Prospecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_lista_clientes_y_prospectos_con_su_etiqueta(): void
    {
        $tienda = $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearVentaPclap(['cliente_id' => $tienda, 'total' => 1224, 'fecha_hora' => '2026-11-01 10:00:00']);
        Prospecto::factory()->create(['nombre' => 'Rosa Quispe']);

        $this->get('/clientes')
            ->assertOk()
            ->assertSeeInOrder(['Comercial Andina SAC', 'Rosa Quispe'])
            ->assertSee('S/ 1,224.00')
            ->assertSee('segmento-nuevo', false)
            ->assertSee('segmento-prospecto', false);
    }

    public function test_filtra_por_segmento_y_busca(): void
    {
        $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        Prospecto::factory()->create(['nombre' => 'Rosa Quispe']);

        $this->get('/clientes?segmento=Prospecto')->assertSee('Rosa Quispe')->assertDontSee('Comercial Andina SAC');
        $this->get('/clientes?q=andina')->assertSee('Comercial Andina SAC')->assertDontSee('Rosa Quispe');
        $this->get('/clientes?q=zzz')->assertSee('No hay clientes que coincidan');
    }

    public function test_la_ficha_muestra_compras_con_series_y_whatsapp(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium', '987 654 321');
        $this->crearVentaPclap(['clienteweb_id' => $web, 'lineas' => [['producto' => 'ssd de 1tb kingston', 'cantidad' => 1, 'precio' => 306, 'series' => ['0003924387']]]]);

        $this->get("/clientes/web-{$web}")
            ->assertOk()
            ->assertSee('Almacenes Premium')
            ->assertSee('ssd de 1tb kingston')
            ->assertSee('0003924387')
            ->assertSee('https://wa.me/51987654321', false);
    }

    public function test_ref_invalida_o_publico_general_da_404(): void
    {
        $this->get('/clientes/tienda-abc')->assertNotFound();
        $this->get('/clientes/otro-1')->assertNotFound();
        $this->get('/clientes/tienda-'.$this->publicoGeneralId())->assertNotFound();
        $this->get('/clientes/web-999')->assertNotFound();
        $this->get('/clientes/prospecto-999')->assertNotFound();
    }

    public function test_crea_y_edita_un_prospecto(): void
    {
        $this->get('/prospectos/crear')->assertOk()->assertSee('Nuevo prospecto');

        $this->post('/prospectos', ['nombre' => '', 'origen' => 'Paloma mensajera'])
            ->assertSessionHasErrors(['nombre', 'origen']);

        $respuesta = $this->post('/prospectos', ['nombre' => 'Rosa Quispe', 'empresa' => 'Bodega Rosita', 'telefono' => '912345678', 'origen' => 'Feria']);
        $prospecto = Prospecto::sole();
        $respuesta->assertRedirect("/clientes/prospecto-{$prospecto->id}");

        $this->put("/prospectos/{$prospecto->id}", ['nombre' => 'Rosa Quispe Mamani', 'origen' => 'Feria'])
            ->assertRedirect("/clientes/prospecto-{$prospecto->id}");
        $this->assertSame('Rosa Quispe Mamani', $prospecto->fresh()->nombre);
    }

    public function test_agrega_una_nota_a_la_ficha(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium');

        $this->post("/clientes/web-{$web}/notas", ['texto' => 'Quiere cotización de 10 monitores'])
            ->assertRedirect(route('clientes.show', ['ref' => "web-{$web}", 'pestana' => 'notas']))
            ->assertSessionHas('success');

        $this->assertSame('Quiere cotización de 10 monitores', Nota::de(\App\Crm\ClienteRef::fromString("web-{$web}"))->value('texto'));
        $this->post("/clientes/web-{$web}/notas", ['texto' => ''])->assertSessionHasErrors('texto');
        $this->post('/clientes/web-999/notas', ['texto' => 'Hola'])->assertNotFound();
    }

    public function test_los_datos_demo_llevan_la_etiqueta_demo(): void
    {
        Prospecto::factory()->create(['nombre' => 'Demo Uno', 'es_demo' => true]);

        $this->get('/clientes')->assertSee('etiqueta-demo', false);

        Prospecto::query()->update(['es_demo' => false]);
        $this->get('/clientes')->assertDontSee('etiqueta-demo', false);
    }

    public function test_clientes_avisa_si_pclap_no_responde(): void
    {
        config(['database.connections.pclap.database' => '/ruta/que/no/existe.sqlite']);
        app('db')->purge('pclap');

        $this->get('/clientes')
            ->assertOk()
            ->assertSee('No se pudo leer la base de PCLAP');
    }
}
