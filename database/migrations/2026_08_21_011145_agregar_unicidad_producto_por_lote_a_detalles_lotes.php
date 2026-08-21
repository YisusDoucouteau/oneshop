<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_lotes', function (Blueprint $table) {
            $table->unique(
                ['lote_id', 'producto_id'],
                'uq_lote_producto'
            );
        });
    }

    public function down(): void
    {
        Schema::table('detalles_lotes', function (Blueprint $table) {
            $table->dropUnique('uq_lote_producto');
        });
    }
};