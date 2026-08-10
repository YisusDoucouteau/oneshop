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
    Schema::create('prorrogas_reservas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('reserva_id')
            ->constrained('reservas')
            ->restrictOnDelete();

        $table->foreignId('autorizado_por_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->dateTime('fecha_expiracion_anterior');

        $table->dateTime('nueva_fecha_expiracion');

        $table->string('motivo', 255);

        $table->timestamp('created_at')
            ->useCurrent();

        $table->index([
            'reserva_id',
            'created_at',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('prorrogas_reservas');
}
};
