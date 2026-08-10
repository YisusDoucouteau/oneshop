<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiarios_distribucion', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 60)
                ->unique();

            $table->string('nombre', 120);

            $table->string('tipo', 30);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiarios_distribucion');
    }
};