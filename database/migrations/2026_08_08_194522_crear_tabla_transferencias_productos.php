<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_productos', function (Blueprint $table) {
            $table->foreignId('transferencia_id')
                ->constrained('transferencias')
                ->cascadeOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->unsignedInteger('cantidad_enviada');

            $table->unsignedInteger('cantidad_recibida')
                ->default(0);

            $table->string('observacion', 255)
                ->nullable();

            $table->primary([
                'transferencia_id',
                'producto_id',
            ]);
        });

        DB::statement(
            'ALTER TABLE transferencias_productos
             ADD CONSTRAINT chk_cantidad_transferencia
             CHECK (cantidad_enviada > 0)'
        );

        DB::statement(
            'ALTER TABLE transferencias_productos
             ADD CONSTRAINT chk_cantidad_recibida_transferencia
             CHECK (cantidad_recibida <= cantidad_enviada)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_productos');
    }
};