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
    Schema::create('items_checklist', function (Blueprint $table) {
        $table->id();

        $table->foreignId('plantilla_checklist_id')
            ->constrained('plantillas_checklist')
            ->restrictOnDelete();

        $table->string('codigo', 80);

        $table->string('nombre', 150);

        $table->string('descripcion', 255)
            ->nullable();

        $table->string('tipo_respuesta', 30)
            ->default('BOOLEANO');

        $table->string('unidad', 20)
            ->nullable();

        $table->decimal('valor_minimo', 12, 2)
            ->nullable();

        $table->decimal('valor_maximo', 12, 2)
            ->nullable();

        $table->json('opciones')
            ->nullable();

        $table->boolean('requerido')
            ->default(true);

        $table->unsignedSmallInteger('orden')
            ->default(0);

        $table->boolean('activo')
            ->default(true);

        $table->timestamps();

        $table->unique(
            ['plantilla_checklist_id', 'codigo'],
            'uq_item_plantilla_codigo'
        );
    });
}

public function down(): void
{
    Schema::dropIfExists('items_checklist');
}
};
