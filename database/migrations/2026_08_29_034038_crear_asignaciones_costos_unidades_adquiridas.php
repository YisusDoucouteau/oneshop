<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'asignaciones_costos_unidades_adquiridas',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Relaciones principales
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'costo_lote_id'
                );

                $table->foreignId(
                    'unidad_adquirida_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Información del cálculo
                |--------------------------------------------------------------------------
                |
                | Guarda cómo fue distribuido el costo.
                |
                | Ejemplo:
                |
                | Método:
                | VALOR_ADQUISICION
                |
                | Base individual:
                | 3500
                |
                | Base total:
                | 70000
                |
                |--------------------------------------------------------------------------
                */


                $table->string(
                    'metodo_asignacion',
                    50
                );


                $table->decimal(
                    'base_individual',
                    14,
                    4
                )
                ->nullable();


                $table->decimal(
                    'base_total',
                    14,
                    4
                )
                ->nullable();


                $table->decimal(
                    'porcentaje',
                    10,
                    6
                )
                ->nullable();



                /*
                |--------------------------------------------------------------------------
                | Resultado del prorrateo
                |--------------------------------------------------------------------------
                */


                $table->decimal(
                    'monto_asignado_bob',
                    14,
                    2
                );


                /*
                 * Diferencia por redondeo.
                 *
                 * Ejemplo:
                 * 0.01 Bs restante
                 */
                $table->decimal(
                    'ajuste_redondeo_bob',
                    14,
                    2
                )
                ->default(0);


                $table->text(
                    'observacion'
                )
                ->nullable();


                $table->timestamps();



                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */


                $table->unique(
                    [
                        'costo_lote_id',
                        'unidad_adquirida_id',
                    ],
                    'uq_asig_costo_unidad'
                );


                $table->index(
                    'unidad_adquirida_id',
                    'idx_asig_unidad_adq'
                );



                /*
                |--------------------------------------------------------------------------
                | Relaciones con nombres cortos
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'costo_lote_id',
                    'fk_asig_costo_lote'
                )
                ->references('id')
                ->on('costos_lotes')
                ->cascadeOnDelete();


                $table->foreign(
                    'unidad_adquirida_id',
                    'fk_asig_unidad_adq'
                )
                ->references('id')
                ->on('unidades_adquiridas')
                ->restrictOnDelete();

            }
        );



        /*
        |--------------------------------------------------------------------------
        | Restricciones de negocio
        |--------------------------------------------------------------------------
        */


        DB::statement(
            'ALTER TABLE asignaciones_costos_unidades_adquiridas
             ADD CONSTRAINT chk_asig_unidad_monto
             CHECK (monto_asignado_bob >= 0)'
        );


        DB::statement(
            'ALTER TABLE asignaciones_costos_unidades_adquiridas
             ADD CONSTRAINT chk_asig_unidad_porcentaje
             CHECK (
                porcentaje IS NULL
                OR porcentaje BETWEEN 0 AND 100
             )'
        );

    }



    public function down(): void
    {
        Schema::dropIfExists(
            'asignaciones_costos_unidades_adquiridas'
        );
    }
};