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

                $table->string(
                    'grado_recibido',
                    10
                )
                ->nullable()
                ->after('serial_fabricante');


                $table->string(
                    'grado_final',
                    10
                )
                ->nullable()
                ->after('grado_recibido');

            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {

                $table->dropColumn([
                    'grado_recibido',
                    'grado_final',
                ]);

            }
        );
    }

};