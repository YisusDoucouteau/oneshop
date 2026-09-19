<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_importacion', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_cargadores')->default(0)->after('cantidad_bultos');
            $table->unsignedInteger('cantidad_accesorios')->default(0)->after('cantidad_cargadores');
            $table->text('detalle_accesorios')->nullable()->after('cantidad_accesorios');
        });
    }

    public function down(): void
    {
        Schema::table('envios_importacion', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_cargadores',
                'cantidad_accesorios',
                'detalle_accesorios',
            ]);
        });
    }
};
