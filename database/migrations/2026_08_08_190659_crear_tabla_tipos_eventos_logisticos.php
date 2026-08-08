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
    Schema::create('tipos_eventos_logisticos', function (Blueprint $table) {
        $table->id();

        $table->string('codigo', 60)->unique();
        $table->string('nombre', 120);

        $table->string('descripcion', 255)
            ->nullable();

        $table->unsignedSmallInteger('orden')
            ->default(0);

        $table->boolean('activo')
            ->default(true);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('tipos_eventos_logisticos');
}
};
