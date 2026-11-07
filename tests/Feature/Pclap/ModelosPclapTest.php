<?php

namespace Tests\Feature\Pclap;

use App\Pclap\ClienteWebPclap;
use App\Pclap\GarantiaPclap;
use App\Pclap\ProformaPclap;
use App\Pclap\VentaPclap;
use LogicException;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class ModelosPclapTest extends TestCase
{
    use ConEsquemaPclap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
    }

    public function test_lee_cliente_web_con_sus_ventas_y_pedidos(): void
    {
        $id = $this->crearClienteWeb('Almacenes Premium', '987654321');
        $venta = $this->crearVentaPclap(['clienteweb_id' => $id, 'lineas' => [['producto' => 'ssd 1tb', 'cantidad' => 2, 'precio' => 306, 'series' => ['A1', 'A2']]]]);
        $this->crearPedidoWeb($id, 'PW-0001', 612.00);
        $this->crearGarantia($venta, 'A1');

        $cliente = ClienteWebPclap::with('pedidos')->findOrFail($id);
        $compra = VentaPclap::with(['productos', 'series', 'garantias.producto'])->where('clienteweb_id', $id)->sole();

        $this->assertSame('Almacenes Premium', $cliente->nombre);
        $this->assertCount(1, $cliente->pedidos);
        $this->assertEquals(2, $compra->productos->first()->pivot->cantidad);
        $this->assertEquals(['A1', 'A2'], $compra->series->pluck('numero_serie')->all());
        $this->assertSame('A1', $compra->garantias->first()->numero_serie);
    }

    public function test_la_cotizacion_trae_sus_items(): void
    {
        $id = $this->crearProforma('Rosa Quispe', '912345678', 302.00);
        \DB::connection('pclap')->table('proforma_items')->insert(['proforma_id' => $id, 'descripcion' => 'Memoria RAM 16GB', 'cantidad' => 2, 'precio' => 151]);

        $this->assertSame('Memoria RAM 16GB', ProformaPclap::with('items')->findOrFail($id)->items->first()->descripcion);
    }

    public function test_no_permite_guardar_ni_borrar_en_pclap(): void
    {
        $venta = VentaPclap::findOrFail($this->crearVentaPclap());
        $garantia = GarantiaPclap::findOrFail($this->crearGarantia($venta->id, 'X1'));

        try {
            $venta->total = 1;
            $venta->save();
            $this->fail('save() debió fallar');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $garantia->delete();
    }

    public function test_pagadas_excluye_pendientes_y_devueltas(): void
    {
        $pagada = $this->crearVentaPclap();
        $this->crearVentaPclap(['estado_pago' => 'PENDIENTE']);
        $this->crearVentaPclap(['estado_pago' => 'DEVUELTO']);

        $this->assertSame([$pagada], VentaPclap::pagadas()->pluck('id')->all());
    }
}
