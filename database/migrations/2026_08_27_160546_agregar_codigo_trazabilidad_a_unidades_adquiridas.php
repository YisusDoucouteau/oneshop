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
        | Código temporal de trazabilidad
        |--------------------------------------------------------------------------
        |
        | Identifica a la unidad desde su llegada física a Cochabamba
        | hasta su incorporación formal al inventario de OneShop.
        |
        | Ejemplo:
        |
        | OS-260827-0023
        |
        | Este código NO reemplaza:
        | - el serial del fabricante;
        | - el código interno comercial asignado posteriormente en Oruro.
        |
        | Se deja nullable para conservar correctamente unidades históricas
        | creadas antes de incorporar esta funcionalidad.
        |
        */
        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {
                $table
                    ->string(
                        'codigo_trazabilidad',
                        20
                    )
                    ->nullable()
                    ->after('estado');

                $table->unique(
                    'codigo_trazabilidad',
                    'uq_unidad_adq_codigo_trazabilidad'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Correlativo diario
        |--------------------------------------------------------------------------
        |
        | Evita utilizar count() + 1.
        |
        | Cada fecha mantiene su propio contador:
        |
        | 2026-08-27 -> 23
        |
        | siguiente:
        | OS-260827-0024
        |
        | La generación se realizará dentro de una transacción
        | utilizando bloqueo de fila.
        |
        */
        Schema::create(
            'correlativos_trazabilidad_unidades',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->date('fecha')
                    ->unique(
                        'uq_correlativo_trazabilidad_fecha'
                    );

                $table
                    ->unsignedInteger(
                        'ultimo_correlativo'
                    )
                    ->default(0);

                $table->timestamps();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'correlativos_trazabilidad_unidades'
        );

        Schema::table(
            'unidades_adquiridas',
            function (Blueprint $table) {
                $table->dropUnique(
                    'uq_unidad_adq_codigo_trazabilidad'
                );

                $table->dropColumn(
                    'codigo_trazabilidad'
                );
            }
        );
    }
};