<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicas_distribucion', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 60)
                ->unique();

            $table->string('nombre', 120);

            $table->date('vigente_desde');

            $table->date('vigente_hasta')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->string('descripcion', 255)
                ->nullable();

            $table->timestamps();

            $table->index(
                ['activo', 'vigente_desde'],
                'idx_politica_distribucion_vigencia'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_distribucion');
    }
};