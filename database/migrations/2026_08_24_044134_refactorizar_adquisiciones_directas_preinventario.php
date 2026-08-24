<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Quitar dependencia obligatoria con Equipo
        |--------------------------------------------------------------------------
        |
        | Una adquisición directa debe poder existir ANTES de que Dani
        | incorpore físicamente la unidad al inventario en Oruro.
        |
        */

        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {
                $table->dropForeign(
                    'adquisiciones_directas_equipo_id_foreign'
                );

                $table->dropUnique(
                    'adquisiciones_directas_equipo_id_unique'
                );
            }
        );

        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {
                $table->dropColumn('equipo_id');
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Datos propios de la adquisición
        |--------------------------------------------------------------------------
        |
        | Esta estructura permite registrar una compra directa realizada
        | por Hugo en Cochabamba o por Dani en Oruro, aunque todavía no
        | exista un Equipo con número de inventario.
        |
        */

        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {

                $table->unsignedBigInteger('producto_id')
                    ->after('id');

                $table->unsignedBigInteger(
                    'almacen_recepcion_id'
                )
                    ->nullable()
                    ->after('proveedor_id');

                $table->unsignedInteger(
                    'cantidad_esperada'
                )
                    ->default(1)
                    ->after('almacen_recepcion_id');

                $table->unsignedBigInteger(
                    'moneda_id'
                )
                    ->nullable()
                    ->after('cantidad_esperada');

                $table->unsignedBigInteger(
                    'tipo_cambio_compra_id'
                )
                    ->nullable()
                    ->after('moneda_id');

                $table->decimal(
                    'costo_unitario_origen',
                    14,
                    2
                )
                    ->nullable()
                    ->after('tipo_cambio_compra_id');

                $table->decimal(
                    'costo_unitario_bob',
                    14,
                    2
                )
                    ->nullable()
                    ->after('costo_unitario_origen');

                $table->unsignedBigInteger(
                    'comprado_por_id'
                )
                    ->nullable()
                    ->after('fecha_adquisicion');


                /*
                 * Claves foráneas con nombres breves para evitar
                 * nuevamente el límite de 64 caracteres de MySQL.
                 */

                $table->foreign(
                    'producto_id',
                    'fk_adq_directa_producto'
                )
                    ->references('id')
                    ->on('productos')
                    ->restrictOnDelete();

                $table->foreign(
                    'almacen_recepcion_id',
                    'fk_adq_directa_almacen'
                )
                    ->references('id')
                    ->on('almacenes')
                    ->nullOnDelete();

                $table->foreign(
                    'moneda_id',
                    'fk_adq_directa_moneda'
                )
                    ->references('id')
                    ->on('monedas')
                    ->restrictOnDelete();

                $table->foreign(
                    'tipo_cambio_compra_id',
                    'fk_adq_directa_tc'
                )
                    ->references('id')
                    ->on('tipos_cambio')
                    ->restrictOnDelete();

                $table->foreign(
                    'comprado_por_id',
                    'fk_adq_directa_comprador'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                $table->index(
                    'producto_id',
                    'idx_adq_directa_producto'
                );

                $table->index(
                    'almacen_recepcion_id',
                    'idx_adq_directa_almacen'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {

                $table->dropForeign(
                    'fk_adq_directa_producto'
                );

                $table->dropForeign(
                    'fk_adq_directa_almacen'
                );

                $table->dropForeign(
                    'fk_adq_directa_moneda'
                );

                $table->dropForeign(
                    'fk_adq_directa_tc'
                );

                $table->dropForeign(
                    'fk_adq_directa_comprador'
                );

                $table->dropIndex(
                    'idx_adq_directa_producto'
                );

                $table->dropIndex(
                    'idx_adq_directa_almacen'
                );
            }
        );

        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {
                $table->dropColumn([
                    'producto_id',
                    'almacen_recepcion_id',
                    'cantidad_esperada',
                    'moneda_id',
                    'tipo_cambio_compra_id',
                    'costo_unitario_origen',
                    'costo_unitario_bob',
                    'comprado_por_id',
                ]);
            }
        );

        /*
         * Restauración del diseño anterior.
         * Es segura mientras no existan adquisiciones creadas
         * con el nuevo modelo.
         */
        Schema::table(
            'adquisiciones_directas',
            function (Blueprint $table) {
                $table->unsignedBigInteger(
                    'equipo_id'
                );

                $table->unique(
                    'equipo_id',
                    'adquisiciones_directas_equipo_id_unique'
                );

                $table->foreign(
                    'equipo_id',
                    'adquisiciones_directas_equipo_id_foreign'
                )
                    ->references('id')
                    ->on('equipos')
                    ->restrictOnDelete();
            }
        );
    }
};