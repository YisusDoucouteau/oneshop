<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_importacion', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_bultos_recibidos')
                ->nullable()
                ->after('cantidad_bultos');

            $table->unsignedInteger('cantidad_cargadores_adicionales_recibidos')
                ->nullable()
                ->after('cantidad_cargadores');

            $table->unsignedInteger('cantidad_accesorios_recibidos')
                ->nullable()
                ->after('cantidad_accesorios');

            $table->text('observacion_recepcion_general')
                ->nullable()
                ->after('detalle_accesorios');

            $table->unsignedBigInteger('verificado_recepcion_por_id')
                ->nullable()
                ->after('recibido_por_id');

            $table->dateTime('fecha_verificacion_recepcion')
                ->nullable()
                ->after('fecha_recepcion');

            $table->foreign(
                'verificado_recepcion_por_id',
                'fk_env_imp_verif_recep'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->boolean('cargador_recibido')
                ->nullable()
                ->after('incluye_cargador');
        });
    }

    public function down(): void
    {
        Schema::table('envios_importacion_unidades', function (Blueprint $table) {
            $table->dropColumn('cargador_recibido');
        });

        Schema::table('envios_importacion', function (Blueprint $table) {
            $table->dropForeign('fk_env_imp_verif_recep');
            $table->dropColumn([
                'cantidad_bultos_recibidos',
                'cantidad_cargadores_adicionales_recibidos',
                'cantidad_accesorios_recibidos',
                'observacion_recepcion_general',
                'verificado_recepcion_por_id',
                'fecha_verificacion_recepcion',
            ]);
        });
    }
};
