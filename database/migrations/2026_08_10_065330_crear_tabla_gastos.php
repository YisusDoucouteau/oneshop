<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_gasto_id')
                ->constrained('categorias_gastos')
                ->restrictOnDelete();

            $table->foreignId('almacen_id')
                ->nullable()
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('moneda_id')
                ->constrained('monedas')
                ->restrictOnDelete();

            $table->foreignId('tipo_cambio_id')
                ->nullable()
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->dateTime('fecha_gasto');

            $table->string('concepto', 200);

            $table->decimal('monto_origen', 14, 2);

            $table->decimal('monto_bob', 14, 2);

            $table->string('comprobante', 500)
                ->nullable();

            $table->foreignId('registrado_por_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('estado', 30)
                ->default('REGISTRADO');

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'fecha_gasto',
                'categoria_gasto_id',
            ], 'idx_gasto_fecha_categoria');
        });

        DB::statement(
            'ALTER TABLE gastos
             ADD CONSTRAINT chk_gasto_montos
             CHECK (
                monto_origen > 0
                AND monto_bob > 0
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};