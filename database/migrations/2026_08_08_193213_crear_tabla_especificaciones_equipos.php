<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especificaciones_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->unique()
                ->constrained('equipos')
                ->cascadeOnDelete();

            $table->string('procesador', 150)
                ->nullable();

            $table->string('generacion_procesador', 80)
                ->nullable();

            $table->unsignedSmallInteger('ram_gb')
                ->nullable();

            $table->unsignedInteger('almacenamiento_gb')
                ->nullable();

            $table->string('tipo_almacenamiento', 50)
                ->nullable();

            $table->string('tarjeta_grafica', 150)
                ->nullable();

            $table->decimal('pantalla_pulgadas', 4, 1)
                ->nullable();

            $table->string('resolucion', 50)
                ->nullable();

            $table->string('sistema_operativo', 100)
                ->nullable();

            $table->unsignedTinyInteger('bateria_porcentaje')
                ->nullable();

            $table->json('datos_adicionales')
                ->nullable();

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE especificaciones_equipos
             ADD CONSTRAINT chk_bateria_porcentaje
             CHECK (
                bateria_porcentaje IS NULL
                OR bateria_porcentaje BETWEEN 0 AND 100
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('especificaciones_equipos');
    }
};