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
    Schema::create('eventos_logisticos_lotes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('lote_id')
            ->constrained('lotes')
            ->restrictOnDelete();

        $table->foreignId('tipo_evento_logistico_id')
            ->constrained('tipos_eventos_logisticos')
            ->restrictOnDelete();

        $table->foreignId('usuario_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->dateTime('fecha_evento');

        $table->string('ubicacion', 150)
            ->nullable();

        $table->text('descripcion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'lote_id',
            'fecha_evento',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('eventos_logisticos_lotes');
}
};
