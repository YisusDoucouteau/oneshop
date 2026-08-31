<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_aprobacion_precios', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Equipo involucrado
            |--------------------------------------------------------------------------
            */

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Usuario que solicita
            |--------------------------------------------------------------------------
            |
            | Puede ser vendedor, administrador u otro usuario autorizado.
            |
            */

            $table->foreignId('usuario_solicitante_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Valores comerciales
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'precio_publicado',
                12,
                2
            );

            $table->decimal(
                'precio_propuesto',
                12,
                2
            );

            $table->decimal(
                'descuento_solicitado',
                12,
                2
            )
            ->default(0);


            $table->decimal(
                'ganancia_estimada',
                12,
                2
            )
            ->default(0);


            /*
            |--------------------------------------------------------------------------
            | Información adicional
            |--------------------------------------------------------------------------
            */

            $table->text('motivo')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Estado del flujo
            |--------------------------------------------------------------------------
            |
            | PENDIENTE
            | APROBADA
            | RECHAZADA
            | VENCIDA
            |
            */

            $table->string(
                'estado',
                30
            )
            ->default('PENDIENTE');


            /*
            |--------------------------------------------------------------------------
            | Resolución
            |--------------------------------------------------------------------------
            */

            $table->foreignId('usuario_aprobador_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            $table->timestamp(
                'fecha_aprobacion'
            )
            ->nullable();


            $table->text(
                'observacion_aprobacion'
            )
            ->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'equipo_id',
                'estado'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'solicitud_aprobacion_precios'
        );
    }
};