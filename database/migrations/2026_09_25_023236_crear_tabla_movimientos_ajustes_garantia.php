<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'movimientos_ajustes_garantia',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('cambio_equipo_id')
                    ->constrained('cambios_equipos')
                    ->restrictOnDelete();

                $table
                    ->string('tipo_movimiento', 30);

                $table
                    ->foreignId('metodo_pago_id')
                    ->constrained('metodos_pago')
                    ->restrictOnDelete();

                $table
                    ->decimal('monto', 14, 2);

                $table
                    ->dateTime('fecha_movimiento');

                $table
                    ->string('referencia', 150)
                    ->nullable();

                $table
                    ->string('comprobante', 500)
                    ->nullable();

                $table
                    ->string('estado', 30)
                    ->default('PENDIENTE');

                $table
                    ->foreignId('registrado_por_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table
                    ->foreignId('verificado_por_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->dateTime('fecha_verificacion')
                    ->nullable();

                $table
                    ->string('motivo_rechazo', 255)
                    ->nullable();

                $table
                    ->text('observacion')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'cambio_equipo_id',
                    'estado',
                ]);

                $table->index([
                    'estado',
                    'fecha_movimiento',
                ]);

                $table->index([
                    'tipo_movimiento',
                    'estado',
                ]);
            }
        );

        DB::statement(
            "ALTER TABLE movimientos_ajustes_garantia
             ADD CONSTRAINT chk_mov_ajuste_garantia_monto
             CHECK (monto > 0)"
        );

        DB::statement(
            "ALTER TABLE movimientos_ajustes_garantia
             ADD CONSTRAINT chk_mov_ajuste_garantia_tipo
             CHECK (
                tipo_movimiento IN (
                    'COBRO',
                    'DEVOLUCION'
                )
             )"
        );

        DB::statement(
            "ALTER TABLE movimientos_ajustes_garantia
             ADD CONSTRAINT chk_mov_ajuste_garantia_estado
             CHECK (
                estado IN (
                    'PENDIENTE',
                    'VERIFICADO',
                    'RECHAZADO'
                )
             )"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'movimientos_ajustes_garantia'
        );
    }
};
