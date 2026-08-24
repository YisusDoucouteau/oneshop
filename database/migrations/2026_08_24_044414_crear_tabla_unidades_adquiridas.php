<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'unidades_adquiridas',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Procedencia
                |--------------------------------------------------------------------------
                |
                | La unidad puede provenir:
                |
                | 1. De una línea de lote.
                | 2. De una adquisición directa.
                |
                | Nunca debería tener ambas procedencias simultáneamente.
                |
                */

                $table->unsignedBigInteger(
                    'detalle_lote_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'adquisicion_directa_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Producto
                |--------------------------------------------------------------------------
                |
                | Se guarda también producto_id para facilitar consultas,
                | aunque la procedencia conserve la evidencia de compra.
                |
                */

                $table->unsignedBigInteger(
                    'producto_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Ubicación y estado previo al inventario
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'almacen_actual_id'
                );

                $table->string(
                    'estado',
                    40
                )->default(
                    'PENDIENTE_LLEGADA'
                );

                /*
                |--------------------------------------------------------------------------
                | Fechas logísticas
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'fecha_llegada'
                )->nullable();

                $table->dateTime(
                    'fecha_revision'
                )->nullable();

                $table->dateTime(
                    'fecha_lista_envio'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Identificación preliminar
                |--------------------------------------------------------------------------
                |
                | Hugo no asigna el número de inventario de OneShop.
                |
                | El serial del fabricante puede conocerse o no.
                | No es obligatorio.
                |
                */

                $table->string(
                    'serial_fabricante',
                    150
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Revisión preliminar en Cochabamba
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'procesador',
                    150
                )->nullable();

                $table->string(
                    'generacion_procesador',
                    80
                )->nullable();

                $table->unsignedSmallInteger(
                    'ram_gb'
                )->nullable();

                $table->unsignedInteger(
                    'almacenamiento_gb'
                )->nullable();

                $table->string(
                    'tipo_almacenamiento',
                    50
                )->nullable();

                $table->string(
                    'tarjeta_grafica',
                    150
                )->nullable();

                $table->decimal(
                    'pantalla_pulgadas',
                    4,
                    1
                )->nullable();

                $table->string(
                    'resolucion',
                    50
                )->nullable();

                $table->string(
                    'sistema_operativo',
                    100
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Revisión básica
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'enciende'
                )->nullable();

                $table->boolean(
                    'tiene_sistema_operativo'
                )->nullable();

                $table->boolean(
                    'tiene_cargador'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Preparación
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'requiere_servicio'
                )->default(false);

                $table->text(
                    'servicio_requerido'
                )->nullable();

                $table->text(
                    'observacion_revision'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Responsables
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'registrado_por_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'revisado_por_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Conversión posterior a Equipo
                |--------------------------------------------------------------------------
                |
                | Cuando Dani incorpore la máquina al inventario,
                | vincularemos esta unidad con el Equipo definitivo.
                |
                */

                $table->unsignedBigInteger(
                    'equipo_id'
                )->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Claves foráneas
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'detalle_lote_id',
                    'fk_unidad_adq_detalle'
                )
                    ->references('id')
                    ->on('detalles_lotes')
                    ->restrictOnDelete();

                $table->foreign(
                    'adquisicion_directa_id',
                    'fk_unidad_adq_directa'
                )
                    ->references('id')
                    ->on('adquisiciones_directas')
                    ->restrictOnDelete();

                $table->foreign(
                    'producto_id',
                    'fk_unidad_adq_producto'
                )
                    ->references('id')
                    ->on('productos')
                    ->restrictOnDelete();

                $table->foreign(
                    'almacen_actual_id',
                    'fk_unidad_adq_almacen'
                )
                    ->references('id')
                    ->on('almacenes')
                    ->restrictOnDelete();

                $table->foreign(
                    'registrado_por_id',
                    'fk_unidad_adq_registrador'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign(
                    'revisado_por_id',
                    'fk_unidad_adq_revisor'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign(
                    'equipo_id',
                    'fk_unidad_adq_equipo'
                )
                    ->references('id')
                    ->on('equipos')
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'almacen_actual_id',
                        'estado',
                    ],
                    'idx_unidad_adq_ubic_estado'
                );

                $table->index(
                    'fecha_llegada',
                    'idx_unidad_adq_fecha_llegada'
                );

                $table->index(
                    'fecha_lista_envio',
                    'idx_unidad_adq_lista_envio'
                );

                /*
                 * Una unidad preinventario solo puede convertirse
                 * una vez en Equipo.
                 */
                $table->unique(
                    'equipo_id',
                    'uq_unidad_adq_equipo'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'unidades_adquiridas'
        );
    }
};