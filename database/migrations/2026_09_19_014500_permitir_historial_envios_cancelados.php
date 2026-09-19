<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * La restricción UNIQUE sobre unidad_adquirida_id servía también
         * como índice para la FK. MySQL no permite eliminar ese índice
         * mientras la FK lo esté utilizando, por lo que primero retiramos
         * temporalmente la FK, sustituimos el índice UNIQUE por uno normal
         * y finalmente restauramos la FK.
         *
         * De esta forma una unidad puede conservar historial en envíos
         * cancelados y participar posteriormente en otro envío activo.
         * La exclusividad de envíos activos queda controlada por el servicio.
         */
        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->dropForeign('fk_env_unid_unidad');
            $table->dropUnique('uq_env_unid_unidad');

            $table->index(
                'unidad_adquirida_id',
                'idx_env_unid_unidad_hist'
            );

            $table->foreign(
                'unidad_adquirida_id',
                'fk_env_unid_unidad'
            )
                ->references('id')
                ->on('unidades_adquiridas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        /*
         * Para restaurar la regla anterior hacemos la operación inversa:
         * retiramos temporalmente la FK, quitamos el índice normal,
         * restauramos el UNIQUE y volvemos a crear la FK.
         */
        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->dropForeign('fk_env_unid_unidad');
            $table->dropIndex('idx_env_unid_unidad_hist');

            $table->unique(
                'unidad_adquirida_id',
                'uq_env_unid_unidad'
            );

            $table->foreign(
                'unidad_adquirida_id',
                'fk_env_unid_unidad'
            )
                ->references('id')
                ->on('unidades_adquiridas')
                ->restrictOnDelete();
        });
    }
};
