<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depositos_ventas', function (Blueprint $table) {
            $table->foreignId('politica_distribucion_id')
                ->nullable()
                ->after('venta_id')
                ->constrained('politicas_distribucion')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('depositos_ventas', function (Blueprint $table) {
            $table->dropForeign([
                'politica_distribucion_id'
            ]);

            $table->dropColumn('politica_distribucion_id');
        });
    }
};