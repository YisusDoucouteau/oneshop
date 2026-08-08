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
    Schema::create('reparaciones', function (Blueprint $table) {
        $table->id();

        $table->foreignId('equipo_id')
            ->constrained('equipos')
            ->restrictOnDelete();

        $table->foreignId('diagnostico_id')
            ->nullable()
            ->constrained('diagnosticos')
            ->restrictOnDelete();

        $table->foreignId('tecnico_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->foreignId('autorizado_por_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->string('estado', 30)
            ->default('ABIERTA');

        $table->dateTime('fecha_inicio');

        $table->dateTime('fecha_finalizacion')
            ->nullable();

        $table->text('trabajo_realizado')
            ->nullable();

        $table->text('resultado')
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'equipo_id',
            'estado',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('reparaciones');
}
};
