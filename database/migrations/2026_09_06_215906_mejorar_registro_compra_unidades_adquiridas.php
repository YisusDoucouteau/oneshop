<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {


                /*
                |--------------------------------------------------------------------------
                | Identificación rápida Hugo
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'nombre_equipo',
                    150
                )
                ->nullable()
                ->after('producto_id');


                $table->string(
                    'modelo_equipo',
                    150
                )
                ->nullable()
                ->after('nombre_equipo');


                /*
                |--------------------------------------------------------------------------
                | Datos económicos de compra
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'precio_compra',
                    10,
                    2
                )
                ->nullable()
                ->after('modelo_equipo');


                $table->unsignedBigInteger(
                    'moneda_id'
                )
                ->nullable()
                ->after('precio_compra');


                $table->foreign(
                    'moneda_id',
                    'fk_unidad_adq_moneda'
                )
                ->references('id')
                ->on('monedas')
                ->nullOnDelete();

            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {

                $table->dropForeign(
                    'fk_unidad_adq_moneda'
                );


                $table->dropColumn([
                    'nombre_equipo',
                    'modelo_equipo',
                    'precio_compra',
                    'moneda_id',
                ]);

            }
        );
    }
};