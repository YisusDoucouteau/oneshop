<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('solicitudes_descuentos', function (Blueprint $table) {
        $table->id();

        $table->foreignId('equipo_id')
            ->constrained('equipos')
            ->restrictOnDelete();

        $table->foreignId('precio_equipo_id')
            ->constrained('precios_equipos')
            ->restrictOnDelete();

        $table->foreignId('politica_descuento_id')
            ->nullable()
            ->constrained('politicas_descuentos')
            ->restrictOnDelete();

        $table->foreignId('cliente_id')
            ->nullable()
            ->constrained('clientes')
            ->restrictOnDelete();

        $table->foreignId('solicitado_por_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->decimal('precio_publico_snapshot', 14, 2);

        $table->decimal('precio_solicitado', 14, 2);

        $table->decimal('descuento_solicitado', 14, 2);

        $table->decimal('porcentaje_descuento', 5, 2);

        $table->decimal('costo_total_snapshot', 14, 2);

        $table->decimal('utilidad_proyectada', 14, 2);

        $table->string('estado', 30)
            ->default('PENDIENTE');

        $table->string('motivo', 255)
            ->nullable();

        $table->foreignId('respondido_por_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->dateTime('fecha_respuesta')
            ->nullable();

        $table->string('motivo_respuesta', 255)
            ->nullable();

        $table->timestamps();

        $table->index([
            'estado',
            'created_at',
        ]);
    });

    DB::statement(
        'ALTER TABLE solicitudes_descuentos
         ADD CONSTRAINT chk_descuento_porcentaje
         CHECK (
             porcentaje_descuento BETWEEN 0 AND 100
         )'
    );

    DB::statement(
        'ALTER TABLE solicitudes_descuentos
         ADD CONSTRAINT chk_precio_solicitado
         CHECK (precio_solicitado > 0)'
    );
}

public function down(): void
{
    Schema::dropIfExists('solicitudes_descuentos');
}
};
