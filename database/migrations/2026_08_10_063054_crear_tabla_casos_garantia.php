<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos_garantia', function (Blueprint $table) {
            $table->id();

            $table->string('numero', 50)
                ->unique();

            $table->foreignId('garantia_id')
                ->constrained('garantias')
                ->restrictOnDelete();

            $table->foreignId('equipo_afectado_id')
                ->nullable()
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('recibido_por_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo_caso', 30)
                ->default('GARANTIA');

            $table->string('estado', 40)
                ->default('ABIERTO');

            $table->dateTime('fecha_apertura');

            $table->text('motivo_cliente');

            $table->text('diagnostico_final')
                ->nullable();

            $table->string('resolucion', 40)
                ->nullable();

            $table->dateTime('fecha_cierre')
                ->nullable();

            $table->foreignId('cerrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'estado',
                'fecha_apertura',
            ]);

            $table->index([
                'garantia_id',
                'fecha_apertura',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_garantia');
    }
};