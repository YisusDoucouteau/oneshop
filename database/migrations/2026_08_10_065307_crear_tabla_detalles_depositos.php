<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_depositos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deposito_venta_id')
                ->constrained('depositos_ventas')
                ->cascadeOnDelete();

            $table->foreignId('concepto_deposito_id')
                ->constrained('conceptos_depositos')
                ->restrictOnDelete();

            $table->foreignId('beneficiario_id')
                ->nullable()
                ->constrained('beneficiarios_distribucion')
                ->restrictOnDelete();

            $table->decimal('porcentaje_snapshot', 7, 4)
                ->nullable();

            $table->decimal('monto', 14, 2);

            $table->string('descripcion', 255)
                ->nullable();

            $table->timestamps();

            $table->index(
                ['deposito_venta_id', 'concepto_deposito_id'],
                'idx_detalle_deposito_concepto'
            );
        });

        DB::statement(
            'ALTER TABLE detalles_depositos
             ADD CONSTRAINT chk_detalle_deposito_monto
             CHECK (monto >= 0)'
        );

        DB::statement(
            'ALTER TABLE detalles_depositos
             ADD CONSTRAINT chk_detalle_deposito_porcentaje
             CHECK (
                 porcentaje_snapshot IS NULL
                 OR porcentaje_snapshot BETWEEN 0 AND 100
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_depositos');
    }
};