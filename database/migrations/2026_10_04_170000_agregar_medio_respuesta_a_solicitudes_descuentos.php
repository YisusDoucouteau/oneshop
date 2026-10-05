<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'solicitudes_descuentos',
            function (Blueprint $table) {
                $table->string(
                    'medio_respuesta',
                    30
                )
                    ->nullable()
                    ->after('motivo_respuesta');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'solicitudes_descuentos',
            function (Blueprint $table) {
                $table->dropColumn('medio_respuesta');
            }
        );
    }
};
