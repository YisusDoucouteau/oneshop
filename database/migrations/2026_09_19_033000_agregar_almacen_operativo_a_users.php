<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('almacen_operativo_id')
                ->nullable()
                ->after('ultimo_acceso');

            $table->foreign(
                'almacen_operativo_id',
                'fk_users_almacen_operativo'
            )
                ->references('id')
                ->on('almacenes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_almacen_operativo');
            $table->dropColumn('almacen_operativo_id');
        });
    }
};
