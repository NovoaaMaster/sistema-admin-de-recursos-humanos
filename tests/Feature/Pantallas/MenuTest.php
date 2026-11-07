<?php

namespace Tests\Feature\Pantallas;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_el_menu_marca_la_pagina_actual(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('panel').'" aria-current="page"', false)
            ->assertDontSee('href="'.route('clientes.index').'" aria-current="page"', false);
    }

    public function test_el_menu_muestra_las_tareas_vencidas(): void
    {
        Tarea::factory()->create(['vence_el' => '2026-11-03']);
        Tarea::factory()->create(['vence_el' => '2026-11-05']);
        Tarea::factory()->create(['vence_el' => '2026-11-06']);

        $this->get('/')->assertSee('data-contador-vencidas>2</span>', false);
    }

    public function test_sin_tareas_vencidas_no_hay_contador(): void
    {
        $this->get('/')->assertDontSee('data-contador-vencidas', false);
    }

    public function test_la_marca_es_la_del_crm(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('class="logo-crm"', $html);
        $this->assertStringNotContainsString('Finanzas', $html);
        $this->assertStringNotContainsString('PCLAP SUPPORT', $html);
    }

    public function test_el_buscador_global_lleva_a_clientes(): void
    {
        $this->get('/')->assertSee('action="'.route('clientes.index').'"', false);
    }
}
