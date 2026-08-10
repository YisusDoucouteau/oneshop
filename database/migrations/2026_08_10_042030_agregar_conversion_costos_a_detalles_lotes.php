<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_lotes', function (Blueprint $table) {
            $table->foreignId('tipo_cambio_compra_id')
                ->nullable()
                ->after('moneda_id')
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->decimal('costo_unitario_bob', 14, 2)
                ->nullable()
                ->after('costo_unitario_origen');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_lotes', function (Blueprint $table) {
            $table->dropForeign([
                'tipo_cambio_compra_id'
            ]);

            $table->dropColumn([
                'tipo_cambio_compra_id',
                'costo_unitario_bob',
            ]);
        });
    }
};