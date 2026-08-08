<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('tipo_movimiento_id')
                ->constrained('tipos_movimientos_inventario')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->integer('cambio_disponible')
                ->default(0);

            $table->integer('cambio_reservado')
                ->default(0);

            $table->unsignedInteger('saldo_disponible_resultante');

            $table->unsignedInteger('saldo_reservado_resultante');

            $table->string('tipo_referencia', 60)
                ->nullable();

            $table->unsignedBigInteger('referencia_id')
                ->nullable();

            $table->dateTime('fecha_movimiento');

            $table->text('observacion')
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['producto_id', 'almacen_id', 'fecha_movimiento'],
                'idx_movimiento_producto_almacen_fecha'
            );

            $table->index(
                ['tipo_referencia', 'referencia_id'],
                'idx_movimiento_referencia'
            );
        });

        DB::statement(
            'ALTER TABLE movimientos_inventario
             ADD CONSTRAINT chk_movimiento_con_cambio
             CHECK (
                 cambio_disponible <> 0
                 OR cambio_reservado <> 0
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};