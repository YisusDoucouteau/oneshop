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
    Schema::create('notificaciones_reservas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('reserva_id')
            ->constrained('reservas')
            ->restrictOnDelete();

        $table->foreignId('usuario_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->string('tipo', 40);

        $table->string('canal', 30);

        $table->string('destino', 150)
            ->nullable();

        $table->dateTime('fecha_notificacion');

        $table->string('resultado', 50)
            ->nullable();

        $table->string('descripcion', 255)
            ->nullable();

        $table->timestamp('created_at')
            ->useCurrent();

        $table->index([
            'reserva_id',
            'fecha_notificacion',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('notificaciones_reservas');
}
};
