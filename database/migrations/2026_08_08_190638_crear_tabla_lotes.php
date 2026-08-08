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
    Schema::create('lotes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('proveedor_id')
            ->nullable()
            ->constrained('proveedores')
            ->restrictOnDelete();

        $table->string('codigo', 50)->unique();

        $table->string('referencia_compra', 100)
            ->nullable();

        $table->string('origen', 150)
            ->nullable();

        $table->string('estado', 30)
            ->default('ABIERTO');

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index('estado');
    });
}

public function down(): void
{
    Schema::dropIfExists('lotes');
}
};
