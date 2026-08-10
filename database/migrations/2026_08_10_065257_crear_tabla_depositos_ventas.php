<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depositos_ventas', function (Blueprint $table) {
            $table->id();

            $table->string('numero', 50)
                ->unique();

            $table->foreignId('venta_id')
                ->unique()
                ->constrained('ventas')
                ->restrictOnDelete();

            $table->decimal('monto_venta_snapshot', 14, 2);

            $table->decimal('costo_total_snapshot', 14, 2);

            $table->decimal('comisiones_snapshot', 14, 2)
                ->default(0);

            $table->decimal('utilidad_distribuible', 14, 2);

            $table->dateTime('fecha_habilitada')
                ->nullable();

            $table->dateTime('fecha_deposito')
                ->nullable();

            $table->string('estado', 30)
                ->default('PENDIENTE');

            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'estado',
                'fecha_habilitada',
            ], 'idx_deposito_estado_fecha');
        });

        DB::statement(
            'ALTER TABLE depositos_ventas
             ADD CONSTRAINT chk_deposito_montos
             CHECK (
                monto_venta_snapshot >= 0
                AND costo_total_snapshot >= 0
                AND comisiones_snapshot >= 0
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('depositos_ventas');
    }
};