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
        | Especificaciones esperadas
        |--------------------------------------------------------------------------
        |
        | Información que Yuyo reporta al momento de la compra.
        | NO representa todavía la verificación física en Oruro.
        |
        */

        Schema::create(
            'especificaciones_esperadas_detalles_lotes',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'detalle_lote_id'
                );

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

                $table->json(
                    'datos_adicionales'
                )->nullable();

                $table->timestamps();

                /*
                 * Una sola ficha de características
                 * esperadas por cada línea de compra.
                 */
                $table->unique(
                    'detalle_lote_id',
                    'uq_esp_esperada_detalle'
                );

                $table->foreign(
                    'detalle_lote_id',
                    'fk_esp_esperada_detalle'
                )
                    ->references('id')
                    ->on('detalles_lotes')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Componentes esperados
        |--------------------------------------------------------------------------
        |
        | Ejemplos:
        | cargador, cable, adaptador, base, etc.
        |
        */

        Schema::create(
            'componentes_esperados_detalles_lotes',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'detalle_lote_id'
                );

                $table->string(
                    'nombre',
                    120
                );

                /*
                 * Cantidad esperada por cada unidad.
                 *
                 * Ejemplo:
                 * 3 laptops × 1 cargador
                 * = 3 cargadores esperados.
                 */
                $table->unsignedInteger(
                    'cantidad_por_unidad'
                )->default(1);

                $table->boolean(
                    'incluido_en_compra'
                )->default(true);

                $table->text(
                    'observacion'
                )->nullable();

                $table->timestamps();

                $table->foreign(
                    'detalle_lote_id',
                    'fk_comp_esperado_detalle'
                )
                    ->references('id')
                    ->on('detalles_lotes')
                    ->cascadeOnDelete();

                $table->index(
                    [
                        'detalle_lote_id',
                        'nombre',
                    ],
                    'idx_comp_esperado_detalle_nombre'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'componentes_esperados_detalles_lotes'
        );

        Schema::dropIfExists(
            'especificaciones_esperadas_detalles_lotes'
        );
    }
};