<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transiciones_estados_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estado_origen_id')
                ->constrained('estados_equipos')
                ->restrictOnDelete();

            $table->foreignId('estado_destino_id')
                ->constrained('estados_equipos')
                ->restrictOnDelete();

            $table->boolean('requiere_autorizacion')
                ->default(false);

            $table->string('descripcion', 255)
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['estado_origen_id', 'estado_destino_id'],
                'uq_transicion_estado'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transiciones_estados_equipos');
    }
};