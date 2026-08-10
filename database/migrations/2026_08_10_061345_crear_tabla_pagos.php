<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('pagos', function (Blueprint $table) {
        $table->id();

        $table->foreignId('reserva_id')
            ->nullable()
            ->constrained('reservas')
            ->restrictOnDelete();

        $table->foreignId('venta_id')
            ->nullable()
            ->constrained('ventas')
            ->restrictOnDelete();

        $table->foreignId('metodo_pago_id')
            ->constrained('metodos_pago')
            ->restrictOnDelete();

        $table->decimal('monto', 14, 2);

        $table->dateTime('fecha_pago');

        $table->string('referencia', 150)
            ->nullable();

        $table->string('comprobante', 500)
            ->nullable();

        $table->string('estado', 30)
            ->default('PENDIENTE');

        $table->foreignId('registrado_por_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->foreignId('verificado_por_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->dateTime('fecha_verificacion')
            ->nullable();

        $table->string('motivo_rechazo', 255)
            ->nullable();

        $table->text('observacion')
            ->nullable();

        $table->timestamps();

        $table->index([
            'estado',
            'fecha_pago',
        ]);
    });

    DB::statement(
        'ALTER TABLE pagos
         ADD CONSTRAINT chk_pago_destino
         CHECK (
            (reserva_id IS NOT NULL AND venta_id IS NULL)
            OR
            (reserva_id IS NULL AND venta_id IS NOT NULL)
         )'
    );

    DB::statement(
        'ALTER TABLE pagos
         ADD CONSTRAINT chk_pago_monto
         CHECK (monto > 0)'
    );
}

public function down(): void
{
    Schema::dropIfExists('pagos');
}
};
