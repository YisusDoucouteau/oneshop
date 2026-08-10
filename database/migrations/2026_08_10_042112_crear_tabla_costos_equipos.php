<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('tipo_costo_id')
                ->constrained('tipos_costos')
                ->restrictOnDelete();

            $table->foreignId('moneda_id')
                ->constrained('monedas')
                ->restrictOnDelete();

            $table->foreignId('tipo_cambio_id')
                ->nullable()
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->decimal('monto_origen', 14, 2);

            $table->decimal('monto_bob', 14, 2);

            $table->date('fecha_costo');

            $table->string('referencia', 150)
                ->nullable();

            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('descripcion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'equipo_id',
                'fecha_costo',
            ]);
        });

        DB::statement(
            'ALTER TABLE costos_equipos
             ADD CONSTRAINT chk_costo_equipo_montos
             CHECK (monto_origen > 0 AND monto_bob > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('costos_equipos');
    }
};