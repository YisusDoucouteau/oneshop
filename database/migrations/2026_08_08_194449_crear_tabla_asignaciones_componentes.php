<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_componentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('movimiento_salida_id')
                ->nullable()
                ->constrained('movimientos_inventario')
                ->restrictOnDelete();

            $table->foreignId('movimiento_retorno_id')
                ->nullable()
                ->constrained('movimientos_inventario')
                ->restrictOnDelete();

            $table->foreignId('asignado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('retirado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('cantidad')
                ->default(1);

            $table->decimal('costo_unitario', 14, 2)
                ->nullable();

            $table->dateTime('fecha_asignacion');

            $table->dateTime('fecha_retiro')
                ->nullable();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'equipo_id',
                'fecha_retiro',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_componentes');
    }
};