<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervenciones_garantia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caso_garantia_id')
                ->constrained('casos_garantia')
                ->cascadeOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('reparacion_id')
                ->nullable()
                ->constrained('reparaciones')
                ->restrictOnDelete();

            $table->string('tipo_intervencion', 40);

            $table->dateTime('fecha_intervencion');

            $table->text('descripcion');

            $table->text('resultado')
                ->nullable();

            $table->timestamps();

$table->index(
    ['caso_garantia_id', 'fecha_intervencion'],
    'idx_intervencion_caso_fecha'
);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenciones_garantia');
    }
};