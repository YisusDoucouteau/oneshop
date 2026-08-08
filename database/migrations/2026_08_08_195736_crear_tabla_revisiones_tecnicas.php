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
    Schema::create('revisiones_tecnicas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('equipo_id')
            ->constrained('equipos')
            ->restrictOnDelete();

        $table->foreignId('plantilla_checklist_id')
            ->constrained('plantillas_checklist')
            ->restrictOnDelete();

        $table->foreignId('tecnico_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->string('tipo_revision', 40)
            ->default('RECEPCION');

        $table->string('estado', 30)
            ->default('EN_PROCESO');

        $table->string('resultado_general', 40)
            ->nullable();

        $table->dateTime('fecha_revision');

        $table->dateTime('fecha_finalizacion')
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'equipo_id',
            'fecha_revision',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('revisiones_tecnicas');
}
};
