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
    Schema::create('detalles_reservas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('reserva_id')
            ->constrained('reservas')
            ->cascadeOnDelete();

        $table->foreignId('equipo_id')
            ->constrained('equipos')
            ->restrictOnDelete();

        $table->decimal('precio_acordado', 14, 2);

        $table->decimal('descuento_acordado', 14, 2)
            ->default(0);

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->unique(
            ['reserva_id', 'equipo_id'],
            'uq_reserva_equipo'
        );
    });
}

public function down(): void
{
    Schema::dropIfExists('detalles_reservas');
}
};
