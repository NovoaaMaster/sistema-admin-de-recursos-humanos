<?php

namespace Database\Factories;

use App\Models\Tarea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tarea> */
class TareaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => 'Llamar al cliente',
            'vence_el' => now()->toDateString(),
            'hecha_en' => null,
        ];
    }
}
