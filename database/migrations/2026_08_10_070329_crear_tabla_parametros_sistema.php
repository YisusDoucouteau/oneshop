<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_sistema', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 100)
                ->unique();

            $table->string('nombre', 150);

            $table->string('modulo', 50);

            $table->string('tipo_dato', 30);

            $table->text('valor');

            $table->string('descripcion', 255)
                ->nullable();

            $table->boolean('editable')
                ->default(true);

            $table->boolean('activo')
                ->default(true);

            $table->foreignId('modificado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['modulo', 'activo'],
                'idx_parametro_modulo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_sistema');
    }
};