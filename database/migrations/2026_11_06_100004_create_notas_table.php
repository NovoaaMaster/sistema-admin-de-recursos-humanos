<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_tipo', 10);
            $table->unsignedBigInteger('cliente_id');
            $table->text('texto');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('es_demo')->default(false);
            $table->timestamps();

            $table->index(['cliente_tipo', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
