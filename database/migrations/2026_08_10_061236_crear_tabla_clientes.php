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
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();

        $table->string('nombre_completo', 150);

        $table->string('documento', 30)
            ->nullable();

        $table->string('telefono', 30)
            ->nullable();

        $table->string('correo', 150)
            ->nullable();

        $table->string('direccion', 255)
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->boolean('activo')
            ->default(true);

        $table->timestamps();

        $table->index('telefono');
        $table->index('documento');
        $table->index('nombre_completo');
    });
}

public function down(): void
{
    Schema::dropIfExists('clientes');
}
};
