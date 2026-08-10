<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_costos_lotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('costo_lote_id')
                ->constrained('costos_lotes')
                ->cascadeOnDelete();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->string('metodo_asignacion', 30);

            $table->decimal('porcentaje', 7, 4)
                ->nullable();

            $table->decimal('monto_asignado_bob', 14, 2);

            $table->timestamps();

            $table->unique(
                ['costo_lote_id', 'equipo_id'],
                'uq_costo_lote_equipo'
            );
        });

        DB::statement(
            'ALTER TABLE asignaciones_costos_lotes
             ADD CONSTRAINT chk_asignacion_costo_monto
             CHECK (monto_asignado_bob >= 0)'
        );

        DB::statement(
            'ALTER TABLE asignaciones_costos_lotes
             ADD CONSTRAINT chk_asignacion_costo_porcentaje
             CHECK (
                 porcentaje IS NULL
                 OR porcentaje BETWEEN 0 AND 100
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_costos_lotes');
    }
};