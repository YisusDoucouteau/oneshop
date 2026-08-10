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
    Schema::create('reservas', function (Blueprint $table) {
        $table->id();

        $table->string('numero', 50)
            ->unique();

        $table->foreignId('cliente_id')
            ->constrained('clientes')
            ->restrictOnDelete();

        $table->foreignId('registrado_por_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->string('estado', 30)
            ->default('ACTIVA');

        $table->dateTime('fecha_reserva');

        $table->dateTime('fecha_expiracion');

        $table->dateTime('fecha_cierre')
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'estado',
            'fecha_expiracion',
        ], 'idx_reserva_estado_expiracion');

        $table->index('cliente_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('reservas');
}
};
