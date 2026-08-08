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
    Schema::create('detalles_revisiones_tecnicas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('revision_tecnica_id')
            ->constrained('revisiones_tecnicas')
            ->cascadeOnDelete();

        $table->foreignId('item_checklist_id')
            ->constrained('items_checklist')
            ->restrictOnDelete();

        $table->boolean('valor_booleano')
            ->nullable();

        $table->decimal('valor_numerico', 12, 2)
            ->nullable();

        $table->text('valor_texto')
            ->nullable();

        $table->string('valor_opcion', 100)
            ->nullable();

        $table->boolean('cumple')
            ->nullable();

        $table->string('observacion', 255)
            ->nullable();

        $table->timestamps();

        $table->unique(
            ['revision_tecnica_id', 'item_checklist_id'],
            'uq_revision_item'
        );
    });
}

public function down(): void
{
    Schema::dropIfExists('detalles_revisiones_tecnicas');
}
};
