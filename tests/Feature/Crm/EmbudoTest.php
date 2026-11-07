<?php

namespace Tests\Feature\Crm;

use App\Crm\Embudo;
use App\Models\Oportunidad;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class EmbudoTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    private function embudo(): Embudo
    {
        return app(Embudo::class);
    }

    private function ordenDe(string $etapa): array
    {
        return Oportunidad::where('etapa', $etapa)->orderBy('orden')->pluck('titulo')->all();
    }

    public function test_importa_cada_cotizacion_una_sola_vez(): void
    {
        $id = $this->crearProforma('Rosa Quispe', '912345678', 302.00, '2026-10-27 21:01:20');
        $this->crearProforma('Comercial Sur', null, 6045.00);

        $this->assertSame(2, $this->embudo()->sincronizarProformas());
        $this->assertSame(0, $this->embudo()->sincronizarProformas());

        $oportunidad = Oportunidad::where('pclap_proforma_id', $id)->sole();
        $this->assertSame("Cotización N° {$id}", $oportunidad->titulo);
        $this->assertSame('cotizado', $oportunidad->etapa);
        $this->assertSame('302.00', $oportunidad->monto);
        $this->assertSame('Rosa Quispe', $oportunidad->contacto_nombre);
        $this->assertSame('912345678', $oportunidad->contacto_telefono);
        $this->assertSame('2026-10-27', $oportunidad->etapa_desde->toDateString());
    }

    public function test_las_cotizaciones_importadas_van_al_final_de_cotizado(): void
    {
        Oportunidad::factory()->etapa('cotizado', 0)->create(['titulo' => 'Existente']);
        $id = $this->crearProforma('Rosa Quispe', null, 302.00);

        $this->embudo()->sincronizarProformas();

        $this->assertSame(['Existente', "Cotización N° {$id}"], $this->ordenDe('cotizado'));
    }

    public function test_columnas_con_cantidad_total_y_porcentaje(): void
    {
        Oportunidad::factory()->etapa('nuevo')->create(['monto' => 1000]);
        Oportunidad::factory()->etapa('contactado')->create(['monto' => 1000]);
        Oportunidad::factory()->etapa('contactado', 1)->create(['monto' => 2000]);

        $columnas = collect($this->embudo()->columnas())->keyBy('etapa');

        $this->assertSame(['nuevo', 'contactado', 'cotizado', 'ganado', 'perdido'], $columnas->keys()->all());
        $this->assertSame('Contactado', $columnas['contactado']['nombre']);
        $this->assertSame(2, $columnas['contactado']['cantidad']);
        $this->assertSame(3000.0, $columnas['contactado']['total']);
        $this->assertSame(75, $columnas['contactado']['porcentaje']);
        $this->assertSame(25, $columnas['nuevo']['porcentaje']);
        $this->assertSame(0, $columnas['ganado']['porcentaje']);
    }

    public function test_el_embudo_vacio_tiene_porcentajes_en_cero(): void
    {
        $this->assertSame([0, 0, 0, 0, 0], array_column($this->embudo()->columnas(), 'porcentaje'));
    }

    public function test_mover_cambia_etapa_y_reordena_la_columna(): void
    {
        Oportunidad::factory()->etapa('contactado', 0)->create(['titulo' => 'A']);
        Oportunidad::factory()->etapa('contactado', 1)->create(['titulo' => 'B']);
        $c = Oportunidad::factory()->etapa('nuevo', 0)->create(['titulo' => 'C', 'etapa_desde' => '2026-10-01 10:00:00']);
        Oportunidad::factory()->etapa('nuevo', 1)->create(['titulo' => 'D']);

        $movida = $this->embudo()->mover($c, 'contactado', 1);

        $this->assertSame('contactado', $movida->etapa);
        $this->assertSame('2026-11-06 10:00:00', $movida->etapa_desde->format('Y-m-d H:i:s'));
        $this->assertSame(['A', 'C', 'B'], $this->ordenDe('contactado'));
        $this->assertSame(['D'], $this->ordenDe('nuevo'));
        $this->assertSame([0], Oportunidad::where('etapa', 'nuevo')->pluck('orden')->all());
    }

    public function test_reordenar_en_la_misma_columna_no_cambia_etapa_desde(): void
    {
        $a = Oportunidad::factory()->etapa('nuevo', 0)->create(['titulo' => 'A', 'etapa_desde' => '2026-10-01 10:00:00']);
        Oportunidad::factory()->etapa('nuevo', 1)->create(['titulo' => 'B']);

        $this->embudo()->mover($a, 'nuevo', 1);

        $this->assertSame(['B', 'A'], $this->ordenDe('nuevo'));
        $this->assertSame('2026-10-01', $a->fresh()->etapa_desde->toDateString());
    }

    public function test_posicion_fuera_de_rango_se_ajusta(): void
    {
        Oportunidad::factory()->etapa('cotizado', 0)->create(['titulo' => 'A']);
        $b = Oportunidad::factory()->etapa('nuevo')->create(['titulo' => 'B']);
        $c = Oportunidad::factory()->etapa('nuevo', 1)->create(['titulo' => 'C']);

        $this->embudo()->mover($b, 'cotizado', 99);
        $this->embudo()->mover($c, 'cotizado', -5);

        $this->assertSame(['C', 'A', 'B'], $this->ordenDe('cotizado'));
    }

    public function test_perdido_exige_motivo(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->expectException(DomainException::class);
        $this->embudo()->mover($oportunidad, 'perdido', 0, '  ');
    }

    public function test_etapa_invalida_falla(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->expectException(DomainException::class);
        $this->embudo()->mover($oportunidad, 'archivado', 0);
    }

    public function test_ganado_y_perdido_marcan_cerrada_en_y_reabrir_la_borra(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->embudo()->mover($oportunidad, 'perdido', 0, 'Eligió otra tienda');
        $this->assertSame('Eligió otra tienda', $oportunidad->fresh()->motivo_perdida);
        $this->assertNotNull($oportunidad->fresh()->cerrada_en);

        $this->embudo()->mover($oportunidad->fresh(), 'contactado', 0);
        $this->assertNull($oportunidad->fresh()->cerrada_en);
        $this->assertNull($oportunidad->fresh()->motivo_perdida);

        $this->embudo()->mover($oportunidad->fresh(), 'ganado', 0);
        $this->assertNotNull($oportunidad->fresh()->cerrada_en);
    }

    public function test_reordenar_dentro_de_perdido_no_pide_otra_vez_el_motivo(): void
    {
        $a = Oportunidad::factory()->etapa('perdido', 0)->create(['titulo' => 'A', 'motivo_perdida' => 'Precio']);
        Oportunidad::factory()->etapa('perdido', 1)->create(['titulo' => 'B', 'motivo_perdida' => 'Plazo']);

        $this->embudo()->mover($a, 'perdido', 1);

        $this->assertSame(['B', 'A'], $this->ordenDe('perdido'));
        $this->assertSame('Precio', $a->fresh()->motivo_perdida);
    }

    public function test_valor_abierto_suma_solo_nuevo_contactado_y_cotizado(): void
    {
        foreach (['nuevo' => 100, 'contactado' => 200, 'cotizado' => 300, 'ganado' => 4000, 'perdido' => 5000] as $etapa => $monto) {
            Oportunidad::factory()->etapa($etapa)->create(['monto' => $monto]);
        }

        $this->assertSame(600.0, $this->embudo()->valorAbierto());
    }
}
