<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->unsignedBigInteger('detalle_lote_id')
                ->nullable();

            $table->foreignId('almacen_actual_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('estado_actual_id')
                ->constrained('estados_equipos')
                ->restrictOnDelete();

            $table->foreignId('condicion_fisica_id')
                ->nullable()
                ->constrained('condiciones_fisicas')
                ->restrictOnDelete();

            $table->string('codigo_interno', 50)
                ->unique();

            $table->string('serial_fabricante', 150)
                ->nullable();

            $table->dateTime('fecha_registro')
                ->useCurrent();

            $table->dateTime('fecha_disponible')
                ->nullable();

            $table->text('observacion')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Integridad lote-producto
            |--------------------------------------------------------------------------
            |
            | Si el equipo procede de un detalle de lote, el producto del equipo
            | debe ser exactamente el mismo producto registrado en dicho detalle.
            |
            */

            $table->foreign(
                ['detalle_lote_id', 'producto_id'],
                'fk_equipo_detalle_producto'
            )
                ->references(['id', 'producto_id'])
                ->on('detalles_lotes')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('serial_fabricante');

            $table->index(
                ['almacen_actual_id', 'estado_actual_id'],
                'idx_equipo_almacen_estado'
            );

            /*
            |--------------------------------------------------------------------------
            | Soporte para futuras relaciones compuestas
            |--------------------------------------------------------------------------
            */

            $table->unique(
                ['id', 'producto_id'],
                'uq_equipo_producto'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};