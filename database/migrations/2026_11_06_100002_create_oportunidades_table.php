<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('etapa', 12)->default('nuevo'); // nuevo, contactado, cotizado, ganado, perdido
            $table->unsignedInteger('orden')->default(0); // posición dentro de la columna del embudo
            $table->dateTime('etapa_desde');
            $table->string('contacto_nombre');
            $table->string('contacto_telefono', 20)->nullable();
            // Cliente opcional: tienda o web (PCLAP, otra base: sin clave foránea) o prospecto.
            $table->string('cliente_tipo', 10)->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            // Cotización de PCLAP de la que salió; se importa una sola vez.
            $table->unsignedBigInteger('pclap_proforma_id')->nullable()->unique();
            $table->string('motivo_perdida')->nullable();
            $table->dateTime('cerrada_en')->nullable();
            $table->boolean('es_demo')->default(false);
            $table->timestamps();

            $table->index(['etapa', 'orden']);
            $table->index(['cliente_tipo', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
    }
};
