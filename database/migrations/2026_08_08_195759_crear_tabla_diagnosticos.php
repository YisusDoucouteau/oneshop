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
    Schema::create('diagnosticos', function (Blueprint $table) {
        $table->id();

        $table->foreignId('equipo_id')
            ->constrained('equipos')
            ->restrictOnDelete();

        $table->foreignId('revision_tecnica_id')
            ->nullable()
            ->constrained('revisiones_tecnicas')
            ->restrictOnDelete();

        $table->foreignId('tecnico_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->dateTime('fecha_diagnostico');

        $table->string('nivel', 30)
            ->nullable();

        $table->text('descripcion');

        $table->boolean('requiere_reparacion')
            ->default(false);

        $table->string('estado', 30)
            ->default('ABIERTO');

        $table->timestamps();

        $table->index([
            'equipo_id',
            'estado',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('diagnosticos');
}
};
