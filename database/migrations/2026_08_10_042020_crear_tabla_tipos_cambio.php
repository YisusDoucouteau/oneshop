<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cambio', function (Blueprint $table) {
            $table->id();

            $table->foreignId('moneda_origen_id')
                ->constrained('monedas')
                ->restrictOnDelete();

            $table->foreignId('moneda_destino_id')
                ->constrained('monedas')
                ->restrictOnDelete();

            $table->decimal('valor', 14, 6);

            $table->dateTime('fecha_vigencia');

            $table->string('fuente', 150)
                ->nullable();

            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index([
                'moneda_origen_id',
                'moneda_destino_id',
                'fecha_vigencia',
            ], 'idx_tipo_cambio_fecha');
        });

        DB::statement(
            'ALTER TABLE tipos_cambio
             ADD CONSTRAINT chk_tipo_cambio_valor
             CHECK (valor > 0)'
        );

        DB::statement(
            'ALTER TABLE tipos_cambio
             ADD CONSTRAINT chk_tipo_cambio_monedas
             CHECK (moneda_origen_id <> moneda_destino_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cambio');
    }
};