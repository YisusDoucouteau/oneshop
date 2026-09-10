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


                $table->foreignId('tipo_cambio_compra_id')
                    ->nullable()
                    ->after('moneda_id')
                    ->constrained('tipos_cambio')
                    ->nullOnDelete();



                $table->decimal(
                    'precio_compra_bob',
                    10,
                    2
                )
                ->nullable()
                ->after('tipo_cambio_compra_id');


            }
        );
    }



    public function down(): void
    {
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {


                $table->dropForeign([
                    'tipo_cambio_compra_id'
                ]);


                $table->dropColumn([
                    'tipo_cambio_compra_id',
                    'precio_compra_bob'
                ]);


            }
        );
    }

};