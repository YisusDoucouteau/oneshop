<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicas_garantias', function (Blueprint $table) {
            $table->date('vigente_desde')
                ->nullable()
                ->change();
        });

        Schema::table('politicas_distribucion', function (Blueprint $table) {
            $table->date('vigente_desde')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('politicas_garantias', function (Blueprint $table) {
            $table->date('vigente_desde')
                ->nullable(false)
                ->change();
        });

        Schema::table('politicas_distribucion', function (Blueprint $table) {
            $table->date('vigente_desde')
                ->nullable(false)
                ->change();
        });
    }
};