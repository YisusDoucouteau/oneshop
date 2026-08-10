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
        | Solicitudes de descuento
        |--------------------------------------------------------------------------
        | El precio_equipo_id ya determina el equipo correspondiente.
        */

        Schema::table('solicitudes_descuentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Archivos
        |--------------------------------------------------------------------------
        | Los comprobantes se administran mediante archivos + adjuntos.
        */

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('comprobante');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('comprobante');
        });

        /*
        |--------------------------------------------------------------------------
        | Anulación de ventas
        |--------------------------------------------------------------------------
        */

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('anulado_por_id')
                ->nullable()
                ->after('estado')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_anulacion')
                ->nullable()
                ->after('anulado_por_id');

            $table->string('motivo_anulacion', 255)
                ->nullable()
                ->after('fecha_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['anulado_por_id']);

            $table->dropColumn([
                'anulado_por_id',
                'fecha_anulacion',
                'motivo_anulacion',
            ]);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->string('comprobante', 500)->nullable();
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->string('comprobante', 500)->nullable();
        });

        Schema::table('solicitudes_descuentos', function (Blueprint $table) {
            $table->foreignId('equipo_id')
                ->nullable()
                ->constrained('equipos')
                ->restrictOnDelete();
        });
    }
};