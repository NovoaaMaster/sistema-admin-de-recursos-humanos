<?php

namespace Tests\Feature\Crm;

use App\Crm\ClienteRef;
use App\Crm\DirectorioClientes;
use App\Models\Prospecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class DirectorioClientesTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    private function directorio(): DirectorioClientes
    {
        return app(DirectorioClientes::class);
    }

    public function test_junta_clientes_de_tienda_web_y_prospectos_sin_publico_general(): void
    {
        $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearClienteWeb('Almacenes Premium');
        Prospecto::factory()->create(['nombre' => 'Rosa Quispe']);

        $clientes = $this->directorio()->todos();

        $this->assertCount(3, $clientes);
        $this->assertEqualsCanonicalizing(['Tienda', 'Web', 'Prospecto'], $clientes->pluck('origen')->all());
        $this->assertNotContains('Público General', $clientes->pluck('nombre')->all());
    }

    public function test_cuenta_solo_ventas_pagadas_y_no_las_de_cliente_nuevo(): void
    {
        $id = $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearVentaPclap(['cliente_id' => $id, 'total' => 100, 'fecha_hora' => '2026-09-01 10:00:00']);
        $this->crearVentaPclap(['cliente_id' => $id, 'total' => 50, 'estado_pago' => 'PENDIENTE']);
        $this->crearVentaPclap(['cliente_id' => $id, 'total' => 30, 'cliente_nuevo' => 'Rosa']);

        $cliente = $this->directorio()->buscarRef(new ClienteRef('tienda', $id));

        $this->assertSame(1, $cliente->compras);
        $this->assertSame(100.0, $cliente->totalComprado);
        $this->assertSame('2026-09-01', $cliente->ultimaCompra->toDateString());
        $this->assertSame('Ocasional', $cliente->segmento);
    }

    public function test_las_ventas_web_cuentan_para_el_cliente_web(): void
    {
        $web = $this->crearClienteWeb('Almacenes Premium', '987654321');
        $this->crearVentaPclap(['cliente_id' => $this->publicoGeneralId(), 'clienteweb_id' => $web, 'total' => 612, 'fecha_hora' => '2026-10-30 10:00:00']);

        $cliente = $this->directorio()->buscarRef(new ClienteRef('web', $web));

        $this->assertSame(1, $cliente->compras);
        $this->assertSame(612.0, $cliente->totalComprado);
        $this->assertSame('Nuevo', $cliente->segmento);
        $this->assertSame('https://wa.me/51987654321', $cliente->whatsapp());
    }

    public function test_ordena_por_ultima_compra_y_deja_al_final_los_que_no_compran(): void
    {
        $antiguo = $this->crearClienteTienda('Antiguo', '11111111');
        $reciente = $this->crearClienteTienda('Reciente', '22222222');
        $this->crearClienteTienda('Sin Compras', '33333333');
        Prospecto::factory()->create(['nombre' => 'Prospecto Uno']);
        $this->crearVentaPclap(['cliente_id' => $antiguo, 'fecha_hora' => '2026-05-01 10:00:00']);
        $this->crearVentaPclap(['cliente_id' => $reciente, 'fecha_hora' => '2026-11-01 10:00:00']);

        $nombres = $this->directorio()->todos()->pluck('nombre')->all();

        $this->assertSame(['Reciente', 'Antiguo'], array_slice($nombres, 0, 2));
        $this->assertEqualsCanonicalizing(['Sin Compras', 'Prospecto Uno'], array_slice($nombres, 2));
    }

    public function test_busca_por_nombre_o_documento_y_filtra_por_segmento(): void
    {
        $this->crearClienteTienda('Comercial Andina SAC', '20512345678');
        $this->crearClienteWeb('Almacenes Premium');
        Prospecto::factory()->create(['nombre' => 'Rosa Quispe']);

        $this->assertSame(['Comercial Andina SAC'], $this->directorio()->buscar('andina', null)->pluck('nombre')->all());
        $this->assertSame(['Comercial Andina SAC'], $this->directorio()->buscar('2051234', null)->pluck('nombre')->all());
        $this->assertSame(['Rosa Quispe'], $this->directorio()->buscar(null, 'Prospecto')->pluck('nombre')->all());
        $this->assertCount(2, $this->directorio()->buscar(null, 'Sin compras'));
    }

    public function test_conteo_por_segmento_incluye_todos_los_segmentos(): void
    {
        Prospecto::factory()->count(2)->create();

        $this->assertSame(
            ['Prospecto' => 2, 'Sin compras' => 0, 'Nuevo' => 0, 'Inactivo' => 0, 'Frecuente' => 0, 'Ocasional' => 0],
            $this->directorio()->conteoPorSegmento(),
        );
    }

    public function test_nuevos_del_mes_cuenta_primeras_compras_del_mes_en_curso(): void
    {
        $nuevo = $this->crearClienteTienda('Nuevo', '11111111');
        $viejo = $this->crearClienteTienda('Viejo', '22222222');
        $this->crearVentaPclap(['cliente_id' => $nuevo, 'fecha_hora' => '2026-11-03 10:00:00']);
        $this->crearVentaPclap(['cliente_id' => $viejo, 'fecha_hora' => '2026-10-15 10:00:00']);
        $this->crearVentaPclap(['cliente_id' => $viejo, 'fecha_hora' => '2026-11-04 10:00:00']);

        $this->assertSame(1, $this->directorio()->nuevosDelMes());
    }

    public function test_buscar_ref_de_publico_general_o_inexistente_falla(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->directorio()->buscarRef(new ClienteRef('tienda', $this->publicoGeneralId()));
    }

    public function test_buscar_ref_inexistente_falla(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->directorio()->buscarRef(new ClienteRef('web', 999));
    }
}
