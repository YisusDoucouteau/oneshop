<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table(
            'costos_lotes',
            function (Blueprint $table) {


                $table->foreignId('anulado_por_id')
                    ->nullable()
                    ->after('registrado_por_id')
                    ->constrained('users');


                $table->timestamp('fecha_anulacion')
                    ->nullable()
                    ->after('anulado_por_id');


                $table->string('motivo_anulacion',255)
                    ->nullable()
                    ->after('fecha_anulacion');


            }
        );

    }



    public function down(): void
    {

        Schema::table(
            'costos_lotes',
            function (Blueprint $table) {


                $table->dropForeign([
                    'anulado_por_id'
                ]);


                $table->dropColumn([
                    'anulado_por_id',
                    'fecha_anulacion',
                    'motivo_anulacion'
                ]);

            }
        );

    }

};