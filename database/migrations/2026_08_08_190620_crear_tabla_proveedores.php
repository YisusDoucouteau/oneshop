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
    Schema::create('proveedores', function (Blueprint $table) {
        $table->id();

        $table->string('nombre', 150);

        $table->string('pais', 100)->nullable();
        $table->string('ciudad', 100)->nullable();

        $table->string('telefono', 30)->nullable();
        $table->string('correo', 150)->nullable();

        $table->string('contacto', 150)->nullable();

        $table->text('observacion')->nullable();

        $table->boolean('activo')->default(true);

        $table->timestamps();

        $table->index('nombre');
    });
}

public function down(): void
{
    Schema::dropIfExists('proveedores');
}
};
