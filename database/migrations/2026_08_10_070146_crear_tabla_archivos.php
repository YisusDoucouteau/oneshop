<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_original', 255);

            $table->string('nombre_guardado', 255);

            $table->string('ruta', 500);

            $table->string('tipo_mime', 120)
                ->nullable();

            $table->unsignedBigInteger('tamano_bytes')
                ->nullable();

            $table->string('hash_sha256', 64)
                ->nullable();

            $table->foreignId('subido_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('activo')
                ->default(true);

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index('hash_sha256');
        });

        DB::statement(
            'ALTER TABLE archivos
             ADD CONSTRAINT chk_archivo_tamano
             CHECK (
                tamano_bytes IS NULL
                OR tamano_bytes >= 0
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};