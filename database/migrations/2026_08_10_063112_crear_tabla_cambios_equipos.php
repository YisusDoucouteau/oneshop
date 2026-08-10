<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caso_garantia_id')
                ->unique()
                ->constrained('casos_garantia')
                ->restrictOnDelete();

            $table->foreignId('equipo_saliente_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('equipo_entrante_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('autorizado_por_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_cambio');

            $table->string('motivo', 255);

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index('equipo_saliente_id');
            $table->index('equipo_entrante_id');
        });

        DB::statement(
            'ALTER TABLE cambios_equipos
             ADD CONSTRAINT chk_equipos_cambio
             CHECK (equipo_saliente_id <> equipo_entrante_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_equipos');
    }
};