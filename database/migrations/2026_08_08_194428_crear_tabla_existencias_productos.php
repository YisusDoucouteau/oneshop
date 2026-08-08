<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existencias_productos', function (Blueprint $table) {
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('almacenes')
                ->restrictOnDelete();

            $table->unsignedInteger('cantidad_disponible')
                ->default(0);

            $table->unsignedInteger('cantidad_reservada')
                ->default(0);

            $table->timestamps();

            $table->primary([
                'producto_id',
                'almacen_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('existencias_productos');
    }
};