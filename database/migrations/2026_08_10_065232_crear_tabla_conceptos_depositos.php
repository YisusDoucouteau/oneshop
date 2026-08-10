<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conceptos_depositos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 60)->unique();

            $table->string('nombre', 120);

            $table->string('descripcion', 255)
                ->nullable();

            $table->boolean('requiere_beneficiario')
                ->default(false);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conceptos_depositos');
    }
};