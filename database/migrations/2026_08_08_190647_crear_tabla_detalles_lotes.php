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
    Schema::create('detalles_lotes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('lote_id')
            ->constrained('lotes')
            ->restrictOnDelete();

        $table->foreignId('producto_id')
            ->constrained('productos')
            ->restrictOnDelete();

        $table->foreignId('moneda_id')
            ->nullable()
            ->constrained('monedas')
            ->restrictOnDelete();

        $table->unsignedInteger('cantidad_esperada');

        $table->unsignedInteger('cantidad_recibida')
            ->default(0);

        $table->decimal('costo_unitario_origen', 14, 2)
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'lote_id',
            'producto_id',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('detalles_lotes');
}
};
