<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string(
                'cliente_nombre_snapshot',
                180
            )
                ->nullable()
                ->after('cliente_id');

            $table->string(
                'cliente_telefono_snapshot',
                50
            )
                ->nullable()
                ->after('cliente_nombre_snapshot');
        });

        Schema::table('detalles_ventas', function (Blueprint $table) {
            $table->string(
                'marca_snapshot',
                120
            )
                ->nullable()
                ->after('tienda_snapshot');

            $table->string(
                'modelo_snapshot',
                150
            )
                ->nullable()
                ->after('marca_snapshot');

            $table->string(
                'codigo_interno_snapshot',
                100
            )
                ->nullable()
                ->after('modelo_snapshot');

            $table->string(
                'condicion_venta_snapshot',
                20
            )
                ->nullable()
                ->after('codigo_interno_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table(
            'detalles_ventas',
            function (Blueprint $table) {
                $table->dropColumn([
                    'marca_snapshot',
                    'modelo_snapshot',
                    'codigo_interno_snapshot',
                    'condicion_venta_snapshot',
                ]);
            }
        );

        Schema::table(
            'ventas',
            function (Blueprint $table) {
                $table->dropColumn([
                    'cliente_nombre_snapshot',
                    'cliente_telefono_snapshot',
                ]);
            }
        );
    }
};
