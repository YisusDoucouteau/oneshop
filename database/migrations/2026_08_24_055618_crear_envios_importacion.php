<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Envíos de importación
        |--------------------------------------------------------------------------
        |
        | Representa un despacho físico de unidades adquiridas
        | desde Cochabamba hacia Oruro.
        |
        | Un envío NO pertenece a un lote específico.
        | Puede contener unidades provenientes de:
        |
        | - distintos lotes;
        | - adquisiciones directas;
        |
        | La procedencia continúa registrada en cada
        | UnidadAdquirida.
        |
        */

        Schema::create(
            'envios_importacion',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Identificación
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'codigo',
                    60
                )->unique();

                /*
                |--------------------------------------------------------------------------
                | Ubicación
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'almacen_origen_id'
                );

                $table->unsignedBigInteger(
                    'almacen_destino_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                |
                | Valores que posteriormente controlará el servicio:
                |
                | BORRADOR
                | DESPACHADO
                | RECIBIDO_PARCIAL
                | RECIBIDO
                | CANCELADO
                |
                */

                $table->string(
                    'estado',
                    40
                )->default(
                    'BORRADOR'
                );

                /*
                |--------------------------------------------------------------------------
                | Responsables
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'preparado_por_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'despachado_por_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'recibido_por_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Fechas
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'fecha_preparacion'
                )->nullable();

                $table->dateTime(
                    'fecha_despacho'
                )->nullable();

                /*
                 * Representa el momento en que el envío
                 * queda completamente recibido/cerrado
                 * en Oruro.
                 */
                $table->dateTime(
                    'fecha_recepcion'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Transporte
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'transportista',
                    150
                )->nullable();

                $table->string(
                    'numero_guia',
                    120
                )->nullable();

                /*
                 * Un despacho puede viajar en una o
                 * varias cajas/bultos.
                 */
                $table->unsignedInteger(
                    'cantidad_bultos'
                )->default(1);

                $table->text(
                    'observacion'
                )->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Relaciones
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'almacen_origen_id',
                    'fk_env_imp_origen'
                )
                    ->references('id')
                    ->on('almacenes')
                    ->restrictOnDelete();

                $table->foreign(
                    'almacen_destino_id',
                    'fk_env_imp_destino'
                )
                    ->references('id')
                    ->on('almacenes')
                    ->restrictOnDelete();

                $table->foreign(
                    'preparado_por_id',
                    'fk_env_imp_preparado'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign(
                    'despachado_por_id',
                    'fk_env_imp_despachado'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign(
                    'recibido_por_id',
                    'fk_env_imp_recibido'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'almacen_origen_id',
                        'estado',
                    ],
                    'idx_env_imp_origen_estado'
                );

                $table->index(
                    [
                        'almacen_destino_id',
                        'estado',
                    ],
                    'idx_env_imp_destino_estado'
                );

                $table->index(
                    'fecha_despacho',
                    'idx_env_imp_fecha_despacho'
                );

                $table->index(
                    'fecha_recepcion',
                    'idx_env_imp_fecha_recepcion'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Unidades incluidas en cada envío
        |--------------------------------------------------------------------------
        |
        | Conserva qué unidades físicas viajaron en el envío
        | y permite registrar la recepción individual.
        |
        | Una UnidadAdquirida solamente puede pertenecer a
        | un envío de importación.
        |
        */

        Schema::create(
            'envios_importacion_unidades',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'envio_importacion_id'
                );

                $table->unsignedBigInteger(
                    'unidad_adquirida_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Recepción individual
                |--------------------------------------------------------------------------
                |
                | PENDIENTE
                | RECIBIDA
                | FALTANTE
                | INCIDENCIA
                |
                | El servicio controlará las transiciones.
                |
                */

                $table->string(
                    'estado_recepcion',
                    40
                )->default(
                    'PENDIENTE'
                );

                $table->dateTime(
                    'fecha_recepcion'
                )->nullable();

                $table->unsignedBigInteger(
                    'recibido_por_id'
                )->nullable();

                /*
                 * Aquí solo conservamos una observación
                 * inmediata de recepción.
                 *
                 * Las incidencias con seguro/proveedor,
                 * reposición o reembolso se modelarán
                 * posteriormente con su propio ciclo.
                 */
                $table->text(
                    'observacion_recepcion'
                )->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Relaciones
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'envio_importacion_id',
                    'fk_env_unid_envio'
                )
                    ->references('id')
                    ->on('envios_importacion')
                    ->cascadeOnDelete();

                $table->foreign(
                    'unidad_adquirida_id',
                    'fk_env_unid_unidad'
                )
                    ->references('id')
                    ->on('unidades_adquiridas')
                    ->restrictOnDelete();

                $table->foreign(
                    'recibido_por_id',
                    'fk_env_unid_recibido'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Integridad
                |--------------------------------------------------------------------------
                |
                | Una unidad de preinventario no debe estar
                | incluida simultáneamente en dos despachos
                | Cochabamba -> Oruro.
                |
                */

                $table->unique(
                    'unidad_adquirida_id',
                    'uq_env_unid_unidad'
                );

                $table->index(
                    [
                        'envio_importacion_id',
                        'estado_recepcion',
                    ],
                    'idx_env_unid_envio_estado'
                );

                $table->index(
                    'fecha_recepcion',
                    'idx_env_unid_fecha_recepcion'
                );
            }
        );
    }

    public function down(): void
    {
        /*
         * Primero eliminamos la tabla dependiente.
         */
        Schema::dropIfExists(
            'envios_importacion_unidades'
        );

        Schema::dropIfExists(
            'envios_importacion'
        );
    }
};