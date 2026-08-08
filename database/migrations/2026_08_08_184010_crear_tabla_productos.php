<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_producto_id')
                ->constrained('categorias_productos')
                ->restrictOnDelete();

            $table->foreignId('marca_id')
                ->nullable()
                ->constrained('marcas')
                ->restrictOnDelete();

            $table->string('codigo', 50)->unique();

            $table->string('nombre', 150);

            $table->string('modelo', 120)
                ->nullable();

            $table->text('descripcion')
                ->nullable();

            $table->boolean('es_serializado')
                ->default(false);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->index([
                'categoria_producto_id',
                'activo',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};