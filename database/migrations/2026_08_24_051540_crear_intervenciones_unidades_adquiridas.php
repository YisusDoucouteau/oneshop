<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'intervenciones_unidades_adquiridas',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Unidad
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'unidad_adquirida_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Tipo de intervención
                |--------------------------------------------------------------------------
                |
                | COMPONENTE:
                | cargador, SSD, RAM, etc.
                |
                | SERVICIO:
                | BIOS, instalación de sistema,
                | mano de obra, reparación básica, etc.
                |
                */

                $table->string(
                    'tipo',
                    30
                );

                /*
                |--------------------------------------------------------------------------
                | Componente
                |--------------------------------------------------------------------------
                |
                | Solo aplica cuando tipo = COMPONENTE.
                |
                | producto_id puede apuntar al catálogo
                | si conocemos exactamente el componente.
                |
                */

                $table->unsignedBigInteger(
                    'producto_id'
                )->nullable();

                /*
                 * COMPRA_EXTERNA:
                 * componente adquirido específicamente
                 * para esta unidad.
                 *
                 * STOCK:
                 * componente tomado de existencias
                 * del depósito.
                 */
                $table->string(
                    'origen_componente',
                    30
                )->nullable();

                $table->unsignedInteger(
                    'cantidad'
                )->default(1);

                $table->unsignedBigInteger(
                    'almacen_id'
                )->nullable();

                /*
                 * Si el componente salió del stock,
                 * posteriormente enlazamos el movimiento
                 * que descontó la existencia.
                 */
                $table->unsignedBigInteger(
                    'movimiento_inventario_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Costo
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'tipo_costo_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'moneda_id'
                )->nullable();

                $table->unsignedBigInteger(
                    'tipo_cambio_id'
                )->nullable();

                $table->decimal(
                    'monto_origen',
                    14,
                    2
                )->nullable();

                $table->decimal(
                    'monto_bob',
                    14,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Servicio / trazabilidad temporal
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'fecha_inicio'
                );

                $table->dateTime(
                    'fecha_fin'
                )->nullable();

                $table->string(
                    'descripcion',
                    255
                );

                $table->text(
                    'resultado'
                )->nullable();

                $table->string(
                    'referencia',
                    150
                )->nullable();

                $table->unsignedBigInteger(
                    'registrado_por_id'
                )->nullable();

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
                    'unidad_adquirida_id',
                    'fk_interv_unidad_adq'
                )
                    ->references('id')
                    ->on('unidades_adquiridas')
                    ->cascadeOnDelete();

                $table->foreign(
                    'producto_id',
                    'fk_interv_producto'
                )
                    ->references('id')
                    ->on('productos')
                    ->restrictOnDelete();

                $table->foreign(
                    'almacen_id',
                    'fk_interv_almacen'
                )
                    ->references('id')
                    ->on('almacenes')
                    ->restrictOnDelete();

                $table->foreign(
                    'movimiento_inventario_id',
                    'fk_interv_movimiento'
                )
                    ->references('id')
                    ->on('movimientos_inventario')
                    ->restrictOnDelete();

                $table->foreign(
                    'tipo_costo_id',
                    'fk_interv_tipo_costo'
                )
                    ->references('id')
                    ->on('tipos_costos')
                    ->restrictOnDelete();

                $table->foreign(
                    'moneda_id',
                    'fk_interv_moneda'
                )
                    ->references('id')
                    ->on('monedas')
                    ->restrictOnDelete();

                $table->foreign(
                    'tipo_cambio_id',
                    'fk_interv_tc'
                )
                    ->references('id')
                    ->on('tipos_cambio')
                    ->restrictOnDelete();

                $table->foreign(
                    'registrado_por_id',
                    'fk_interv_usuario'
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
                        'unidad_adquirida_id',
                        'tipo',
                    ],
                    'idx_interv_unidad_tipo'
                );

                $table->index(
                    'fecha_inicio',
                    'idx_interv_fecha_inicio'
                );

                $table->index(
                    'fecha_fin',
                    'idx_interv_fecha_fin'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'intervenciones_unidades_adquiridas'
        );
    }
};