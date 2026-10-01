<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'existencias_productos',
            function (Blueprint $table) {
                $table->decimal(
                    'costo_promedio_bob',
                    14,
                    6
                )
                    ->nullable()
                    ->after('cantidad_reservada');
            }
        );

        Schema::table(
            'movimientos_inventario',
            function (Blueprint $table) {
                $table->foreignId('moneda_id')
                    ->nullable()
                    ->after('usuario_id')
                    ->constrained('monedas')
                    ->restrictOnDelete();

                $table->foreignId('tipo_cambio_id')
                    ->nullable()
                    ->after('moneda_id')
                    ->constrained('tipos_cambio')
                    ->restrictOnDelete();

                $table->decimal(
                    'costo_unitario_origen',
                    14,
                    6
                )
                    ->nullable()
                    ->after('tipo_cambio_id');

                $table->decimal(
                    'costo_total_origen',
                    14,
                    2
                )
                    ->nullable()
                    ->after('costo_unitario_origen');

                $table->decimal(
                    'costo_unitario_bob',
                    14,
                    6
                )
                    ->nullable()
                    ->after('costo_total_origen');

                $table->decimal(
                    'costo_total_bob',
                    14,
                    2
                )
                    ->nullable()
                    ->after('costo_unitario_bob');

                $table->decimal(
                    'costo_promedio_resultante_bob',
                    14,
                    6
                )
                    ->nullable()
                    ->after('costo_total_bob');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'movimientos_inventario',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId(
                    'tipo_cambio_id'
                );

                $table->dropConstrainedForeignId(
                    'moneda_id'
                );

                $table->dropColumn([
                    'costo_unitario_origen',
                    'costo_total_origen',
                    'costo_unitario_bob',
                    'costo_total_bob',
                    'costo_promedio_resultante_bob',
                ]);
            }
        );

        Schema::table(
            'existencias_productos',
            function (Blueprint $table) {
                $table->dropColumn(
                    'costo_promedio_bob'
                );
            }
        );
    }
};
