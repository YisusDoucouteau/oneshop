<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->boolean('incluye_cargador')
                ->nullable()
                ->after('unidad_adquirida_id');
        });

        /*
         * Para los envíos ya existentes tomamos como referencia el dato
         * físico registrado en la unidad. A partir de esta migración la
         * decisión de si el cargador VIAJA con el equipo queda congelada
         * en el detalle del envío y deja de depender del estado actual de
         * la unidad.
         */
        DB::statement(<<<'SQL'
            UPDATE envios_importacion_unidades eu
            INNER JOIN unidades_adquiridas ua
                ON ua.id = eu.unidad_adquirida_id
            SET eu.incluye_cargador = COALESCE(ua.tiene_cargador, 0)
        SQL);
    }

    public function down(): void
    {
        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->dropColumn('incluye_cargador');
        });
    }
};
