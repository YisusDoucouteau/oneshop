<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regularizaciones_valoracion_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('cantidad_disponible_snapshot');
            $table->unsignedInteger('cantidad_reservada_snapshot');
            $table->unsignedInteger('stock_fisico_snapshot');

            $table->decimal('costo_promedio_anterior_bob', 14, 6)
                ->nullable();

            $table->decimal('costo_promedio_resultante_bob', 14, 6);

            $table->decimal('valor_total_bob', 14, 2);

            $table->string('referencia', 120);
            $table->text('motivo');

            $table->dateTime('fecha_regularizacion');
            $table->timestamps();

            $table->unique(
                ['producto_id', 'almacen_id'],
                'uq_regularizacion_valoracion_producto_almacen'
            );

            $table->index(
                ['almacen_id', 'fecha_regularizacion'],
                'idx_regularizacion_valoracion_almacen_fecha'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regularizaciones_valoracion_inventario');
    }
};
