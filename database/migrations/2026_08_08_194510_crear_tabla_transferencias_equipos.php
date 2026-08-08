<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_equipos', function (Blueprint $table) {
            $table->foreignId('transferencia_id')
                ->constrained('transferencias')
                ->cascadeOnDelete();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->string('observacion', 255)
                ->nullable();

            $table->primary([
                'transferencia_id',
                'equipo_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_equipos');
    }
};