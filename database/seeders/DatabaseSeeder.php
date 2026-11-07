<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Único usuario de la Fase 1. Correo y contraseña vienen del .env.
        User::updateOrCreate(
            ['email' => env('CRM_ADMIN_EMAIL', 'crm@gmail.com')],
            ['name' => 'Administrador', 'password' => Hash::make(env('CRM_ADMIN_PASSWORD', 'password'))],
        );

        $this->call(DemoSeeder::class);
    }
}
