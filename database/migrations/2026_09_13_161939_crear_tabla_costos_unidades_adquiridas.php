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
            'costos_unidades_adquiridas',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Unidad en etapa previa al inventario
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'unidad_adquirida_id'
                )
                ->constrained(
                    'unidades_adquiridas'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Tipo de costo
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | REPUESTO_EXTERNO
                | SERVICIO_EXTERNO
                |
                */

                $table->foreignId(
                    'tipo_costo_id'
                )
                ->constrained(
                    'tipos_costos'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Conversión monetaria
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'moneda_id'
                )
                ->constrained(
                    'monedas'
                )
                ->restrictOnDelete();


                $table->foreignId(
                    'tipo_cambio_id'
                )
                ->nullable()
                ->constrained(
                    'tipos_cambio'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Montos
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | SSD 35 USD
                | tipo cambio 7
                | monto_bob 245 Bs
                |
                */

                $table->decimal(
                    'monto_origen',
                    14,
                    2
                );


                $table->decimal(
                    'monto_bob',
                    14,
                    2
                );



                $table->date(
                    'fecha_costo'
                );



                /*
                |--------------------------------------------------------------------------
                | Información del gasto
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'referencia',
                    150
                )
                ->nullable();


                $table->foreignId(
                    'registrado_por_id'
                )
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();


                $table->text(
                    'descripcion'
                )
                ->nullable();



                $table->timestamps();



                $table->index(
                    [
                        'unidad_adquirida_id',
                        'fecha_costo',
                    ]
                );

            }
        );



        DB::statement(
            'ALTER TABLE costos_unidades_adquiridas
             ADD CONSTRAINT chk_costo_unidad_adquirida_montos
             CHECK (
                monto_origen > 0
                AND monto_bob > 0
             )'
        );
    }



    public function down(): void
    {
        Schema::dropIfExists(
            'costos_unidades_adquiridas'
        );
    }
};