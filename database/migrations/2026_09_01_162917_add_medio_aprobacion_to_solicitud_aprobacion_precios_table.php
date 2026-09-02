<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'solicitud_aprobacion_precios',
            function (Blueprint $table) {

                $table->string(
                    'medio_aprobacion',
                    30
                )
                ->nullable()
                ->after('observacion_aprobacion');

            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'solicitud_aprobacion_precios',
            function (Blueprint $table) {

                $table->dropColumn(
                    'medio_aprobacion'
                );

            }
        );
    }
};