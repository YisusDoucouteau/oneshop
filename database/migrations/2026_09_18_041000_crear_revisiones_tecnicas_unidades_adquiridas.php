<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones_tecnicas_unidades_adquiridas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unidad_adquirida_id');
            $table->foreignId('usuario_id')->nullable();

            $table->dateTime('fecha_revision');

            $table->string('grado_final', 10)
                ->nullable();

            $table->unsignedTinyInteger('bateria_porcentaje')
                ->nullable();

            $table->boolean('enciende')
                ->nullable();

            $table->boolean('tiene_sistema_operativo')
                ->nullable();

            $table->boolean('tiene_cargador')
                ->nullable();

            $table->boolean('requiere_servicio')
                ->default(false);

            $table->text('servicio_requerido')
                ->nullable();

            $table->json('checklist_tecnico')
                ->nullable();

            $table->string('resultado', 30);

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->foreign(
                'unidad_adquirida_id',
                'fk_revtec_unidad'
            )
                ->references('id')
                ->on('unidades_adquiridas')
                ->cascadeOnDelete();

            $table->foreign(
                'usuario_id',
                'fk_revtec_usuario'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['unidad_adquirida_id', 'fecha_revision'],
                'idx_revision_tecnica_unidad_fecha'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_tecnicas_unidades_adquiridas');
    }
};
