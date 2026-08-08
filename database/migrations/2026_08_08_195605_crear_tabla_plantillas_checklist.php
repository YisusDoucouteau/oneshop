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
    Schema::create('plantillas_checklist', function (Blueprint $table) {
        $table->id();

        $table->foreignId('categoria_producto_id')
            ->nullable()
            ->constrained('categorias_productos')
            ->restrictOnDelete();

        $table->string('codigo', 80);

        $table->string('nombre', 150);

        $table->unsignedSmallInteger('version')
            ->default(1);

        $table->string('descripcion', 255)
            ->nullable();

        $table->boolean('activo')
            ->default(true);

        $table->timestamps();

        $table->unique(
            ['codigo', 'version'],
            'uq_plantilla_codigo_version'
        );
    });
}

public function down(): void
{
    Schema::dropIfExists('plantillas_checklist');
}
};
