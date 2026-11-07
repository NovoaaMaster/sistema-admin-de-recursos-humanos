<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->date('vence_el');
            $table->dateTime('hecha_en')->nullable(); // null = pendiente
            $table->string('cliente_tipo', 10)->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreignId('oportunidad_id')->nullable()->constrained('oportunidades')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('es_demo')->default(false);
            $table->timestamps();

            $table->index(['cliente_tipo', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
