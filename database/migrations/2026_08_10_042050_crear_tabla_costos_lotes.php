<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos_lotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lote_id')
                ->constrained('lotes')
                ->restrictOnDelete();

            $table->foreignId('tipo_costo_id')
                ->constrained('tipos_costos')
                ->restrictOnDelete();

            $table->foreignId('moneda_id')
                ->constrained('monedas')
                ->restrictOnDelete();

            $table->foreignId('tipo_cambio_id')
                ->nullable()
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->decimal('monto_origen', 14, 2);

            $table->decimal('monto_bob', 14, 2);

            $table->date('fecha_costo');

            $table->string('referencia', 150)
                ->nullable();

            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'lote_id',
                'tipo_costo_id',
            ]);
        });

        DB::statement(
            'ALTER TABLE costos_lotes
             ADD CONSTRAINT chk_costo_lote_montos
             CHECK (monto_origen > 0 AND monto_bob > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('costos_lotes');
    }
};