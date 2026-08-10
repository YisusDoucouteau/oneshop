<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('detalles_ventas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('venta_id')
            ->constrained('ventas')
            ->restrictOnDelete();

        $table->foreignId('producto_id')
            ->constrained('productos')
            ->restrictOnDelete();

        $table->unsignedBigInteger('equipo_id')
            ->nullable();

        $table->unsignedInteger('cantidad')
            ->default(1);

        $table->decimal('precio_lista_snapshot', 14, 2);

        $table->decimal('descuento_unitario', 14, 2)
            ->default(0);

        $table->decimal('precio_unitario', 14, 2);

        $table->decimal('costo_unitario_snapshot', 14, 2)
            ->nullable();

        $table->decimal('subtotal', 14, 2);

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->foreign(
            ['equipo_id', 'producto_id'],
            'fk_detalle_venta_equipo_producto'
        )
            ->references([
                'id',
                'producto_id'
            ])
            ->on('equipos')
            ->restrictOnDelete();

        $table->index('venta_id');
        $table->index('equipo_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('detalles_ventas');
}
};
