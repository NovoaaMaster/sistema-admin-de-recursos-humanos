<?php

namespace Tests\Feature\Crm;

use App\Crm\ClienteRef;
use App\Crm\FichaCliente;
use App\Models\Nota;
use App\Models\Oportunidad;
use App\Models\Prospecto;
use App\Models\Tarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class FichaClienteTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    private function ficha(ClienteRef $ref): array
    {
        return app(FichaCliente::class)->para($ref);
    }

    public function test_la_ficha_de_un_cliente_web_trae_compras_garantias_y_pedidos(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium', '987654321');
        $venta = $this->crearVentaPclap(['clienteweb_id' => $web, 'lineas' => [['producto' => 'ssd 1tb', 'cantidad' => 2, 'precio' => 306, 'series' => ['A1', 'A2']]]]);
        $this->crearGarantia($venta, 'A1');
        $this->crearPedidoWeb($web, 'PW-0001', 612.00);
        $this->crearPedidoWeb($this->crearClienteWeb('Otro'), 'PW-0002', 99.00);

        $ficha = $this->ficha(new ClienteRef('web', $web));

        $this->assertSame('Almacenes Premium', $ficha['cliente']->nombre);
        $this->assertCount(1, $ficha['compras']);
        $this->assertEquals(['A1', 'A2'], $ficha['compras']->first()->series->pluck('numero_serie')->all());
        $this->assertSame(['A1'], $ficha['garantias']->pluck('numero_serie')->all());
        $this->assertSame(['PW-0001'], $ficha['pedidosWeb']->pluck('codigo')->all());
    }

    public function test_la_actividad_mezcla_pclap_y_crm_ordenada_por_fecha(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium');
        $venta = $this->crearVentaPclap(['clienteweb_id' => $web, 'fecha_hora' => '2026-10-01 10:00:00']);
        $this->crearGarantia($venta, '515151', '2026-10-05 12:00:00');
        Nota::forceCreate(['cliente_tipo' => 'web', 'cliente_id' => $web, 'texto' => 'Pidió precio de monitores', 'created_at' => '2026-11-02 09:00:00']);

        $actividad = $this->ficha(new ClienteRef('web', $web))['actividad'];

        $this->assertSame(['nota', 'garantia', 'compra'], $actividad->pluck('tipo')->all());
        $this->assertSame('Pidió precio de monitores', $actividad->first()['detalle']);
    }

    public function test_ticket_promedio_es_total_entre_compras(): void
    {
        $id = $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearVentaPclap(['cliente_id' => $id, 'total' => 100]);
        $this->crearVentaPclap(['cliente_id' => $id, 'total' => 300]);

        $this->assertSame(200.0, $this->ficha(new ClienteRef('tienda', $id))['ticketPromedio']);
    }

    public function test_trae_notas_tareas_y_oportunidades_del_cliente(): void
    {
        $id = $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $ref = new ClienteRef('tienda', $id);
        Nota::create(['cliente_tipo' => 'tienda', 'cliente_id' => $id, 'texto' => 'Cliente puntual']);
        Tarea::factory()->create(['cliente_tipo' => 'tienda', 'cliente_id' => $id]);
        Oportunidad::factory()->create(['cliente_tipo' => 'tienda', 'cliente_id' => $id]);
        Nota::create(['cliente_tipo' => 'web', 'cliente_id' => $id, 'texto' => 'De otro cliente']);

        $ficha = $this->ficha($ref);

        $this->assertSame(['Cliente puntual'], $ficha['notas']->pluck('texto')->all());
        $this->assertCount(1, $ficha['tareas']);
        $this->assertCount(1, $ficha['oportunidades']);
    }

    public function test_la_ficha_de_un_prospecto_no_consulta_pclap(): void
    {
        $prospecto = Prospecto::factory()->create(['nombre' => 'Rosa Quispe']);
        config(['database.connections.pclap.database' => '/ruta/que/no/existe.sqlite']);
        app('db')->purge('pclap');

        $ficha = $this->ficha($prospecto->ref());

        $this->assertSame('Rosa Quispe', $ficha['cliente']->nombre);
        $this->assertTrue($ficha['compras']->isEmpty());
        $this->assertTrue($ficha['garantias']->isEmpty());
        $this->assertTrue($ficha['pedidosWeb']->isEmpty());
    }
}
