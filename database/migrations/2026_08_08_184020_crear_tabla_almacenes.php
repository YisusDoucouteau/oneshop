<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->string('nombre', 120);

            $table->string('ciudad', 100)
                ->nullable();

            $table->string('direccion', 255)
                ->nullable();

            $table->boolean('principal')
                ->default(false);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};