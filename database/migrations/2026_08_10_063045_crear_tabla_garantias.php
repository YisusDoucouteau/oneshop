<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garantias', function (Blueprint $table) {
            $table->id();

            $table->string('numero', 50)
                ->unique();

            $table->foreignId('detalle_venta_id')
                ->unique()
                ->constrained('detalles_ventas')
                ->restrictOnDelete();

            $table->foreignId('politica_garantia_id')
                ->constrained('politicas_garantias')
                ->restrictOnDelete();

            $table->dateTime('fecha_inicio');

            $table->dateTime('fecha_fin');

            $table->dateTime('fecha_limite_cambio_inicial')
                ->nullable();

            $table->unsignedSmallInteger('duracion_meses_snapshot');

            $table->text('condiciones_snapshot');

            $table->text('exclusiones_snapshot')
                ->nullable();

            $table->string('estado', 30)
                ->default('VIGENTE');

            $table->timestamps();

            $table->index([
                'estado',
                'fecha_fin',
            ]);
        });

        DB::statement(
            'ALTER TABLE garantias
             ADD CONSTRAINT chk_fechas_garantia
             CHECK (fecha_fin >= fecha_inicio)'
        );

        DB::statement(
            'ALTER TABLE garantias
             ADD CONSTRAINT chk_duracion_garantia_snapshot
             CHECK (duracion_meses_snapshot > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('garantias');
    }
};