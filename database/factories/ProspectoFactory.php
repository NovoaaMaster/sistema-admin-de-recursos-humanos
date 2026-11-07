<?php

namespace Database\Factories;

use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prospecto> */
class ProspectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'empresa' => null,
            'telefono' => '9'.fake()->numerify('########'),
            'origen' => 'WhatsApp',
            'es_demo' => false,
        ];
    }
}
