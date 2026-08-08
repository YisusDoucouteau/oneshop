<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('estado_origen_id')
                ->nullable()
                ->constrained('estados_equipos')
                ->restrictOnDelete();

            $table->foreignId('estado_destino_id')
                ->constrained('estados_equipos')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('autorizado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_cambio');

            $table->string('motivo', 255)
                ->nullable();

            $table->text('observacion')
                ->nullable();

            /*
             * El historial es inmutable.
             * Solo almacenamos fecha de creación.
             */
            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['equipo_id', 'fecha_cambio'],
                'idx_historial_equipo_fecha'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados_equipos');
    }
};