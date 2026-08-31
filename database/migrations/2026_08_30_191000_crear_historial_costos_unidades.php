<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'historial_costos_unidades',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Unidad
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'unidad_adquirida_id'
                )
                    ->constrained(
                        'unidades_adquiridas'
                    )
                    ->cascadeOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Composición del costo calculado
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'costo_compra',
                    14,
                    2
                );


                $table->decimal(
                    'costos_lote',
                    14,
                    2
                )
                    ->default(0);



                $table->decimal(
                    'intervenciones',
                    14,
                    2
                )
                    ->default(0);



                $table->decimal(
                    'costo_total',
                    14,
                    2
                );



                /*
                |--------------------------------------------------------------------------
                | Estado del cálculo
                |--------------------------------------------------------------------------
                |
                | true:
                | costo completamente valorado
                |
                | false:
                | existen datos pendientes
                |
                */

                $table->boolean(
                    'completo'
                )
                    ->default(true);



                /*
                |--------------------------------------------------------------------------
                | Información adicional
                |--------------------------------------------------------------------------
                |
                | Guarda el detalle del cálculo:
                | costos individuales,
                | advertencias,
                | composición.
                |
                */

                $table->json(
                    'detalle_json'
                )
                    ->nullable();



                /*
                |--------------------------------------------------------------------------
                | Usuario que ejecutó cálculo
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'calculado_por_id'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();



                $table->dateTime(
                    'fecha_calculo'
                );


                $table->timestamps();



                $table->index(
                    [
                        'unidad_adquirida_id',
                        'fecha_calculo',
                    ],
                    'idx_historial_costos_unidad_fecha'
                );

            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'historial_costos_unidades'
        );
    }
};