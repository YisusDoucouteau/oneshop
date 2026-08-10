<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('accion', 50);

            $table->string('entidad', 80);

            $table->unsignedBigInteger('entidad_id')
                ->nullable();

            $table->json('datos_anteriores')
                ->nullable();

            $table->json('datos_nuevos')
                ->nullable();

            $table->string('direccion_ip', 45)
                ->nullable();

            $table->text('agente_usuario')
                ->nullable();

            $table->string('ruta', 255)
                ->nullable();

            $table->string('metodo_http', 10)
                ->nullable();

            $table->timestamp('fecha_evento')
                ->useCurrent();

            $table->index(
                ['entidad', 'entidad_id'],
                'idx_auditoria_entidad'
            );

            $table->index(
                ['usuario_id', 'fecha_evento'],
                'idx_auditoria_usuario_fecha'
            );

            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};