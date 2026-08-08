<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)
                ->unique();

            $table->foreignId('almacen_origen_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('almacen_destino_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->foreignId('solicitado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('despachado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('recibido_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('estado', 30)
                ->default('SOLICITADA');

            $table->dateTime('fecha_solicitud');

            $table->dateTime('fecha_despacho')
                ->nullable();

            $table->dateTime('fecha_recepcion')
                ->nullable();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index('estado');

            $table->index([
                'almacen_origen_id',
                'almacen_destino_id',
            ]);
        });

        DB::statement(
            'ALTER TABLE transferencias
             ADD CONSTRAINT chk_almacenes_transferencia
             CHECK (almacen_origen_id <> almacen_destino_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};