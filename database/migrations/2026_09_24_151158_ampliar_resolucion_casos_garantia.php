<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casos_garantia', function (Blueprint $table) {
            $table->text('resolucion')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('casos_garantia', function (Blueprint $table) {
            $table->string('resolucion', 40)
                ->nullable()
                ->change();
        });
    }
};