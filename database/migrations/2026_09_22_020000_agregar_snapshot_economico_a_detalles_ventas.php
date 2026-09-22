<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_ventas', function (Blueprint $table) {
            $table->foreignId('tipo_cambio_snapshot_id')
                ->nullable()
                ->after('costo_unitario_snapshot')
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->decimal(
                'tipo_cambio_valor_snapshot',
                14,
                6
            )
                ->nullable()
                ->after('tipo_cambio_snapshot_id');

            $table->string(
                'moneda_origen_snapshot',
                10
            )
                ->nullable()
                ->after('tipo_cambio_valor_snapshot');

            $table->decimal(
                'monto_origen_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('moneda_origen_snapshot');

            $table->string(
                'fuente_costo_snapshot',
                50
            )
                ->nullable()
                ->after('monto_origen_snapshot');

            $table->decimal(
                'margen_total_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('fuente_costo_snapshot');

            $table->decimal(
                'ganancia_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('margen_total_snapshot');

            $table->decimal(
                'hugo_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('ganancia_snapshot');

            $table->decimal(
                'daniel_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('hugo_snapshot');

            $table->decimal(
                'tienda_snapshot',
                14,
                2
            )
                ->nullable()
                ->after('daniel_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'tipo_cambio_snapshot_id'
            );

            $table->dropColumn([
                'tipo_cambio_valor_snapshot',
                'moneda_origen_snapshot',
                'monto_origen_snapshot',
                'fuente_costo_snapshot',
                'margen_total_snapshot',
                'ganancia_snapshot',
                'hugo_snapshot',
                'daniel_snapshot',
                'tienda_snapshot',
            ]);
        });
    }
};
