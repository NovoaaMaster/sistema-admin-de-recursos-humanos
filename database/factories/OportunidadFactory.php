<?php

namespace Database\Factories;

use App\Models\Oportunidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Oportunidad> */
class OportunidadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => 'Laptop para oficina',
            'monto' => 1000,
            'etapa' => 'nuevo',
            'orden' => 0,
            'etapa_desde' => now(),
            'contacto_nombre' => fake()->name(),
        ];
    }

    public function etapa(string $etapa, int $orden = 0): static
    {
        return $this->state(['etapa' => $etapa, 'orden' => $orden]);
    }
}
