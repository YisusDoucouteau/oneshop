<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create(
            'incidencias_logisticas_importacion',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Unidad afectada dentro del envío
                |--------------------------------------------------------------------------
                |
                | La incidencia queda vinculada al detalle individual
                | del envío y no solamente al envío general.
                |
                | Esto permite saber exactamente qué unidad física
                | presentó el problema.
                |
                */
                $table
                    ->unsignedBigInteger(
                        'envio_importacion_unidad_id'
                    );

                /*
                |--------------------------------------------------------------------------
                | Clasificación de la incidencia
                |--------------------------------------------------------------------------
                |
                | Por ahora se utiliza string y no ENUM.
                |
                | Ejemplos futuros:
                | - DANIO_TRANSPORTE
                | - EQUIPO_INCORRECTO
                | - ACCESORIO_FALTANTE
                | - EMBALAJE_MANIPULADO
                | - UNIDAD_FALTANTE
                | - OTRO
                |
                | Los valores definitivos se controlarán
                | desde la aplicación.
                |
                */
                $table
                    ->string(
                        'tipo',
                        60
                    );

                /*
                |--------------------------------------------------------------------------
                | Estado de gestión
                |--------------------------------------------------------------------------
                |
                | Flujo inicial previsto:
                |
                | ABIERTA
                |    ↓
                | EN_GESTION
                |    ↓
                | RESUELTA
                |
                */
                $table
                    ->string(
                        'estado',
                        30
                    )
                    ->default(
                        'ABIERTA'
                    );

                /*
                |--------------------------------------------------------------------------
                | Descripción inicial
                |--------------------------------------------------------------------------
                */
                $table
                    ->text(
                        'descripcion'
                    );

                /*
                |--------------------------------------------------------------------------
                | Apertura
                |--------------------------------------------------------------------------
                */
                $table
                    ->timestamp(
                        'fecha_apertura'
                    );

                $table
                    ->unsignedBigInteger(
                        'abierta_por_id'
                    );

                /*
                |--------------------------------------------------------------------------
                | Resolución
                |--------------------------------------------------------------------------
                |
                | Estos campos permanecen NULL mientras
                | la incidencia no haya sido resuelta.
                |
                | El resultado no se restringe todavía
                | mediante ENUM porque primero debemos
                | validar el proceso real de OneShop.
                |
                */
                $table
                    ->string(
                        'resultado',
                        60
                    )
                    ->nullable();

                $table
                    ->text(
                        'detalle_resolucion'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'fecha_resolucion'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'resuelta_por_id'
                    )
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */
                $table->index(
                    'envio_importacion_unidad_id',
                    'idx_incidencia_envio_unidad'
                );

                $table->index(
                    'estado',
                    'idx_incidencia_estado'
                );

                $table->index(
                    'tipo',
                    'idx_incidencia_tipo'
                );

                $table->index(
                    'fecha_apertura',
                    'idx_incidencia_fecha_apertura'
                );


                /*
                |--------------------------------------------------------------------------
                | Llaves foráneas
                |--------------------------------------------------------------------------
                |
                | Utilizamos nombres cortos explícitos para
                | evitar problemas con el límite de nombres
                | de constraints de MySQL.
                |
                */

                $table
                    ->foreign(
                        'envio_importacion_unidad_id',
                        'fk_inc_envio_unidad'
                    )
                    ->references(
                        'id'
                    )
                    ->on(
                        'envios_importacion_unidades'
                    )
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'abierta_por_id',
                        'fk_inc_abierta_usuario'
                    )
                    ->references(
                        'id'
                    )
                    ->on(
                        'users'
                    )
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'resuelta_por_id',
                        'fk_inc_resuelta_usuario'
                    )
                    ->references(
                        'id'
                    )
                    ->on(
                        'users'
                    )
                    ->restrictOnDelete();
            }
        );
    }


    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'incidencias_logisticas_importacion'
        );
    }
};