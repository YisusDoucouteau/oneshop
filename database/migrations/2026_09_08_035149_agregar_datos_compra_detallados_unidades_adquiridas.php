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


                $table->date('fecha_compra')
                    ->nullable()
                    ->after('precio_compra_bob');


                $table->string('referencia_compra',150)
                    ->nullable()
                    ->after('fecha_compra');


                $table->string('proveedor_compra',150)
                    ->nullable()
                    ->after('referencia_compra');


            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {


                $table->dropColumn([
                    'fecha_compra',
                    'referencia_compra',
                    'proveedor_compra',
                ]);

            }
        );
    }

};