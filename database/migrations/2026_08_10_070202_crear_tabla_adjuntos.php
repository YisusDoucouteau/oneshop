<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('archivo_id')
                ->constrained('archivos')
                ->restrictOnDelete();

            $table->string('entidad', 60);

            $table->unsignedBigInteger('entidad_id');

            $table->string('tipo_adjunto', 60);

            $table->string('descripcion', 255)
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['entidad', 'entidad_id'],
                'idx_adjunto_entidad'
            );

            $table->index(
                ['tipo_adjunto', 'entidad'],
                'idx_adjunto_tipo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};