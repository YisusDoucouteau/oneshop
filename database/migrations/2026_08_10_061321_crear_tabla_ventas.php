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
    Schema::create('ventas', function (Blueprint $table) {
        $table->id();

        $table->string('numero', 50)
            ->unique();

        $table->foreignId('cliente_id')
            ->nullable()
            ->constrained('clientes')
            ->restrictOnDelete();

        $table->foreignId('vendedor_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->foreignId('reserva_id')
            ->nullable()
            ->unique()
            ->constrained('reservas')
            ->restrictOnDelete();

        $table->dateTime('fecha_venta');

        $table->decimal('subtotal', 14, 2);

        $table->decimal('descuento_total', 14, 2)
            ->default(0);

        $table->decimal('total', 14, 2);

        $table->string('estado', 30)
            ->default('REGISTRADA');

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'fecha_venta',
            'estado',
        ]);

        $table->index('cliente_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('ventas');
}
};
