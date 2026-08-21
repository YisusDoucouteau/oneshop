<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adquisiciones_directas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->unique()
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->nullOnDelete();

            $table->date('fecha_adquisicion');

            $table->string(
                'referencia_compra',
                150
            )->nullable();

            $table->string(
                'origen',
                150
            )->nullable();

            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->index('fecha_adquisicion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'adquisiciones_directas'
        );
    }
};